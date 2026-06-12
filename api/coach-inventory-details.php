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
$coach_inventory_id = (int) ($input['coach_inventory_id'] ?? 0);
$unit_id = (int) ($input['unit_id'] ?? ($input['inventory_unit_id'] ?? 0));

if ($auditor_id <= 0 || $schedule_id <= 0 || ($coach_inventory_id <= 0 && $unit_id <= 0)) {
    send_json(422, [
        'success' => false,
        'message' => 'auditor_id, schedule_id and coach_inventory_id or unit_id are required.'
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

$inventory_query = "SELECT
                        ci.id AS coach_inventory_id,
                        ci.status AS assignment_status,
                        ci.warranty_replace_status,
                        ci.created_at AS assigned_at,
                        ci.updated_at AS assignment_updated_at,
                        iu.inventory_parameter_id,
                        iu.unit_id,
                        iu.inventory_id,
                        iu.serial_number,
                        iu.model_number,
                        iu.purchase_date,
                        iu.Warranty_expire AS warranty_expire,
                        iu.manufacturer_id,
                        iu.notes,
                        iu.Token_id,
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
                     AND (
                        ci.id = ?
                        OR iu.unit_id = ?
                     )
                     LIMIT 1";

$inventory_stmt = $conn->prepare($inventory_query);

if (!$inventory_stmt) {
    send_json(500, [
        'success' => false,
        'message' => 'Inventory detail SQL error.'
    ]);
}

$coach_id = (int) $schedule['coach_id'];
$owner_user_id = (int) $schedule['user_id'];

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
        'message' => 'Inventory item not found for this coach.'
    ]);
}

$last_inspection = null;
$last_inspection_query = "SELECT
                            id AS inspection_id,
                            Conditions,
                            working_status,
                            status AS inspection_status,
                            remarks,
                            defective_cause,
                            detach_coach,
                            in_warranty,
                            part_replaced,
                            reference_no,
                            suggestion,
                            other_observation,
                            created_at AS inspected_at
                          FROM fdds_coach_inspection
                          WHERE coach_id = ?
                          AND inventory_id = ?
                          ORDER BY created_at DESC
                          LIMIT 1";

$last_inspection_stmt = $conn->prepare($last_inspection_query);
if ($last_inspection_stmt) {
    $last_inspection_stmt->bind_param('ii', $coach_id, $inventory['inventory_id']);
    $last_inspection_stmt->execute();
    $last_inspection = $last_inspection_stmt->get_result()->fetch_assoc();
    $last_inspection_stmt->close();
}

$related_unit = null;
$related_unit_query = "SELECT
                            ci.id AS coach_inventory_id,
                            ci.status AS assignment_status,
                            ci.warranty_replace_status,
                            ci.created_at AS assigned_at,
                            iu.unit_id,
                            iu.inventory_id,
                            iu.serial_number,
                            iu.model_number,
                            iu.purchase_date,
                            iu.Warranty_expire AS warranty_expire,
                            iu.manufacturer_id,
                            iu.notes,
                            iu.Token_id,
                            iu.inventory_parameter_id,
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
                         AND iu.inventory_id = ?
                         AND iu.unit_id != ?
                         AND LOWER(im.status) != 'inactive'
                         ORDER BY ci.created_at DESC
                         LIMIT 1";

$related_unit_stmt = $conn->prepare($related_unit_query);
if ($related_unit_stmt) {
    $current_unit_id = (int) $inventory['unit_id'];
    $current_inventory_id = (int) $inventory['inventory_id'];
    $related_unit_stmt->bind_param('iiii', $coach_id, $owner_user_id, $current_inventory_id, $current_unit_id);
    $related_unit_stmt->execute();
    $related_unit = $related_unit_stmt->get_result()->fetch_assoc();
    $related_unit_stmt->close();
}

