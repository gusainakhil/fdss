<?php
require_once __DIR__ . '/_auth.php';

$active_page = 'systems';
$db_name = defined('DB_NAME') ? DB_NAME : 'fdssbeatleanalyt_Database';
$db_host = defined('DB_HOST') ? DB_HOST : 'Unknown';
$server_version = $conn->server_info;
$php_version = PHP_VERSION;

$table_counts = [];
$tables = ['fdss_users', 'fdss_train_information', 'fdss_train_coach', 'fdss_coach_schedule', 'fdds_coach_inspection', 'fdss_coach_inventory'];

foreach ($tables as $table) {
    $table_counts[$table] = admin_count($conn, "SELECT COUNT(*) AS total FROM `$table`");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>

<div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>System Settings</h1>
                <p class="page-header-subtitle">Environment and database health information</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="bi bi-database"></i> Database Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered mb-0">
                            <tbody>
                            <tr><th>Host</th><td><?php echo e($db_host); ?></td></tr>
                            <tr><th>Database</th><td><?php echo e($db_name); ?></td></tr>
                            <tr><th>Server Version</th><td><?php echo e($server_version); ?></td></tr>
                            <tr><th>PHP Version</th><td><?php echo e($php_version); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="bi bi-table"></i> Core Table Counts</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr><th>Table</th><th class="text-end">Rows</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($table_counts as $table => $count): ?>
                                <tr>
                                    <td><?php echo e($table); ?></td>
                                    <td class="text-end"><strong><?php echo number_format($count); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-info-circle"></i> Operational Notes</h5>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted">
                    Application settings are currently database-driven through the FDSS tables. Use User Management for access control and the main dashboard modules for operational data.
                </p>
            </div>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
