<?php
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function send_json($code, $payload)
{
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

$old_coach_inventory_id = (int) ($input['old_coach_inventory_id'] ?? 0);
$disposition            = trim($input['disposition'] ?? '');  // 'inventory' or 'scrap'
$new_unit_id            = (int) ($input['new_unit_id'] ?? 0);
$coach_id               = (int) ($input['coach_id'] ?? 0);
$user_id                = (int) ($input['user_id'] ?? 0);
$schedule_id            = (int) ($input['schedule_id'] ?? 0);
$auditor_id             = (int) ($input['auditor_id'] ?? 0);
$train_info_id          = (int) ($input['train_info_id'] ?? 0);

if ($old_coach_inventory_id <= 0 || $new_unit_id <= 0 || $coach_id <= 0 || $user_id <= 0) {
    send_json(422, [
        'success' => false,
        'message' => 'old_coach_inventory_id, new_unit_id, coach_id, user_id are required.'
    ]);
}

if (!in_array($disposition, ['inventory', 'scrap'], true)) {
    send_json(422, [
        'success' => false,
        'message' => "disposition must be 'inventory' or 'scrap'."
    ]);
}

// Fetch old coach_inventory to get old unit_id
$old_ci_result = $conn->query("
    SELECT id, inventory_unit_id
    FROM fdss_coach_inventory
    WHERE inventory_unit_id = {$old_coach_inventory_id}
    LIMIT 1
");
if (!$old_ci_result) {
    send_json(500, ['success' => false, 'message' => "Query error: {$conn->error}"]);
}
$old_ci = $old_ci_result->fetch_assoc();
if (!$old_ci) {
    send_json(404, ['success' => false, 'message' => 'Old coach inventory record not found.']);
}
$old_unit_id = (int) $old_ci['inventory_unit_id'];

// Fetch new unit details for inspection
$new_unit_result = $conn->query("
    SELECT iu.unit_id, iu.inventory_id, iu.inventory_parameter_id,
           iu.serial_number,
           im.item_name
    FROM fdds_inventory_unit iu
    INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
    WHERE iu.unit_id = {$new_unit_id}
    LIMIT 1
");
if (!$new_unit_result) {
    send_json(500, ['success' => false, 'message' => "Query error: {$conn->error}"]);
}
$new_unit = $new_unit_result->fetch_assoc();
if (!$new_unit) {
    send_json(404, ['success' => false, 'message' => 'New unit not found.']);
}

// ── Transaction ──────────────────────────────────────────────
$conn->begin_transaction();
try {

    // STEP 1a: Delete old coach_inventory record
    if (!$conn->query("DELETE FROM fdss_coach_inventory WHERE inventory_unit_id = {$old_coach_inventory_id}")) {
        throw new Exception("Step 1a failed: {$conn->error}");
    }

    // STEP 1b: Update old unit based on disposition
    if ($disposition === 'inventory') {
        $old_status = 'Working';
        $old_use    = 0;
    } else {
        $old_status = 'Not_working';
        $old_use    = 2;
    }
    if (!$conn->query("UPDATE fdds_inventory_unit SET unit_status = '{$old_status}', use_status = {$old_use} WHERE unit_id = {$old_unit_id}")) {
        throw new Exception("Step 1b failed: {$conn->error}");
    }

    // STEP 2a: Insert new unit into fdss_coach_inventory
    if (!$conn->query("
        INSERT INTO fdss_coach_inventory (coach_id, inventory_unit_id, user_id, status, warranty_replace_status)
        VALUES ({$coach_id}, {$new_unit_id}, {$user_id}, 'Active', 'normal')
    ")) {
        throw new Exception("Step 2a failed: {$conn->error}");
    }
    $new_coach_inventory_id = (int) $conn->insert_id;

    // STEP 2b: Mark new unit as in-use
    if (!$conn->query("UPDATE fdds_inventory_unit SET use_status = 1 WHERE unit_id = {$new_unit_id}")) {
        throw new Exception("Step 2b failed: {$conn->error}");
    }

    // STEP 3: Auto-create inspection for new unit
    $inv_id       = (int) $new_unit['inventory_id'];
    $inv_param_id = $new_unit['inventory_parameter_id'] !== null ? (int) $new_unit['inventory_parameter_id'] : 'NULL';
    $sql_auditor  = $auditor_id > 0 ? $auditor_id : 'NULL';

    // Verify schedule_id exists before using it (FK constraint)
    $sql_sched = 'NULL';
    if ($schedule_id > 0) {
        $sched_check = $conn->query("SELECT schedule_id FROM fdss_coach_schedule WHERE schedule_id = {$schedule_id} LIMIT 1");
        if ($sched_check && $sched_check->num_rows > 0) {
            $sql_sched = $schedule_id;
        }
    }
    $sql_train = $train_info_id > 0 ? $train_info_id : 'NULL';
    $esc_tool   = $conn->real_escape_string($new_unit['item_name'] ?? '');
    $esc_serial = $conn->real_escape_string($new_unit['serial_number'] ?? '');

    if (!$conn->query("
        INSERT INTO fdds_coach_inspection
            (schedule_id, train_info_id, coach_id, auditor_id, user_id,
             inventory_id, inventory_parameter_id,
             unit_id, tool_name, Serial_No,
             Conditions, status, remarks)
        VALUES
            ({$sql_sched}, {$sql_train}, {$coach_id}, {$sql_auditor}, {$user_id},
             {$inv_id}, {$inv_param_id},
             {$new_unit_id}, '{$esc_tool}', '{$esc_serial}',
             'ok', 'normal', 'done')
    ")) {
        throw new Exception("Step 3 failed: {$conn->error}");
    }
    $inspection_id = (int) $conn->insert_id;

    $conn->commit();

    send_json(200, [
        'success' => true,
        'message' => 'Old unit removed, new unit added to coach, inspection auto-created.',
        'result'  => [
            'old_unit' => [
                'unit_id'     => $old_unit_id,
                'disposition' => $disposition,
                'unit_status' => $old_status,
                'use_status'  => $old_use
            ],
            'new_unit' => [
                'unit_id'                 => $new_unit_id,
                'coach_inventory_id'      => $new_coach_inventory_id,
                'status'                  => 'Active',
                'warranty_replace_status' => 'normal',
                'use_status'              => 1
            ],
            'inspection' => [
                'inspection_id' => $inspection_id,
                'conditions'    => 'ok',
                'status'        => 'normal',
                'remarks'       => 'done'
            ]
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    send_json(500, ['success' => false, 'message' => $e->getMessage()]);
}
