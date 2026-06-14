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

function send_json($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    send_json(405, ['success' => false, 'message' => 'Only GET and POST methods are allowed.']);
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (is_array($d = json_decode(file_get_contents('php://input'), true)) ? $d : $_POST)
    : $_GET;

$inventory_id = isset($input['inventory_id']) ? (int)$input['inventory_id'] : 0;
$coach_id     = isset($input['coach_id'])     ? (int)$input['coach_id']     : 0;
$limit        = isset($input['limit'])        ? min(max((int)$input['limit'], 1), 100) : 10;

if ($inventory_id <= 0 && $coach_id <= 0) {
    send_json(422, [
        'success' => false,
        'message' => 'At least one of inventory_id or coach_id is required.'
    ]);
}

$where_parts = [];
$bind_types  = '';
$bind_params = [];

if ($inventory_id > 0) { $where_parts[] = 'ci.inventory_id = ?'; $bind_types .= 'i'; $bind_params[] = $inventory_id; }
if ($coach_id     > 0) { $where_parts[] = 'ci.coach_id = ?';     $bind_types .= 'i'; $bind_params[] = $coach_id; }

$where_sql = implode(' AND ', $where_parts);

$history_query = "
    SELECT
        ci.inspection_id,
        ci.schedule_id,
        ci.train_info_id,
        ci.coach_id,
        ci.auditor_id,
        ci.user_id,
        ci.inventory_id,
        ci.unit_id,
        ci.inventory_parameter_id,
        ci.tool_name,
        ci.Serial_No        AS serial_no,
        ci.Conditions       AS conditions,
        ci.status,
        ci.remarks,
        ci.created_at       AS inspected_at,
        ci.updated_at,
        c.coach_no,
        c.coach_type,
        u.full_name         AS auditor_name,
        u.user_name         AS auditor_username,
        wc.warranty_claim_id,
        wc.defectiveCause,
        wc.otherObservation,
        wc.referenceNo,
        wc.suggestion,
        wc.status           AS warranty_status,
        wc.POH_Date,
        wc.Production_unit,
        wc.claim_complete_date,
        wc.complete_remark,
        wc.created_at       AS warranty_created_at
    FROM fdds_coach_inspection ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    LEFT JOIN fdss_users u ON u.user_id = ci.auditor_id
    LEFT JOIN fdss_warranty_claim wc
        ON wc.unit_id = ci.unit_id
       AND (wc.schedule_id = ci.schedule_id OR (wc.schedule_id IS NULL AND ci.schedule_id IS NULL))
    WHERE {$where_sql}
    ORDER BY ci.created_at DESC
    LIMIT {$limit}
";

$stmt = $conn->prepare($history_query);

if (!$stmt) {
    send_json(500, ['success' => false, 'message' => "SQL error: {$conn->error}"]);
}

$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = [
        'inspection_id'          => (int)$row['inspection_id'],
        'schedule_id'            => $row['schedule_id']            !== null ? (int)$row['schedule_id']            : null,
        'train_info_id'          => $row['train_info_id']          !== null ? (int)$row['train_info_id']          : null,
        'coach_id'               => (int)$row['coach_id'],
        'coach_no'               => $row['coach_no'],
        'coach_type'             => $row['coach_type'],
        'inventory_id'           => (int)$row['inventory_id'],
        'unit_id'                => (int)$row['unit_id'],
        'inventory_parameter_id' => $row['inventory_parameter_id'] !== null ? (int)$row['inventory_parameter_id'] : null,
        'tool_name'              => $row['tool_name'],
        'serial_no'              => $row['serial_no'],
        'conditions'             => $row['conditions'],
        'status'                 => $row['status'],
        'remarks'                => $row['remarks'],
        'inspected_at'           => $row['inspected_at'],
        'updated_at'             => $row['updated_at'],
        'auditor' => [
            'auditor_id' => $row['auditor_id'] !== null ? (int)$row['auditor_id'] : null,
            'full_name'  => $row['auditor_name'],
            'user_name'  => $row['auditor_username'],
        ],
        'warranty_claim' => $row['warranty_claim_id'] !== null ? [
            'warranty_claim_id'   => (int)$row['warranty_claim_id'],
            'defective_cause'     => $row['defectiveCause'],
            'other_observation'   => $row['otherObservation'],
            'reference_no'        => $row['referenceNo'],
            'suggestion'          => $row['suggestion'],
            'status'              => $row['warranty_status'],
            'poh_date'            => $row['POH_Date'],
            'production_unit'     => $row['Production_unit'],
            'claim_complete_date' => $row['claim_complete_date'],
            'complete_remark'     => $row['complete_remark'],
            'created_at'          => $row['warranty_created_at'],
        ] : null,
    ];
}

$stmt->close();

send_json(200, [
    'success'      => true,
    'message'      => count($history) > 0 ? 'Inspection history fetched successfully.' : 'No inspection history found.',
    'inventory_id' => $inventory_id > 0 ? $inventory_id : null,
    'coach_id'     => $coach_id     > 0 ? $coach_id     : null,
    'total'        => count($history),
    'history'      => $history,
]);
