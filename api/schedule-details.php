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

function send_json($status_code, $payload)
{
    http_response_code($status_code);
    echo json_encode($payload);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    send_json(405, [
        'success' => false,
        'message' => 'Only GET and POST methods are allowed.'
    ]);
}

$input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

$auditor_id = (int) ($input['auditor_id'] ?? 0);
$schedule_id = (int) ($input['schedule_id'] ?? 0);

if ($auditor_id <= 0 || $schedule_id <= 0) {
    send_json(422, [
        'success' => false,
        'message' => 'auditor_id and schedule_id are required.'
    ]);
}

$auditor_query = "SELECT user_id, user_name, full_name, email, phone
                  FROM fdss_users
                  WHERE user_id = ?
                  AND role = 'AUDITOR'
                  AND status = 'Active'
                  LIMIT 1";

$auditor_stmt = $conn->prepare($auditor_query);

if (!$auditor_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Auditor SQL error.'
    ]);
}

$auditor_stmt->bind_param('i', $auditor_id);
$auditor_stmt->execute();
$auditor_result = $auditor_stmt->get_result();
$auditor = $auditor_result->fetch_assoc();
$auditor_stmt->close();

if (!$auditor) {
    send_json(403, [
        'success' => false,
        'message' => 'Active auditor not found.'
    ]);
}

$schedule_query = "SELECT
                    s.schedule_id,
                    s.coach_id,
                    s.user_id
                  FROM fdss_coach_schedule s
                  WHERE s.schedule_id = ?
                  AND s.auditor_id = ?
                  LIMIT 1";

$schedule_stmt = $conn->prepare($schedule_query);

if (!$schedule_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Schedule SQL error.'
    ]);
}

$schedule_stmt->bind_param('ii', $schedule_id, $auditor_id);
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
                        iu.unit_id,
                        im.item_name
                     FROM fdss_coach_inventory ci
                     INNER JOIN fdds_inventory_unit iu
                        ON iu.unit_id = ci.inventory_unit_id
                     INNER JOIN fdss_Inventory_Management im
                        ON im.inventory_id = iu.inventory_id
                     WHERE ci.coach_id = ?
                     AND ci.user_id = ?
                     AND ci.status = 'Active'
                    
                     ORDER BY im.item_name ASC, iu.serial_number ASC";

$inventory_stmt = $conn->prepare($inventory_query);

if (!$inventory_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Inventory SQL error.'
    ]);
}

$coach_id = (int) $schedule['coach_id'];
$owner_user_id = (int) $schedule['user_id'];

$inventory_stmt->bind_param('ii', $coach_id, $owner_user_id);
$inventory_stmt->execute();
$inventory_result = $inventory_stmt->get_result();
$inventory = [];

while ($row = $inventory_result->fetch_assoc()) {
    $inventory[] = [
        'coach_inventory_id' => (int) $row['coach_inventory_id'],
        'unit_id' => (int) $row['unit_id'],
        'name' => $row['item_name']
    ];
}

$inventory_stmt->close();

send_json(200, [
    'success' => true,
    'message' => 'Inventory names fetched successfully.',
    'count' => count($inventory),
    'inventory' => $inventory
]);
