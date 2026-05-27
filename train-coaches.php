<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$train_info_id = isset($_GET['train_info_id']) ? (int) $_GET['train_info_id'] : 0;

if ($train_info_id <= 0) {
    die('Invalid train details.');
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$train_query = "SELECT train_info_id, train_no, train_name, status, created_at, updated_at
                FROM fdss_train_information
                WHERE train_info_id = ? AND user_id = ?
                LIMIT 1";
$stmt = $conn->prepare($train_query);
$stmt->bind_param("ii", $train_info_id, $user_id);
$stmt->execute();
$train_result = $stmt->get_result();

if ($train_result->num_rows === 0) {
    die('Train not found.');
}

$train = $train_result->fetch_assoc();
$stmt->close();

$coaches = [];

$coach_query = "SELECT
                    c.coach_id,
                    c.train_info_id,
                    c.coach_no,
                    c.coach_type,
                    c.`Type` AS coach_body_type,
                    c.coach_status,
                    c.status,
                    c.next_inspection_date,
                    c.created_at,
                    COUNT(ci.id) AS total_inventory,
                    SUM(CASE WHEN ci.status = 'Active' THEN 1 ELSE 0 END) AS active_inventory,
                    SUM(CASE WHEN ci.status = 'Expired' THEN 1 ELSE 0 END) AS expired_inventory,
                    SUM(CASE
                        WHEN iu.Warranty_expire IS NOT NULL
                        AND DATE(iu.Warranty_expire) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        THEN 1 ELSE 0
                    END) AS expire_soon
                FROM fdss_train_coach c
                LEFT JOIN fdss_coach_inventory ci
                    ON ci.coach_id = c.coach_id
                    AND ci.user_id = c.user_id
                LEFT JOIN fdds_inventory_unit iu
                    ON iu.unit_id = ci.inventory_unit_id
                WHERE c.user_id = ?
                AND c.train_info_id = ?
                GROUP BY c.coach_id
                ORDER BY c.coach_no ASC";

$stmt = $conn->prepare($coach_query);
$stmt->bind_param("ii", $user_id, $train_info_id);
$stmt->execute();
$coach_result = $stmt->get_result();

while ($row = $coach_result->fetch_assoc()) {
    $coaches[] = $row;
}

$stmt->close();

$total_components = 0;
$active_components = 0;
$expired_components = 0;
$expire_soon_components = 0;

foreach ($coaches as $coach) {
    $total_components += (int) ($coach['total_inventory'] ?? 0);
    $active_components += (int) ($coach['active_inventory'] ?? 0);
    $expired_components += (int) ($coach['expired_inventory'] ?? 0);
    $expire_soon_components += (int) ($coach['expire_soon'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Train Coach Details - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Train Coach Details</h1>
                <p class="page-header-subtitle">
                    <?php echo e($train['train_no']); ?> - <?php echo e($train['train_name']); ?>
                </p>
            </div>
            <div class="page-header-actions">
                <a class="btn btn-primary" href="trains.php">
                    <i class="bi bi-arrow-left"></i> Back To Trains
                </a>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5>Train Summary</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Train Number</h6>
                            <p><strong><?php echo e($train['train_no']); ?></strong></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Train Route / Name</h6>
                            <p><strong><?php echo e($train['train_name']); ?></strong></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Total Coaches</h6>
                            <p><strong><?php echo count($coaches); ?></strong></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Status</h6>
                            <p>
                                <span class="badge <?php echo ($train['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo e($train['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5>Component Summary</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Total Components</h6>
                            <p><strong><?php echo e($total_components); ?></strong></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Active Components</h6>
                            <p><span class="badge badge-success"><?php echo e($active_components); ?></span></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Expired Components</h6>
                            <p><span class="badge badge-danger"><?php echo e($expired_components); ?></span></p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="quick-box">
                            <h6>Expire Soon</h6>
                            <p><span class="badge badge-warning"><?php echo e($expire_soon_components); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-box-seam"></i>
                    Coaches In This Train (<?php echo count($coaches); ?> Total)
                </h5>
            </div>

            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Coach No.</th>
                                <th>Type</th>
                                <th>Coach Type</th>
                                <th>Coach Status</th>
                                <th>FDSS Status</th>
                                <th>Total Components</th>
                                <th>Active</th>
                                <th>Expired</th>
                                <th>Expire Soon</th>
                                <th>Next Inspection</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($coaches)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    No coaches assigned to this train.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coaches as $coach): ?>
                                <tr>
                                    <td>
                                        <a href="coach-inventory.php?train_info_id=<?php echo e($coach['train_info_id']); ?>&coach_no=<?php echo urlencode($coach['coach_no']); ?>">
                                            <?php echo e($coach['coach_no']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo e($coach['coach_body_type'] ?: '-'); ?></td>
                                    <td><?php echo e($coach['coach_type'] ?: '-'); ?></td>
                                    <td><?php echo e($coach['coach_status'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($coach['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo e($coach['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($coach['total_inventory'] ?? 0); ?></td>
                                    <td><span class="badge badge-success"><?php echo e($coach['active_inventory'] ?? 0); ?></span></td>
                                    <td><span class="badge badge-danger"><?php echo e($coach['expired_inventory'] ?? 0); ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo e($coach['expire_soon'] ?? 0); ?></span></td>
                                    <td>
                                        <?php echo $coach['next_inspection_date'] ? e(date('Y-m-d', strtotime($coach['next_inspection_date']))) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php echo $coach['created_at'] ? e(date('Y-m-d', strtotime($coach['created_at']))) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>

</body>
</html>

<?php
$conn->close();
?>
