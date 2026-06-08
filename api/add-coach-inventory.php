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

$coach_inventory_id      = isset($input['coach_inventory_id']) ? (int)$input['coach_inventory_id'] : 0;
$inventory_unit_id       = isset($input['inventory_unit_id'])   ? (int)$input['inventory_unit_id']   : 0;
$user_id                 = isset($input['user_id'])             ? (int)$input['user_id']             : 0;
$coach_id                = isset($input['coach_id'])            ? (int)$input['coach_id']            : 0;
$status                  = trim($input['status']               ?? 'Active');
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

        // Reset old unit use_status = 0 and set unit_status = warranty_claim_process
        $r1 = $conn->prepare("UPDATE fdds_inventory_unit SET use_status = 0, unit_status = 'warranty_claim_process' WHERE unit_id = ?");
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

        $conn->commit();
        send_json(200, [
            'success'                => true,
            'message'                => 'Old record marked inactive, new record added.',
            'old_coach_inventory_id' => $coach_inventory_id,
            'new_coach_inventory_id' => (int)$new_id,
            'inventory_unit_id'      => $inventory_unit_id,
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
    SELECT iu.unit_id, iu.use_status, im.item_name
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

$conn->begin_transaction();

try {
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
        if (!$use_stmt) {
            throw new Exception("use_status update error: {$conn->error}");
        }
        $use_stmt->bind_param('i', $inventory_unit_id);
        if (!$use_stmt->execute()) {
            throw new Exception("use_status update failed: {$use_stmt->error}");
        }
        $use_stmt->close();
    }

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
        ],
    ]);
} catch (Exception $e) {
    $conn->rollback();
    send_json(500, ['success' => false, 'message' => 'Failed to add record.', 'error' => $e->getMessage()]);
}
