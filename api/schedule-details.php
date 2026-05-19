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
                    s.train_info_id,
                    s.last_inspection_date,
                    s.next_due_date,
                    s.assignment_date_time,
                    s.priority,
                    s.special_remarks,
                    s.status AS schedule_status,
                    s.user_id,
                    c.coach_no,
                    c.coach_type,
                    c.coach_status,
                    c.status AS coach_status_label,
                    c.next_inspection_date,
                    c.schedule_status AS coach_schedule_status,
                    t.train_no,
                    t.train_name
                  FROM fdss_coach_schedule s
                  LEFT JOIN fdss_train_coach c
                    ON c.coach_id = s.coach_id
                  LEFT JOIN fdss_train_information t
                    ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)
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

$components_query = "SELECT
                        ci.id AS coach_inventory_id,
                        ci.status AS assignment_status,
                        ci.created_at AS assigned_at,
                        ci.updated_at AS assignment_updated_at,
                        iu.unit_id,
                        iu.inventory_id,
                        iu.serial_number,
                        iu.model_number,
                        iu.purchase_date,
                        iu.Warranty_expire AS warranty_expire,
                        iu.manufacturer_id,
                        iu.notes,
                        im.item_code,
                        im.item_name,
                        im.category,
                        im.status AS inventory_status,
                        im.remarks AS inventory_remarks,
                        m.company_name AS manufacturer_company,
                        m.name AS manufacturer_name,
                        m.mobile_number AS manufacturer_mobile,
                        m.email_id AS manufacturer_email
                     FROM fdss_coach_inventory ci
                     INNER JOIN fdds_inventory_unit iu
                        ON iu.unit_id = ci.inventory_unit_id
                     INNER JOIN fdss_Inventory_Management im
                        ON im.inventory_id = iu.inventory_id
                     LEFT JOIN fdss_manufacturers m
                        ON m.manufacturer_id = iu.manufacturer_id
                     WHERE ci.coach_id = ?
                     AND ci.user_id = ?
                     ORDER BY im.item_name ASC, iu.serial_number ASC";

$components_stmt = $conn->prepare($components_query);

if (!$components_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Components SQL error.'
    ]);
}

$coach_id = (int) $schedule['coach_id'];
$owner_user_id = (int) $schedule['user_id'];

$components_stmt->bind_param('ii', $coach_id, $owner_user_id);
$components_stmt->execute();
$components_result = $components_stmt->get_result();
$components = [];

while ($row = $components_result->fetch_assoc()) {
    $components[] = [
        'coach_inventory_id' => (int) $row['coach_inventory_id'],
        'assignment_status' => $row['assignment_status'],
        'assigned_at' => $row['assigned_at'],
        'assignment_updated_at' => $row['assignment_updated_at'],
        'unit' => [
            'unit_id' => (int) $row['unit_id'],
            'inventory_id' => (int) $row['inventory_id'],
            'serial_number' => $row['serial_number'],
            'model_number' => $row['model_number'],
            'purchase_date' => $row['purchase_date'],
            'warranty_expire' => $row['warranty_expire'],
            'manufacturer_id' => $row['manufacturer_id'] !== null ? (int) $row['manufacturer_id'] : null,
            'notes' => $row['notes']
        ],
        'component' => [
            'item_code' => $row['item_code'],
            'item_name' => $row['item_name'],
            'category' => $row['category'],
            'status' => $row['inventory_status'],
            'remarks' => $row['inventory_remarks']
        ],
        'manufacturer' => [
            'company_name' => $row['manufacturer_company'],
            'name' => $row['manufacturer_name'],
            'mobile_number' => $row['manufacturer_mobile'],
            'email' => $row['manufacturer_email']
        ]
    ];
}

$components_stmt->close();

send_json(200, [
    'success' => true,
    'message' => 'Schedule details fetched successfully.',
    'auditor' => [
        'user_id' => (int) $auditor['user_id'],
        'user_name' => $auditor['user_name'],
        'full_name' => $auditor['full_name'],
        'email' => $auditor['email'],
        'phone' => $auditor['phone']
    ],
    'schedule' => [
        'schedule_id' => (int) $schedule['schedule_id'],
        'status' => $schedule['schedule_status'],
        'assignment_date_time' => $schedule['assignment_date_time'],
        'last_inspection_date' => $schedule['last_inspection_date'],
        'next_due_date' => $schedule['next_due_date'],
        'priority' => $schedule['priority'],
        'special_remarks' => $schedule['special_remarks']
    ],
    'coach' => [
        'coach_id' => (int) $schedule['coach_id'],
        'coach_no' => $schedule['coach_no'],
        'coach_type' => $schedule['coach_type'],
        'coach_status' => $schedule['coach_status'],
        'status' => $schedule['coach_status_label'],
        'next_inspection_date' => $schedule['next_inspection_date'],
        'schedule_status' => (int) $schedule['coach_schedule_status']
    ],
    'train' => [
        'train_info_id' => $schedule['train_info_id'] !== null ? (int) $schedule['train_info_id'] : null,
        'train_no' => $schedule['train_no'],
        'train_name' => $schedule['train_name']
    ],
    'component_count' => count($components),
    'components' => $components
]);
