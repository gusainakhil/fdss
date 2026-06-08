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

$inventory_id    = isset($input['inventory_id'])    ? (int)$input['inventory_id']    : 0;
$manufacturer_id = isset($input['manufacturer_id']) ? (int)$input['manufacturer_id'] : 0;
$category        = strtoupper(trim($input['category'] ?? ''));

if ($inventory_id <= 0) {
    send_json(422, ['success' => false, 'message' => 'inventory_id is required and must be a positive integer.']);
}
if ($category !== '' && !in_array($category, ['FDSS', 'FSDS'], true)) {
    send_json(422, ['success' => false, 'message' => 'category must be FDSS or FSDS.']);
}

// Check which optional columns exist
$has_use_status  = (bool)$conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'use_status'")->num_rows;
$has_unit_status = (bool)$conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'unit_status'")->num_rows;
$has_token_id    = (bool)$conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'token_id'")->num_rows;

/*
|--------------------------------------------------------------------------
| Count query — inventory item + filtered unit count
|--------------------------------------------------------------------------
*/
$cq  = "SELECT im.inventory_id, im.item_name, COUNT(iu.unit_id) AS unit_count
        FROM fdss_Inventory_Management im
        LEFT JOIN fdds_inventory_unit iu
          ON iu.inventory_id = im.inventory_id";

// JOIN-level filters (applied per unit row in the LEFT JOIN)
$join_conds = [];
if ($manufacturer_id > 0)  $join_conds[] = "iu.manufacturer_id = ?";
if ($has_token_id)          $join_conds[] = "(iu.token_id IS NULL OR iu.token_id = '')";
if ($has_use_status)        $join_conds[] = "iu.use_status = 0";
if ($has_unit_status)       $join_conds[] = "iu.unit_status = 'Working'";

if (!empty($join_conds)) {
    $cq .= " AND " . implode(" AND ", $join_conds);
}

$cq .= " WHERE im.inventory_id = ?";
if ($category !== '') $cq .= " AND UPPER(TRIM(im.category)) = ?";
$cq .= " GROUP BY im.inventory_id, im.item_name LIMIT 1";

$cstmt = $conn->prepare($cq);
if (!$cstmt) {
    send_json(500, ['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
}

// Build bind params: manufacturer_id (if any) comes first (JOIN), then inventory_id, then category
$c_types  = '';
$c_params = [];
if ($manufacturer_id > 0) { $c_types .= 'i'; $c_params[] = $manufacturer_id; }
$c_types .= 'i'; $c_params[] = $inventory_id;
if ($category !== '')     { $c_types .= 's'; $c_params[] = $category; }

$cstmt->bind_param($c_types, ...$c_params);
$cstmt->execute();
$item = $cstmt->get_result()->fetch_assoc();
$cstmt->close();

if (!$item) {
    send_json(404, ['success' => false, 'message' => 'Inventory item not found.']);
}

/*
|--------------------------------------------------------------------------
| Units query — individual unit rows with all filters
|--------------------------------------------------------------------------
*/
$uq  = "SELECT iu.unit_id, iu.serial_number, iu.model_number,
               iu.purchase_date, iu.Warranty_expire AS warranty_expire,
               iu.manufacturer_id, iu.notes";
if ($has_unit_status) $uq .= ", iu.unit_status";
if ($has_use_status)  $uq .= ", iu.use_status";

$uq .= " FROM fdds_inventory_unit iu
         INNER JOIN fdss_Inventory_Management im
           ON im.inventory_id = iu.inventory_id
         WHERE iu.inventory_id = ?";

if ($manufacturer_id > 0)  $uq .= " AND iu.manufacturer_id = ?";
if ($has_token_id)          $uq .= " AND (iu.token_id IS NULL OR iu.token_id = '')";
if ($has_use_status)        $uq .= " AND iu.use_status = 0";
if ($has_unit_status)       $uq .= " AND iu.unit_status = 'Working'";
if ($category !== '')       $uq .= " AND UPPER(TRIM(im.category)) = ?";

$uq .= " ORDER BY iu.unit_id ASC";

$ustmt = $conn->prepare($uq);
if (!$ustmt) {
    send_json(500, ['success' => false, 'message' => 'DB prepare error (units): ' . $conn->error]);
}

$u_types  = 'i';
$u_params = [$inventory_id];
if ($manufacturer_id > 0) { $u_types .= 'i'; $u_params[] = $manufacturer_id; }
if ($category !== '')     { $u_types .= 's'; $u_params[] = $category; }

$ustmt->bind_param($u_types, ...$u_params);
$ustmt->execute();
$unit_result = $ustmt->get_result();

$units = [];
while ($row = $unit_result->fetch_assoc()) {
    $units[] = [
        'unit_id'         => (int)$row['unit_id'],
        'serial_number'   => $row['serial_number'],
        'model_number'    => $row['model_number'],
        'purchase_date'   => $row['purchase_date'],
        'warranty_expire' => $row['warranty_expire'],
        'manufacturer_id' => $row['manufacturer_id'] !== null ? (int)$row['manufacturer_id'] : null,
        'notes'           => $row['notes'],
        'unit_status'     => $has_unit_status ? $row['unit_status'] : null,
        'use_status'      => $has_use_status  ? (int)$row['use_status'] : null,
    ];
}
$ustmt->close();

send_json(200, [
    'success'   => true,
    'message'   => 'Inventory details fetched successfully.',
    'inventory' => [
        'inventory_id'    => (int)$item['inventory_id'],
        'item_name'       => $item['item_name'],
        'category'        => $category ?: null,
        'manufacturer_id' => $manufacturer_id > 0 ? $manufacturer_id : null,
        'unit_count'      => (int)$item['unit_count'],
        'units'           => $units,
    ],
]);