send_json(200, [
    'success' => true,
    'message' => 'Coach inventory details fetched successfully.',
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
    'inventory' => [
        'coach_inventory_id' => (int) $inventory['coach_inventory_id'],
        'assignment_status' => $inventory['assignment_status'],
        'warranty_replace_status' => $inventory['warranty_replace_status'],
        'assigned_at' => $inventory['assigned_at'],
        'assignment_updated_at' => $inventory['assignment_updated_at'],
        'unit' => [
            'unit_id' => (int) $inventory['unit_id'],
            'inventory_id' => (int) $inventory['inventory_id'],
            'serial_number' => $inventory['serial_number'],
            'model_number' => $inventory['model_number'],
            'purchase_date' => $inventory['purchase_date'],
            'warranty_expire' => $inventory['warranty_expire'],
            'manufacturer_id' => $inventory['manufacturer_id'] !== null ? (int) $inventory['manufacturer_id'] : null,
            'notes' => $inventory['notes'],
            'inventory_parameter_id' => $inventory['inventory_parameter_id'] !== null ? (int) $inventory['inventory_parameter_id'] : null,
            'unit_type' => (empty($inventory['Token_id']) ? 'temporary' : 'primary')
        ],
        'component' => [
            'item_code' => $inventory['item_code'],
            'item_name' => $inventory['item_name'],
            'category' => $inventory['category'],
            'status' => $inventory['inventory_status'],
            'remarks' => $inventory['inventory_remarks']
        ],
        'inactive_info' => (strtolower($inventory['inventory_status']) === 'inactive')
            ? ['status' => $inventory['inventory_status']]
            : null,
        'last_inspection' => $last_inspection ? [
            'inspection_id'     => (int) $last_inspection['inspection_id'],
            'conditions'        => $last_inspection['Conditions'],
            'working_status'    => $last_inspection['working_status'],
            'status'            => $last_inspection['inspection_status'],
            'remarks'           => $last_inspection['remarks'],
            'defective_cause'   => $last_inspection['defective_cause'],
            'detach_coach'      => $last_inspection['detach_coach'],
            'in_warranty'       => $last_inspection['in_warranty'],
            'part_replaced'     => $last_inspection['part_replaced'],
            'reference_no'      => $last_inspection['reference_no'],
            'suggestion'        => $last_inspection['suggestion'],
            'other_observation' => $last_inspection['other_observation'],
            'inspected_at'      => $last_inspection['inspected_at']
        ] : null,
        'related_unit' => $related_unit ? [
            'coach_inventory_id'   => (int) $related_unit['coach_inventory_id'],
            'assignment_status'    => $related_unit['assignment_status'],
            'warranty_replace_status' => $related_unit['warranty_replace_status'],
            'assigned_at'          => $related_unit['assigned_at'],
            'unit' => [
                'unit_id'              => (int) $related_unit['unit_id'],
                'inventory_id'         => (int) $related_unit['inventory_id'],
                'serial_number'        => $related_unit['serial_number'],
                'model_number'         => $related_unit['model_number'],
                'purchase_date'        => $related_unit['purchase_date'],
                'warranty_expire'      => $related_unit['warranty_expire'],
                'manufacturer_id'      => $related_unit['manufacturer_id'] !== null ? (int) $related_unit['manufacturer_id'] : null,
                'notes'                => $related_unit['notes'],
                'inventory_parameter_id' => $related_unit['inventory_parameter_id'] !== null ? (int) $related_unit['inventory_parameter_id'] : null,
                'token_id'             => $related_unit['Token_id'],
                'unit_type'            => (empty($related_unit['Token_id']) ? 'temporary' : 'primary')
            ],
            'component' => [
                'item_code' => $related_unit['item_code'],
                'item_name' => $related_unit['item_name'],
                'category'  => $related_unit['category'],
                'status'    => $related_unit['inventory_status'],
                'remarks'   => $related_unit['inventory_remarks']
            ],
            'manufacturer' => [
                'company_name'  => $related_unit['manufacturer_company'],
                'name'          => $related_unit['manufacturer_name'],
                'mobile_number' => $related_unit['manufacturer_mobile'],
                'email'         => $related_unit['manufacturer_email']
            ]
        ] : null,
        'manufacturer' => [
            'company_name' => $inventory['manufacturer_company'],
            'name' => $inventory['manufacturer_name'],
            'mobile_number' => $inventory['manufacturer_mobile'],
            'email' => $inventory['manufacturer_email']
        ]
    ]
]);
