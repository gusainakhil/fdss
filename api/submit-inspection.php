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


function send_json($status_code, $payload)
{
    http_response_code($status_code);
    echo json_encode($payload);
    exit;
}

function clean_text($value)
{
    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function yes_no($value)
{
    $value = strtolower(trim((string) $value));
    return in_array($value, ['yes', 'y', 'true', '1'], true) ? 'yes' : 'no';
}

function db_columns($conn, $table)
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `$table`");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = true;
        }
    }

    return $columns;
}

function find_table_by_column($conn, $column, $preferred_tables = [])
{
    foreach ($preferred_tables as $table) {
        $escaped_table = $conn->real_escape_string($table);
        $escaped_column = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM `$escaped_table` LIKE '$escaped_column'");

        if ($result && $result->num_rows > 0) {
            return $table;
        }
    }

    $stmt = $conn->prepare("SELECT TABLE_NAME
                            FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE()
                            AND COLUMN_NAME = ?
                            LIMIT 1");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['TABLE_NAME'] ?? null;
}

function bind_and_execute($stmt, $types, &$values)
{
    if ($types !== '') {
        $refs = [];
        $refs[] = $types;

        foreach ($values as $key => $value) {
            $refs[] = &$values[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    return $stmt->execute();
}

function add_if_column(&$columns, &$placeholders, &$types, &$values, $available_columns, $column, $type, $value)
{
    if (isset($available_columns[$column])) {
        $columns[] = "`$column`";
        $placeholders[] = '?';
        $types .= $type;
        $values[] = $value;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(405, [
        'success' => false,
        'message' => 'Only POST method is allowed.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_POST;
}

$auditor_id = (int) ($input['auditor_id'] ?? 0);
$user_id = (int) ($input['user_id'] ?? 0);
$schedule_id = (int) ($input['schedule_id'] ?? 0);
$coach_id = (int) ($input['coach_id'] ?? 0);
$coach_inventory_id = (int) ($input['coach_inventory_id'] ?? 0);
$unit_id = (int) ($input['unit_id'] ?? ($input['inventory_unit_id'] ?? 0));

$working_status = yes_no($input['workingStatus'] ?? '');
$detach_coach = yes_no($input['detachCoach'] ?? '');
$in_warranty = yes_no($input['inWarranty'] ?? '');
$part_replaced = yes_no($input['partReplaced'] ?? '');

$param_title = clean_text($input['paramTitle'] ?? null);
$coach_no = clean_text($input['coach_no'] ?? null);
$coach_type = clean_text($input['coach_type'] ?? null);
$defective_cause = clean_text($input['defectiveCause'] ?? null);
$other_observation = clean_text($input['otherObservation'] ?? null);
$reference_no = clean_text($input['referenceNo'] ?? null);
$remarks = clean_text($input['remarks'] ?? null);
$suggestion = clean_text($input['suggestion'] ?? null);

if ($auditor_id <= 0 || $user_id <= 0 || $schedule_id <= 0 || $coach_id <= 0 || ($coach_inventory_id <= 0 && $unit_id <= 0)) {
    send_json(422, [
        'success' => false,
        'message' => 'auditor_id, user_id, schedule_id, coach_id and coach_inventory_id or unit_id are required.'
    ]);
}

if ($param_title === null) {
    send_json(422, [
        'success' => false,
        'message' => 'paramTitle is required.'
    ]);
}

$schedule_query = "SELECT
                    s.schedule_id,
                    s.coach_id,
                    s.train_info_id,
                    s.auditor_id,
                    s.user_id AS owner_user_id,
                    s.next_due_date,
                    c.coach_no AS db_coach_no,
                    c.coach_type AS db_coach_type
                  FROM fdss_coach_schedule s
                  LEFT JOIN fdss_train_coach c
                    ON c.coach_id = s.coach_id
                  WHERE s.schedule_id = ?
                  AND s.auditor_id = ?
                  AND s.user_id = ?
                  AND s.coach_id = ?
                  LIMIT 1";

$schedule_stmt = $conn->prepare($schedule_query);

if (!$schedule_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Schedule SQL error.'
    ]);
}

$schedule_stmt->bind_param('iiii', $schedule_id, $auditor_id, $user_id, $coach_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();
$schedule_stmt->close();

if (!$schedule) {
    send_json(404, [
        'success' => false,
        'message' => 'Schedule not found for this auditor.'
    ]);
}

$inventory_query = "SELECT
                        ci.id AS coach_inventory_id,
                        ci.coach_id,
                        ci.user_id AS owner_user_id,
                        iu.unit_id,
                        iu.inventory_id,
                        iu.serial_number,
                        im.item_name
                    FROM fdss_coach_inventory ci
                    INNER JOIN fdds_inventory_unit iu
                        ON iu.unit_id = ci.inventory_unit_id
                    INNER JOIN fdss_Inventory_Management im
                        ON im.inventory_id = iu.inventory_id
                    WHERE ci.coach_id = ?
                    AND ci.user_id = ?
                    AND (
                        ci.id = ?
                        OR iu.unit_id = ?
                    )
                    LIMIT 1";

$inventory_stmt = $conn->prepare($inventory_query);

if (!$inventory_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Inventory SQL error.'
    ]);
}

$owner_user_id = (int) $schedule['owner_user_id'];

$lookup_coach_inventory_id = $coach_inventory_id;
$lookup_unit_id = $unit_id > 0 ? $unit_id : $coach_inventory_id;

$inventory_stmt->bind_param('iiii', $coach_id, $owner_user_id, $lookup_coach_inventory_id, $lookup_unit_id);
$inventory_stmt->execute();
$inventory_result = $inventory_stmt->get_result();
$inventory = $inventory_result->fetch_assoc();
$inventory_stmt->close();

if (!$inventory) {
    send_json(404, [
        'success' => false,
        'message' => 'Inventory item not found for this schedule.'
    ]);
}

$coach_inventory_id = (int) $inventory['coach_inventory_id'];
$unit_id = (int) $inventory['unit_id'];
$coach_no = $coach_no ?: $schedule['db_coach_no'];
$coach_type = $coach_type ?: $schedule['db_coach_type'];
$condition = $working_status;
$warranty_replace_status = 'normal';

if ($part_replaced === 'yes') {
    $warranty_replace_status = 'replace';
} elseif ($in_warranty === 'yes') {
    $warranty_replace_status = 'warranty';
}

$combined_remarks = $remarks;

$inspection_columns_available = db_columns($conn, 'fdds_coach_inspection');
$coach_inventory_columns_available = db_columns($conn, 'fdss_coach_inventory');
$warranty_claim_table = find_table_by_column($conn, 'warranty_claim_id', [
    'fdss_warranty_claim',
    'fdds_warranty_claim',
    'warranty_claim',
    'warranty_claims',
    'fdss_warranty_claims',
    'fdds_warranty_claims'
]);
$warranty_claim_columns_available = $warranty_claim_table ? db_columns($conn, $warranty_claim_table) : [];

$conn->begin_transaction();

try {
    $insert_columns = [];
    $insert_placeholders = [];
    $insert_types = '';
    $insert_values = [];

    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'schedule_id', 'i', $schedule_id);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'train_info_id', 'i', $schedule['train_info_id'] !== null ? (int) $schedule['train_info_id'] : null);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'coach_id', 'i', $coach_id);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'auditor_id', 'i', $auditor_id);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'user_id', 'i', $user_id);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'inventory_id', 'i', (int) $inventory['inventory_id']);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'tool_name', 's', $param_title);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'Serial_No', 's', (string) ($inventory['serial_number'] ?? '0'));
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'Conditions', 's', $condition);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'unit_id', 'i', $unit_id);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'status', 's', 'Completed');
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'remarks', 's', $combined_remarks);

    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'coach_inventory_id', 'i', $coach_inventory_id);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'coach_no', 's', $coach_no);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'coach_type', 's', $coach_type);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'working_status', 's', $working_status);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'defective_cause', 's', $defective_cause);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'detach_coach', 's', $detach_coach);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'in_warranty', 's', $in_warranty);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'part_replaced', 's', $part_replaced);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'reference_no', 's', $reference_no);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'suggestion', 's', $suggestion);
    add_if_column($insert_columns, $insert_placeholders, $insert_types, $insert_values, $inspection_columns_available, 'other_observation', 's', $other_observation);

    $insert_query = 'INSERT INTO fdds_coach_inspection (' . implode(', ', $insert_columns) . ')
                     VALUES (' . implode(', ', $insert_placeholders) . ')';
    $insert_stmt = $conn->prepare($insert_query);

    if (!$insert_stmt || !bind_and_execute($insert_stmt, $insert_types, $insert_values)) {
        throw new Exception('Inspection insert failed: ' . ($insert_stmt ? $insert_stmt->error : $conn->error));
    }

    $inspection_id = $conn->insert_id;
    $insert_stmt->close();

    if (!$warranty_claim_table) {
        throw new Exception('Warranty claim table not found.');
    }

    $claim_columns = [];
    $claim_placeholders = [];
    $claim_types = '';
    $claim_values = [];

    add_if_column($claim_columns, $claim_placeholders, $claim_types, $claim_values, $warranty_claim_columns_available, 'schedule_id', 'i', $schedule_id);
    add_if_column($claim_columns, $claim_placeholders, $claim_types, $claim_values, $warranty_claim_columns_available, 'unit_id', 'i', $unit_id);
    add_if_column($claim_columns, $claim_placeholders, $claim_types, $claim_values, $warranty_claim_columns_available, 'defectiveCause', 's', $defective_cause);
    add_if_column($claim_columns, $claim_placeholders, $claim_types, $claim_values, $warranty_claim_columns_available, 'otherObservation', 's', $other_observation);
    add_if_column($claim_columns, $claim_placeholders, $claim_types, $claim_values, $warranty_claim_columns_available, 'referenceNo', 's', $reference_no);
    add_if_column($claim_columns, $claim_placeholders, $claim_types, $claim_values, $warranty_claim_columns_available, 'suggestion', 's', $suggestion);
    add_if_column($claim_columns, $claim_placeholders, $claim_types, $claim_values, $warranty_claim_columns_available, 'status', 's', 'claim process');

    if (isset($warranty_claim_columns_available['created_at'])) {
        $claim_columns[] = '`created_at`';
        $claim_placeholders[] = 'NOW()';
    }

    if (empty($claim_columns)) {
        throw new Exception('Warranty claim columns not found.');
    }

    $claim_query = 'INSERT INTO `' . $warranty_claim_table . '` (' . implode(', ', $claim_columns) . ')
                    VALUES (' . implode(', ', $claim_placeholders) . ')';
    $claim_stmt = $conn->prepare($claim_query);

    if (!$claim_stmt || !bind_and_execute($claim_stmt, $claim_types, $claim_values)) {
        throw new Exception('Warranty claim insert failed: ' . ($claim_stmt ? $claim_stmt->error : $conn->error));
    }

    $warranty_claim_id = $conn->insert_id;
    $claim_stmt->close();

    $inventory_updates = [];
    $inventory_types = '';
    $inventory_values = [];

    if (isset($coach_inventory_columns_available['warranty_replace_status'])) {
        $inventory_updates[] = 'warranty_replace_status = ?';
        $inventory_types .= 's';
        $inventory_values[] = $warranty_replace_status;
    }

    if (isset($coach_inventory_columns_available['status']) && $part_replaced === 'yes') {
        $inventory_updates[] = 'status = ?';
        $inventory_types .= 's';
        $inventory_values[] = 'Expired';
    }

    if (!empty($inventory_updates)) {
        $inventory_types .= 'i';
        $inventory_values[] = $coach_inventory_id;
        $inventory_update_query = 'UPDATE fdss_coach_inventory
                                   SET ' . implode(', ', $inventory_updates) . '
                                   WHERE id = ?';
        $inventory_update_stmt = $conn->prepare($inventory_update_query);

        if (!$inventory_update_stmt || !bind_and_execute($inventory_update_stmt, $inventory_types, $inventory_values)) {
            throw new Exception('Coach inventory update failed: ' . ($inventory_update_stmt ? $inventory_update_stmt->error : $conn->error));
        }

        $inventory_update_stmt->close();
    }

    $schedule_stmt = $conn->prepare("UPDATE fdss_coach_schedule
                                     SET status = 'Completed',
                                         last_inspection_date = CURDATE()
                                     WHERE schedule_id = ?
                                     AND auditor_id = ?");

    if (!$schedule_stmt) {
        throw new Exception('Schedule update SQL error: ' . $conn->error);
    }

    $schedule_stmt->bind_param('ii', $schedule_id, $auditor_id);

    if (!$schedule_stmt->execute()) {
        throw new Exception('Schedule update failed: ' . $schedule_stmt->error);
    }

    $schedule_stmt->close();

    if ($detach_coach === 'yes') {
        $coach_stmt = $conn->prepare("UPDATE fdss_train_coach
                                      SET train_info_id = NULL
                                      WHERE coach_id = ?
                                      AND user_id = ?");

        if (!$coach_stmt) {
            throw new Exception('Coach update SQL error: ' . $conn->error);
        }

        $coach_stmt->bind_param('ii', $coach_id, $owner_user_id);

        if (!$coach_stmt->execute()) {
            throw new Exception('Coach update failed: ' . $coach_stmt->error);
        }

        $coach_stmt->close();
    }

    $conn->commit();

    send_json(200, [
        'success' => true,
        'message' => 'Inspection submitted successfully.',
        'inspection_id' => (int) $inspection_id,
        'warranty_claim_id' => (int) $warranty_claim_id,
        'updated' => [
            'inspection' => true,
            'warranty_claim' => true,
            'coach_inventory' => !empty($inventory_updates),
            'schedule' => true,
            'coach_detached' => $detach_coach === 'yes'
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();

    send_json(500, [
        'success' => false,
        'message' => 'Inspection submission failed.',
        'error' => $e->getMessage()
    ]);
}
