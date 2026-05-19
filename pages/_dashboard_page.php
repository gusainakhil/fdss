<?php
session_start();
require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'ORG_ADMIN') {
    header('Location: ../login.php?access=denied&role=' . urlencode($_SESSION['role'] ?? ''));
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$base_path = '../';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_cell($value)
{
    $value = trim((string) $value);
    return $value !== '' ? $value : '-';
}

$page_type = $page_type ?? '';

$pages = [
    'components-used' => [
        'title' => 'Total Components Used',
        'icon' => 'bi-boxes',
        'sql' => "SELECT im.item_name AS `Component`, iu.serial_number AS `Serial No`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, CONCAT(t.train_no, ' - ', t.train_name) AS `Train`, ci.status AS `Status`, ci.created_at AS `Assigned On`
                  FROM fdss_coach_inventory ci
                  INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
                  INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
                  LEFT JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
                  LEFT JOIN fdss_train_information t ON t.train_info_id = c.train_info_id
                  WHERE ci.user_id = ?
                  ORDER BY ci.created_at DESC",
    ],
    'detached-coaches' => [
        'title' => 'Coach Detached',
        'icon' => 'bi-arrows-collapse',
        'sql' => "SELECT coach_no AS `Coach No`, coach_type AS `Coach Type`, status AS `Status`, next_inspection_date AS `Next Inspection`, created_at AS `Created On`
                  FROM fdss_train_coach
                  WHERE user_id = ? AND train_info_id IS NULL
                  ORDER BY coach_no ASC",
    ],
    'intact-coaches' => [
        'title' => 'Coach Intact',
        'icon' => 'bi-link-45deg',
        'sql' => "SELECT c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, CONCAT(t.train_no, ' - ', t.train_name) AS `Train`, c.status AS `Status`, c.next_inspection_date AS `Next Inspection`
                  FROM fdss_train_coach c
                  INNER JOIN fdss_train_information t ON t.train_info_id = c.train_info_id
                  WHERE c.user_id = ?
                  ORDER BY t.train_no ASC, c.coach_no ASC",
    ],
    'oem-makes' => [
        'title' => 'Total OEM Makes',
        'icon' => 'bi-building-gear',
        'sql' => "SELECT COALESCE(m.company_name, CONCAT('Manufacturer #', iu.manufacturer_id)) AS `OEM Make`, im.item_name AS `Component`, iu.serial_number AS `Serial No`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`
                  FROM fdss_coach_inventory ci
                  INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
                  INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
                  LEFT JOIN fdss_manufacturers m ON m.manufacturer_id = iu.manufacturer_id
                  LEFT JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
                  WHERE ci.user_id = ? AND iu.manufacturer_id IS NOT NULL
                  ORDER BY `OEM Make` ASC, im.item_name ASC",
    ],
    'components-ok' => [
        'title' => 'Components OK',
        'icon' => 'bi-emoji-smile',
        'sql' => "SELECT i.tool_name AS `Component`, i.Serial_No AS `Serial No`, i.Conditions AS `Condition`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, CONCAT(t.train_no, ' - ', t.train_name) AS `Train`, i.created_at AS `Inspection Date`
                  FROM fdds_coach_inspection i
                  LEFT JOIN fdss_train_coach c ON c.coach_id = i.coach_id
                  LEFT JOIN fdss_train_information t ON t.train_info_id = i.train_info_id
                  WHERE i.user_id = ? AND LOWER(i.Conditions) IN ('ok', 'good', 'working', 'active')
                  ORDER BY i.created_at DESC",
    ],
    'components-broken' => [
        'title' => 'Components Broken / Defected',
        'icon' => 'bi-tools',
        'sql' => "SELECT i.tool_name AS `Component`, i.Serial_No AS `Serial No`, i.Conditions AS `Condition`, i.remarks AS `Remarks`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, CONCAT(t.train_no, ' - ', t.train_name) AS `Train`, i.created_at AS `Inspection Date`
                  FROM fdds_coach_inspection i
                  LEFT JOIN fdss_train_coach c ON c.coach_id = i.coach_id
                  LEFT JOIN fdss_train_information t ON t.train_info_id = i.train_info_id
                  WHERE i.user_id = ? AND LOWER(i.Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed')
                  ORDER BY i.created_at DESC",
    ],
    'warranty-claim' => [
        'title' => 'Warranty Claim',
        'icon' => 'bi-receipt-cutoff',
        'sql' => "SELECT i.tool_name AS `Component`, i.Serial_No AS `Serial No`, i.Conditions AS `Condition`, iu.Warranty_expire AS `Warranty Expire`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, i.remarks AS `Remarks`
                  FROM fdds_coach_inspection i
                  INNER JOIN fdds_inventory_unit iu ON iu.unit_id = i.unit_id
                  LEFT JOIN fdss_train_coach c ON c.coach_id = i.coach_id
                  WHERE i.user_id = ?
                  AND LOWER(i.Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed')
                  AND iu.Warranty_expire IS NOT NULL
                  AND DATE(iu.Warranty_expire) >= CURDATE()
                  ORDER BY iu.Warranty_expire ASC",
    ],
    'under-warranty' => [
        'title' => 'Under Warranty',
        'icon' => 'bi-shield-check',
        'sql' => "SELECT im.item_name AS `Component`, iu.serial_number AS `Serial No`, iu.Warranty_expire AS `Warranty Expire`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, CONCAT(t.train_no, ' - ', t.train_name) AS `Train`
                  FROM fdss_coach_inventory ci
                  INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
                  INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
                  LEFT JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
                  LEFT JOIN fdss_train_information t ON t.train_info_id = c.train_info_id
                  WHERE ci.user_id = ? AND iu.Warranty_expire IS NOT NULL AND DATE(iu.Warranty_expire) >= CURDATE()
                  ORDER BY iu.Warranty_expire ASC",
    ],
    'out-warranty' => [
        'title' => 'Out Of Warranty',
        'icon' => 'bi-exclamation-triangle',
        'sql' => "SELECT im.item_name AS `Component`, iu.serial_number AS `Serial No`, iu.Warranty_expire AS `Warranty Expire`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, CONCAT(t.train_no, ' - ', t.train_name) AS `Train`
                  FROM fdss_coach_inventory ci
                  INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
                  INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
                  LEFT JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
                  LEFT JOIN fdss_train_information t ON t.train_info_id = c.train_info_id
                  WHERE ci.user_id = ? AND iu.Warranty_expire IS NOT NULL AND DATE(iu.Warranty_expire) < CURDATE()
                  ORDER BY iu.Warranty_expire DESC",
    ],
    'components-count-type' => [
        'title' => 'Components Count Type',
        'icon' => 'bi-check-circle',
        'sql' => "SELECT im.item_name AS `Component`, iu.serial_number AS `Serial No`, c.coach_no AS `Coach No`, c.coach_type AS `Coach Type`, ci.status AS `Status`, ci.created_at AS `Assigned On`
                  FROM fdss_coach_inventory ci
                  INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
                  INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
                  LEFT JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
                  WHERE ci.user_id = ? AND ci.status = 'Active'
                  ORDER BY im.item_name ASC",
    ],
];

if (!isset($pages[$page_type])) {
    http_response_code(404);
    echo 'Dashboard page not found.';
    exit;
}

$page = $pages[$page_type];
$rows = [];
$columns = [];

$stmt = $conn->prepare($page['sql']);

if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (empty($columns)) {
            $columns = array_keys($row);
        }

        $rows[] = $row;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page['title']); ?> - FDSS Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="sidebar-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
</div>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1><?php echo e($page['title']); ?></h1>
            <p class="page-header-subtitle">
                <a href="../index.php" class="text-primary text-decoration-none">Dashboard</a>
                <span class="text-muted"> / </span><?php echo e($page['title']); ?>
            </p>
        </div>
        <div class="page-header-actions">
            <a href="../index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="content-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi <?php echo e($page['icon']); ?>"></i>
                <?php echo e($page['title']); ?>
            </h5>
            <span class="text-muted small"><?php echo count($rows); ?> records</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>S.No.</th>
                            <?php foreach ($columns as $column): ?>
                                <th><?php echo e($column); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="<?php echo max(1, count($columns) + 1); ?>" class="text-center text-muted py-4">
                                    No records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $index => $row): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <?php foreach ($columns as $column): ?>
                                        <td><?php echo e(format_cell($row[$column] ?? '')); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>

<?php $conn->close(); ?>
