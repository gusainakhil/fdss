<?php
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function send_json($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(405, ['success' => false, 'message' => 'Only POST method is allowed.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$coach_inventory_id      = isset($input['coach_inventory_id'])     ? (int)$input['coach_inventory_id']     : 0;
$inventory_unit_id       = isset($input['inventory_unit_id'])       ? (int)$input['inventory_unit_id']       : 0;
$user_id                 = isset($input['user_id'])                 ? (int)$input['user_id']                 : 0;
$coach_id                = isset($input['coach_id'])                ? (int)$input['coach_id']                : 0;
$old_coach_inventory_id  = isset($input['old_coach_inventory_id'])  ? (int)$input['old_coach_inventory_id']  : 0;
$schedule_id             = isset($input['schedule_id'])             ? (int)$input['schedule_id']             : 0;
$auditor_id              = isset($input['auditor_id'])              ? (int)$input['auditor_id']              : 0;
$train_info_id           = isset($input['train_info_id'])           ? (int)$input['train_info_id']           : 0;
$status                  = trim($input['status']                   ?? 'Active');
$wrs                     = strtolower(trim($input['warranty_replace_status'] ?? 'normal'));
$warranty_replace_status = ($wrs === '' || $wrs === 'null') ? 'normal' : $wrs;

// Auto-detect action
if (isset($input['action'])) {
    $action = strtolower(trim($input['action']));
} elseif ($coach_inventory_id > 0 && $inventory_unit_id > 0 && $user_id > 0) {
    $action = 'replace'; // mark old inactive + add new
} elseif ($coach_inventory_id > 0) {
    $action = 'inactive';
} else {
    $action = 'add';
}

// ── INACTIVE action ───────────────────────────────────────────────────────────
if ($action === 'inactive') {
    if ($coach_inventory_id <= 0) {
        send_json(422, ['success' => false, 'message' => 'coach_inventory_id is required.']);
    }

    $fetch_stmt = $conn->prepare("SELECT id, inventory_unit_id FROM fdss_coach_inventory WHERE id = ? LIMIT 1");
    if (!$fetch_stmt) {
        send_json(500, ['success' => false, 'message' => "DB error: {$conn->error}"]);
    }
    $fetch_stmt->bind_param('i', $coach_inventory_id);
    $fetch_stmt->execute();
    $record = $fetch_stmt->get_result()->fetch_assoc();
    $fetch_stmt->close();

    if (!$record) {
        send_json(404, ['success' => false, 'message' => 'Record not found.']);
    }

    $unit_id_to_reset = (int)$record['inventory_unit_id'];

    $conn->begin_transaction();
    try {
        // Mark coach inventory record as Inactive (inactive)
        $upd_stmt = $conn->prepare("UPDATE fdss_coach_inventory SET status = 'Inactive' WHERE id = ?");
        if (!$upd_stmt) throw new Exception("Update prepare error: {$conn->error}");
        $upd_stmt->bind_param('i', $coach_inventory_id);
        if (!$upd_stmt->execute()) throw new Exception("Update failed: {$upd_stmt->error}");
        $upd_stmt->close();

        // Reset use_status = 0 on the unit
        $reset_stmt = $conn->prepare("UPDATE fdds_inventory_unit SET use_status = 0 WHERE unit_id = ?");
        if (!$reset_stmt) throw new Exception("use_status reset error: {$conn->error}");
        $reset_stmt->bind_param('i', $unit_id_to_reset);
        if (!$reset_stmt->execute()) throw new Exception("use_status reset failed: {$reset_stmt->error}");
        $reset_stmt->close();

        $conn->commit();
        send_json(200, [
            'success'            => true,
            'message'            => 'Marked as inactive and unit is now available.',
            'coach_inventory_id' => $coach_inventory_id,
            'inventory_unit_id'  => $unit_id_to_reset,
            'status'             => 'Inactive',
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        send_json(500, ['success' => false, 'message' => 'Update failed.', 'error' => $e->getMessage()]);
    }
}
// ─────────────────────────────────────────────────────────────────────────────

// ── REPLACE action (mark old inactive + add new) ──────────────────────────────
if ($action === 'replace') {
    if ($coach_inventory_id <= 0 || $inventory_unit_id <= 0 || $user_id <= 0) {
        send_json(422, ['success' => false, 'message' => 'coach_inventory_id, inventory_unit_id and user_id are required for replace.']);
    }

    // Fetch old record
    $fetch_stmt = $conn->prepare("SELECT id, inventory_unit_id FROM fdss_coach_inventory WHERE id = ? LIMIT 1");
    if (!$fetch_stmt) send_json(500, ['success' => false, 'message' => "DB error: {$conn->error}"]);
    $fetch_stmt->bind_param('i', $coach_inventory_id);
    $fetch_stmt->execute();
    $old_record = $fetch_stmt->get_result()->fetch_assoc();
    $fetch_stmt->close();

    if (!$old_record) send_json(404, ['success' => false, 'message' => 'Old record not found.']);

    $old_unit_id = (int)$old_record['inventory_unit_id'];

    $allowed_wrs = ['warranty', 'replace', 'normal'];
    if (!in_array($warranty_replace_status, $allowed_wrs, true)) $warranty_replace_status = 'normal';

    $conn->begin_transaction();
    try {
        // Mark old record Inactive
        $inact_stmt = $conn->prepare("UPDATE fdss_coach_inventory SET status = 'Inactive' WHERE id = ?");
        if (!$inact_stmt) throw new Exception("Inactive prepare error: {$conn->error}");
        $inact_stmt->bind_param('i', $coach_inventory_id);
        if (!$inact_stmt->execute()) throw new Exception("Inactive update failed: {$inact_stmt->error}");
        $inact_stmt->close();

        // Mark old unit as not working
        $r1 = $conn->prepare("UPDATE fdds_inventory_unit SET use_status = 2, unit_status = 'Not_working' WHERE unit_id = ?");
        if (!$r1) throw new Exception("Reset prepare error: {$conn->error}");
        $r1->bind_param('i', $old_unit_id);
        if (!$r1->execute()) throw new Exception("Reset failed: {$r1->error}");
        $r1->close();

        // Insert new record
        $coach_id_val = $coach_id > 0 ? $coach_id : null;
        $new_status   = 'Active';
        $ins = $conn->prepare("INSERT INTO fdss_coach_inventory (coach_id, inventory_unit_id, user_id, status, warranty_replace_status) VALUES (?, ?, ?, ?, ?)");
        if (!$ins) throw new Exception("Insert prepare error: {$conn->error}");
        $ins->bind_param('iiiss', $coach_id_val, $inventory_unit_id, $user_id, $new_status, $warranty_replace_status);
        if (!$ins->execute()) throw new Exception("Insert failed: {$ins->error}");
        $new_id = $conn->insert_id;
        $ins->close();

        // Set new unit use_status = 1
        if ($coach_id > 0) {
            $r2 = $conn->prepare("UPDATE fdds_inventory_unit SET use_status = 1 WHERE unit_id = ?");
            if (!$r2) throw new Exception("use_status update error: {$conn->error}");
            $r2->bind_param('i', $inventory_unit_id);
            if (!$r2->execute()) throw new Exception("use_status update failed: {$r2->error}");
            $r2->close();
        }

        // Fetch new unit details for auto-inspection
        $nu = $conn->query("
            SELECT iu.inventory_id, iu.inventory_parameter_id, iu.serial_number, im.item_name
            FROM fdds_inventory_unit iu
            INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
            WHERE iu.unit_id = {$inventory_unit_id} LIMIT 1
        ");
        if (!$nu) throw new Exception("New unit fetch failed: {$conn->error}");
        $new_unit = $nu->fetch_assoc();

        if ($new_unit) {
            $r_inv_id       = (int)$new_unit['inventory_id'];
            $r_inv_param_id = $new_unit['inventory_parameter_id'] !== null ? (int)$new_unit['inventory_parameter_id'] : 'NULL';
            $r_coach        = $coach_id      > 0 ? $coach_id      : 'NULL';
            $r_auditor      = $auditor_id    > 0 ? $auditor_id    : 'NULL';
            $r_sched        = $schedule_id   > 0 ? $schedule_id   : 'NULL';
            $r_train        = $train_info_id > 0 ? $train_info_id : 'NULL';
            $r_esc_tool     = $conn->real_escape_string($new_unit['item_name']     ?? '');
            $r_esc_serial   = $conn->real_escape_string($new_unit['serial_number'] ?? '');

            if (!$conn->query("
                INSERT INTO fdds_coach_inspection
                    (schedule_id, train_info_id, coach_id, auditor_id, user_id,
                     inventory_id, inventory_parameter_id,
                     unit_id, tool_name, Serial_No, Conditions, status, remarks)
                VALUES
                    ({$r_sched}, {$r_train}, {$r_coach}, {$r_auditor}, {$user_id},
                     {$r_inv_id}, {$r_inv_param_id},
                     {$inventory_unit_id}, '{$r_esc_tool}', '{$r_esc_serial}',
                     'ok', 'normal', 'done')
            ")) {
                throw new Exception("Inspection insert failed: {$conn->error}");
            }
            $replace_inspection_id = (int)$conn->insert_id;
        }

        $conn->commit();
        send_json(200, [
            'success'                => true,
            'message'                => 'Old record marked inactive, new record added.',
            'old_coach_inventory_id' => $coach_inventory_id,
            'new_coach_inventory_id' => (int)$new_id,
            'inventory_unit_id'      => $inventory_unit_id,
            'inspection_id'          => $replace_inspection_id ?? null,
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        send_json(500, ['success' => false, 'message' => 'Replace failed.', 'error' => $e->getMessage()]);
    }
}
// ─────────────────────────────────────────────────────────────────────────────

if ($action !== 'add') {
    send_json(400, ['success' => false, 'message' => 'Invalid action.']);
}

// Validate required fields
if ($inventory_unit_id <= 0 || $user_id <= 0) {
    send_json(422, ['success' => false, 'message' => 'inventory_unit_id and user_id are required.']);
}

// Validate enum values
$allowed_status = ['Active', 'Inactive'];
$allowed_warranty = ['warranty', 'replace', 'normal'];

if (!in_array($status, $allowed_status, true)) {
    $status = 'Active';
}
if (!in_array($warranty_replace_status, $allowed_warranty, true)) {
    $warranty_replace_status = 'normal';
}

// Auto-add updated_at column if missing
$col_check = $conn->query("SHOW COLUMNS FROM fdss_coach_inventory LIKE 'updated_at'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE fdss_coach_inventory
                  ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL
                  ON UPDATE CURRENT_TIMESTAMP");
}

// Verify inventory_unit_id belongs to this user
$unit_stmt = $conn->prepare("
    SELECT iu.unit_id, iu.use_status, iu.inventory_id, iu.inventory_parameter_id,
           iu.serial_number, im.item_name
    FROM fdds_inventory_unit iu
    INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
    WHERE iu.unit_id = ?
      AND iu.user_id = ?
    LIMIT 1
");

if (!$unit_stmt) {
    send_json(500, ['success' => false, 'message' => 'DB error: ' . $conn->error]);
}

$unit_stmt->bind_param('ii', $inventory_unit_id, $user_id);
$unit_stmt->execute();
$unit = $unit_stmt->get_result()->fetch_assoc();
$unit_stmt->close();

if (!$unit) {
    send_json(404, ['success' => false, 'message' => 'Inventory unit not found for this user.']);
}

// Verify coach belongs to this user (if provided)
if ($coach_id > 0) {
    $coach_stmt = $conn->prepare("
        SELECT coach_id FROM fdss_train_coach
        WHERE coach_id = ? AND user_id = ?
        LIMIT 1
    ");
    if (!$coach_stmt) {
        send_json(500, ['success' => false, 'message' => 'DB error: ' . $conn->error]);
    }
    $coach_stmt->bind_param('ii', $coach_id, $user_id);
    $coach_stmt->execute();
    $coach_row = $coach_stmt->get_result()->fetch_assoc();
    $coach_stmt->close();

    if (!$coach_row) {
        send_json(404, ['success' => false, 'message' => 'Coach not found for this user.']);
    }
}

// Fetch old unit_id if replacing
$old_unit_id = 0;
if ($old_coach_inventory_id > 0) {
    $old_ci_stmt = $conn->prepare("SELECT inventory_unit_id FROM fdss_coach_inventory WHERE id = ? LIMIT 1");
    if (!$old_ci_stmt) {
        send_json(500, ['success' => false, 'message' => "DB error: {$conn->error}"]);
    }
    $old_ci_stmt->bind_param('i', $old_coach_inventory_id);
    $old_ci_stmt->execute();
    $old_ci = $old_ci_stmt->get_result()->fetch_assoc();
    $old_ci_stmt->close();
    if (!$old_ci) {
        send_json(404, ['success' => false, 'message' => 'Old coach inventory record not found.']);
    }
    $old_unit_id = (int)$old_ci['inventory_unit_id'];
}

$conn->begin_transaction();

try {
    // Mark old record Inactive and update old unit status (if replacing)
    if ($old_coach_inventory_id > 0 && $old_unit_id > 0) {
        $inact_stmt = $conn->prepare("UPDATE fdss_coach_inventory SET status = 'Inactive' WHERE id = ?");
        if (!$inact_stmt) throw new Exception("Inactive prepare error: {$conn->error}");
        $inact_stmt->bind_param('i', $old_coach_inventory_id);
        if (!$inact_stmt->execute()) throw new Exception("Inactive update failed: {$inact_stmt->error}");
        $inact_stmt->close();

        $old_upd = $conn->prepare("UPDATE fdds_inventory_unit SET use_status = 2, unit_status = 'replace' WHERE unit_id = ?");
        if (!$old_upd) throw new Exception("Old unit update error: {$conn->error}");
        $old_upd->bind_param('i', $old_unit_id);
        if (!$old_upd->execute()) throw new Exception("Old unit update failed: {$old_upd->error}");
        $old_upd->close();
    }

    // Insert into fdss_coach_inventory
    $insert_stmt = $conn->prepare("
        INSERT INTO fdss_coach_inventory
            (coach_id, inventory_unit_id, user_id, status, warranty_replace_status)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$insert_stmt) {
        throw new Exception('Insert prepare error: ' . $conn->error);
    }

    $coach_id_val = $coach_id > 0 ? $coach_id : null;
    $insert_stmt->bind_param('iiiss', $coach_id_val, $inventory_unit_id, $user_id, $status, $warranty_replace_status);

    if (!$insert_stmt->execute()) {
        throw new Exception('Insert failed: ' . $insert_stmt->error);
    }

    $new_id = $conn->insert_id;
    $insert_stmt->close();

    // Set use_status = 1 when coach is assigned
    if ($coach_id > 0) {
        $use_stmt = $conn->prepare("UPDATE fdds_inventory_unit SET use_status = 1 WHERE unit_id = ?");
        if (!$use_stmt) throw new Exception("use_status update error: {$conn->error}");
        $use_stmt->bind_param('i', $inventory_unit_id);
        if (!$use_stmt->execute()) throw new Exception("use_status update failed: {$use_stmt->error}");
        $use_stmt->close();
    }

    // Auto-create inspection for new unit
    $insp_inv_id    = (int)$unit['inventory_id'];
    $insp_param_id  = $unit['inventory_parameter_id'] !== null ? (int)$unit['inventory_parameter_id'] : null;
    $insp_coach_id  = $coach_id     > 0 ? $coach_id     : null;
    $insp_auditor   = $auditor_id   > 0 ? $auditor_id   : null;
    $insp_train     = $train_info_id > 0 ? $train_info_id : null;
    $insp_tool      = $unit['item_name']    ?? '';
    $insp_serial    = $unit['serial_number'] ?? '';
    $insp_cond      = 'ok';
    $insp_status    = 'normal';
    $insp_remarks   = 'done';

    $insp_sched = null;
    if ($schedule_id > 0) {
        $sc = $conn->prepare("SELECT schedule_id FROM fdss_coach_schedule WHERE schedule_id = ? LIMIT 1");
        if ($sc) { $sc->bind_param('i', $schedule_id); $sc->execute(); if ($sc->get_result()->num_rows > 0) $insp_sched = $schedule_id; $sc->close(); }
    }

    $insp_stmt = $conn->prepare("
        INSERT INTO fdds_coach_inspection
            (schedule_id, train_info_id, coach_id, auditor_id, user_id,
             inventory_id, inventory_parameter_id,
             unit_id, tool_name, Serial_No,
             Conditions, status, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$insp_stmt) throw new Exception("Inspection prepare error: {$conn->error}");
    $insp_stmt->bind_param(
        'iiiiiiiisssss',
        $insp_sched, $insp_train, $insp_coach_id, $insp_auditor, $user_id,
        $insp_inv_id, $insp_param_id,
        $inventory_unit_id, $insp_tool, $insp_serial,
        $insp_cond, $insp_status, $insp_remarks
    );
    if (!$insp_stmt->execute()) throw new Exception("Inspection insert failed: {$insp_stmt->error}");
    $inspection_id = (int)$conn->insert_id;
    $insp_stmt->close();

    $conn->commit();

    send_json(200, [
        'success' => true,
        'message' => 'Coach inventory record added successfully.',
        'data' => [
            'id'                      => (int)$new_id,
            'coach_id'                => $coach_id > 0 ? $coach_id : null,
            'inventory_unit_id'       => $inventory_unit_id,
            'user_id'                 => $user_id,
            'status'                  => $status,
            'warranty_replace_status' => $warranty_replace_status,
            'item_name'               => $unit['item_name'],
            'old_unit_replaced'       => $old_unit_id > 0 ? $old_unit_id : null,
            'inspection_id'           => $inspection_id,
        ],
    ]);
} catch (Exception $e) {
    $conn->rollback();
    send_json(500, ['success' => false, 'message' => 'Failed to add record.', 'error' => $e->getMessage()]);
}
