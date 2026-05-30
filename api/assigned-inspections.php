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

function bind_statement($stmt, $bind_types, &$bind_values)
{
    $bind_references = [];

    foreach ($bind_values as $key => $value) {
        $bind_references[$key] = &$bind_values[$key];
    }

    return $stmt->bind_param($bind_types, ...$bind_references);
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
$status = trim($input['status'] ?? '');
$date = trim($input['date'] ?? '');
$completed_count_only = in_array(
    strtolower(trim((string) ($input['completed_count_only'] ?? $input['count_only'] ?? ''))),
    ['1', 'true', 'yes'],
    true
);

if ($auditor_id <= 0) {
    send_json(422, [
        'success' => false,
        'message' => 'auditor_id is required.'
    ]);
}

if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    send_json(422, [
        'success' => false,
        'message' => 'date must be in YYYY-MM-DD format.'
    ]);
}

$auditor_query = "SELECT user_id, created_by_user_id, user_name, full_name, email, phone, status
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

$completed_count_where = "WHERE s.auditor_id = ? AND s.status = 'Completed'";
$completed_count_types = "i";
$completed_count_values = [$auditor_id];

if ($date !== '') {
    $completed_count_where .= " AND DATE(s.assignment_date_time) = ?";
    $completed_count_types .= "s";
    $completed_count_values[] = $date;
}

$completed_count_query = "SELECT COUNT(*) AS completed_count
                          FROM fdss_coach_schedule s
                          $completed_count_where";

$completed_count_stmt = $conn->prepare($completed_count_query);

if (!$completed_count_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Completed schedules count SQL error.'
    ]);
}

bind_statement($completed_count_stmt, $completed_count_types, $completed_count_values);
$completed_count_stmt->execute();
$completed_count_result = $completed_count_stmt->get_result();
$completed_count_row = $completed_count_result->fetch_assoc();
$completed_count_stmt->close();

$completed_count = (int) ($completed_count_row['completed_count'] ?? 0);

if ($completed_count_only) {
    send_json(200, [
        'success' => true,
        'message' => 'Completed schedules count fetched successfully.',
        'completed_count' => $completed_count
    ]);
}

$where_clause = "WHERE s.auditor_id = ?";
$bind_types = "i";
$bind_values = [$auditor_id];

if ($status !== '') {
    $allowed_statuses = ['Pending', 'Assigned', 'Completed'];

    if (!in_array($status, $allowed_statuses, true)) {
        send_json(422, [
            'success' => false,
            'message' => 'Invalid status value.'
        ]);
    }

    $where_clause .= " AND s.status = ?";
    $bind_types .= "s";
    $bind_values[] = $status;
} else {
    $where_clause .= " AND s.status IN ('Pending', 'Assigned')";
}

if ($date !== '') {
    $where_clause .= " AND DATE(s.assignment_date_time) = ?";
    $bind_types .= "s";
    $bind_values[] = $date;
}

$query = "SELECT
            s.schedule_id,
            s.coach_id,
            s.train_info_id,
            s.last_inspection_date,
            s.next_due_date,
            s.assignment_date_time,
            s.priority,
            s.special_remarks,
            s.status,
            c.coach_no,
            c.coach_type,
            c.user_id,
            t.train_no,
            t.train_name,

            COUNT(i.inspection_id) AS submitted_items
          FROM fdss_coach_schedule s
          LEFT JOIN fdss_train_coach c
            ON c.coach_id = s.coach_id
          LEFT JOIN fdss_train_information t
            ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)
          LEFT JOIN fdds_coach_inspection i
            ON i.schedule_id = s.schedule_id
            AND i.auditor_id = s.auditor_id
          $where_clause
          GROUP BY
            s.schedule_id,
            s.coach_id,
            s.train_info_id,
            s.last_inspection_date,
            s.next_due_date,
            s.assignment_date_time,
            s.priority,
            s.special_remarks,
            s.status,
            c.coach_no,
            c.coach_type,
            t.train_no,
            t.train_name
          ORDER BY s.assignment_date_time ASC, s.schedule_id DESC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Assigned inspections SQL error.'
    ]);
}

bind_statement($stmt, $bind_types, $bind_values);
$stmt->execute();
$result = $stmt->get_result();
$inspections = [];

while ($row = $result->fetch_assoc()) {
    $inspections[] = [
        'schedule_id' => (int) $row['schedule_id'],
        'coach_id' => (int) $row['coach_id'],
        'coach_no' => $row['coach_no'],
        'coach_type' => $row['coach_type'],
        'train_info_id' => $row['train_info_id'] !== null ? (int) $row['train_info_id'] : null,
        'train_no' => $row['train_no'],
        'train_name' => $row['train_name'],
        'last_inspection_date' => $row['last_inspection_date'],
        'next_due_date' => $row['next_due_date'],
        'assignment_date_time' => $row['assignment_date_time'],
        'priority' => $row['priority'],
        'special_remarks' => $row['special_remarks'],
        'status' => $row['status'],
        'submitted_items' => (int) $row['submitted_items']
    ];
}

$stmt->close();

send_json(200, [
    'success' => true,
    'message' => 'Assigned inspections fetched successfully.',
    'auditor' => [
        'AuditorID' => (int) $auditor['user_id'],
        'CreatedByID' => (int) $auditor['created_by_user_id'],
        'UserName' => $auditor['user_name'],
        'FullName' => $auditor['full_name'],
        'Email' => $auditor['email'],
        'Phone' => $auditor['phone']
    ],
    'count' => count($inspections),
    'completed_count' => $completed_count,
    'inspections' => $inspections
]);
