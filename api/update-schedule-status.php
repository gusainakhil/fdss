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

    return trim((string) $value);
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

$schedule_id = (int) ($input['schedule_id'] ?? 0);
$status = clean_text($input['status'] ?? null);
$special_remarks = clean_text($input['special_remarks'] ?? ($input['specialRemarks'] ?? null));

if ($schedule_id <= 0 || $status === null || $status === '') {
    send_json(422, [
        'success' => false,
        'message' => 'schedule_id and status are required.'
    ]);
}

$schedule_stmt = $conn->prepare("SELECT schedule_id, coach_id, status, special_remarks
                                 FROM fdss_coach_schedule
                                 WHERE schedule_id = ?
                                 LIMIT 1");

if (!$schedule_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Schedule SQL error.'
    ]);
}

$schedule_stmt->bind_param('i', $schedule_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();
$schedule_stmt->close();

if (!$schedule) {
    send_json(404, [
        'success' => false,
        'message' => 'Schedule not found.'
    ]);
}

$coach_id = (int) $schedule['coach_id'];
$coach_schedule_updated = false;

$conn->begin_transaction();

try {
    $update_stmt = $conn->prepare("UPDATE fdss_coach_schedule
                                   SET status = ?,
                                       special_remarks = ?
                                   WHERE schedule_id = ?");

    if (!$update_stmt) {
        throw new Exception('Schedule update SQL error: ' . $conn->error);
    }

    $update_stmt->bind_param('ssi', $status, $special_remarks, $schedule_id);

    if (!$update_stmt->execute()) {
        throw new Exception('Schedule update failed: ' . $update_stmt->error);
    }

    $update_stmt->close();

    if (strtolower($status) === 'completed') {
        $coach_stmt = $conn->prepare("UPDATE fdss_train_coach
                                      SET schedule_status = 0
                                      WHERE coach_id = ?");

        if (!$coach_stmt) {
            throw new Exception('Coach schedule status SQL error: ' . $conn->error);
        }

        $coach_stmt->bind_param('i', $coach_id);

        if (!$coach_stmt->execute()) {
            throw new Exception('Coach schedule status update failed: ' . $coach_stmt->error);
        }

        $coach_schedule_updated = $coach_stmt->affected_rows >= 0;
        $coach_stmt->close();
    }

    $conn->commit();

    send_json(200, [
        'success' => true,
        'message' => 'Schedule updated successfully.',
        'schedule_id' => $schedule_id,
        'updated' => [
            'schedule' => true,
            'coach_schedule_status' => $coach_schedule_updated
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();

    send_json(500, [
        'success' => false,
        'message' => 'Schedule update failed.',
        'error' => $e->getMessage()
    ]);
}
