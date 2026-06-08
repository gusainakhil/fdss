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
    send_json(405, ['success' => false, 'message' => 'Only GET and POST methods are allowed.']);
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?: $_POST)
    : $_GET;

$user_id = (int) ($input['user_id'] ?? 0);

if ($user_id <= 0) {
    send_json(422, ['success' => false, 'message' => 'user_id is required.']);
}

$stmt = $conn->prepare("SELECT end_date FROM fdss_users WHERE user_id = ? LIMIT 1");
if (!$stmt) {
    send_json(500, ['success' => false, 'message' => 'Database error.']);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    send_json(404, ['success' => false, 'message' => 'User not found.']);
}

send_json(200, [
    'success'  => true,
    'end_date' => $row['end_date'],
]);
