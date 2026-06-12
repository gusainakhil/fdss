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

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

$token_id     = trim($input['token_id'] ?? '');
$inventory_id = (int) ($input['inventory_id'] ?? 0);

if ($inventory_id <= 0) {
    send_json(422, [
        'success' => false,
        'message' => 'inventory_id is required.'
    ]);
}

$token_filter = $token_id !== '' ? "AND iu.Token_id = '{$conn->real_escape_string($token_id)}'" : '';

$query = "SELECT
            iu.unit_id,
            iu.inventory_id,
            iu.inventory_parameter_id,
            iu.user_id,
            iu.serial_number,
            iu.model_number,
            iu.purchase_date,
            iu.Warranty_expire  AS warranty_expire,
            iu.manufacturer_id,
            iu.use_status,
            iu.notes,
            iu.Token_id,
            iu.unit_status,
            iu.Category,
            iu.created_at,
            im.item_code,
            im.item_name,
            im.category         AS item_category,
            im.status           AS inventory_status,
            m.company_name      AS manufacturer_company,
            m.name              AS manufacturer_name,
            m.mobile_number     AS manufacturer_mobile,
            m.email_id          AS manufacturer_email
          FROM fdds_inventory_unit iu
          INNER JOIN fdss_Inventory_Management im
            ON im.inventory_id = iu.inventory_id
          LEFT JOIN fdss_manufacturers m
            ON m.manufacturer_id = iu.manufacturer_id
          WHERE iu.inventory_id = ?
            AND iu.unit_status = 'Working'
            AND iu.use_status = 0
            {$token_filter}
          LIMIT 1";

$stmt = $conn->prepare($query);

if (!$stmt) {
    send_json(500, ['success' => false, 'message' => "SQL error: {$conn->error}"]);
}

$stmt->bind_param('i', $inventory_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    send_json(404, [
        'success'      => false,
        'message'      => 'No eligible unit found. Unit must be Working and not in use.',
        'token_id'     => $token_id,
        'inventory_id' => $inventory_id
    ]);
}

send_json(200, [
    'success'      => true,
    'message'      => 'Eligible unit found.',
    'token_id'     => $token_id,
    'inventory_id' => $inventory_id,
    'unit' => [
        'unit_id'                => (int) $row['unit_id'],
        'inventory_id'           => (int) $row['inventory_id'],
        'inventory_parameter_id' => $row['inventory_parameter_id'] !== null ? (int) $row['inventory_parameter_id'] : null,
        'user_id'                => (int) $row['user_id'],
        'serial_number'          => $row['serial_number'],
        'model_number'           => $row['model_number'],
        'purchase_date'          => $row['purchase_date'],
        'warranty_expire'        => $row['warranty_expire'],
        'manufacturer_id'        => $row['manufacturer_id'] !== null ? (int) $row['manufacturer_id'] : null,
        'use_status'             => (int) $row['use_status'],
        'notes'                  => $row['notes'],
        'token_id'               => $row['Token_id'],
        'unit_status'            => $row['unit_status'],
        'category'               => $row['Category'],
        'created_at'             => $row['created_at']
    ],
    'component' => [
        'item_code'        => $row['item_code'],
        'item_name'        => $row['item_name'],
        'category'         => $row['item_category'],
        'inventory_status' => $row['inventory_status']
    ],
    'manufacturer' => [
        'company_name'  => $row['manufacturer_company'],
        'name'          => $row['manufacturer_name'],
        'mobile_number' => $row['manufacturer_mobile'],
        'email'         => $row['manufacturer_email']
    ]
]);
