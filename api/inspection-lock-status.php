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

$schedule_id  = isset($input['schedule_id'])  ? (int)$input['schedule_id']  : 0;
$inventory_id = isset($input['inventory_id']) ? (int)$input['inventory_id'] : 0;
$coach_id     = isset($input['coach_id'])     ? (int)$input['coach_id']     : 0;

if ($schedule_id <= 0 || $inventory_id <= 0 || $coach_id <= 0) {
    send_json(422, [
        'success' => false,
        'message' => 'schedule_id, inventory_id and coach_id are required.'
    ]);
}

$stmt = $conn->prepare("
    SELECT status
    FROM fdds_coach_inspection
    WHERE schedule_id  = ?
      AND inventory_id = ?
      AND coach_id     = ?
    ORDER BY created_at DESC
    LIMIT 1
");

if (!$stmt) {
    send_json(500, ['success' => false, 'message' => "DB error: {$conn->error}"]);
}

$stmt->bind_param('iii', $schedule_id, $inventory_id, $coach_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_locked = $row && $row['status'] === 'normal';

send_json(200, [
    'success'      => true,
    'status'       => $is_locked ? 'lock' : 'unlock',
    'message'      => $is_locked ? 'Inspection is locked.' : 'Inspection is unlocked.',
    'schedule_id'  => $schedule_id,
    'inventory_id' => $inventory_id,
    'coach_id'     => $coach_id,
    'latest_status' => $row['status'] ?? null,
]);
