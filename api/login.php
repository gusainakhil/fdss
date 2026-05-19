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

$username = trim($input['username'] ?? '');
$password = (string) ($input['password'] ?? '');

if ($username === '' || $password === '') {
    send_json(422, [
        'success' => false,
        'message' => 'Username and password are required.'
    ]);
}

$query = "SELECT
            user_id,
            user_name,
            username,
            email,
            full_name,
            phone,
            designation,
            role,
            status,
            station_id,
            password_hash
          FROM fdss_users
          WHERE username = ?
          LIMIT 1";

$stmt = $conn->prepare($query);

if (!$stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Login SQL error.'
    ]);
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
    send_json(401, [
        'success' => false,
        'message' => 'Invalid username or password.'
    ]);
}

if ($user['role'] !== 'AUDITOR') {
    send_json(403, [
        'success' => false,
        'message' => 'Only auditor users can login from this API.'
    ]);
}

if ($user['status'] !== 'Active') {
    send_json(403, [
        'success' => false,
        'message' => 'Your account is inactive.'
    ]);
}

unset($user['password_hash']);

send_json(200, [
    'success' => true,
    'message' => 'Login successful.',
    'user' => $user
]);
