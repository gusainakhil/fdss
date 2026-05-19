<?php
require_once __DIR__ . '/_auth.php';

$active_page = 'dashboard';

$total_users = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_users");
$active_users = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_users WHERE status = 'Active'");
$org_admins = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_users WHERE role = 'ORG_ADMIN'");
$auditors = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_users WHERE role = 'AUDITOR'");
$active_stations = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_stations WHERE status = 'Active'");
$trains = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_information");
$coaches = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach");
$pending_schedules = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_coach_schedule WHERE status = 'Pending'");
$completed_schedules = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_coach_schedule WHERE status = 'Completed'");
$inspection_rows = admin_count($conn, "SELECT COUNT(*) AS total FROM fdds_coach_inspection");

$recent_users = [];
$stmt = $conn->prepare("SELECT user_id, username, user_name, email, role, status, created_at FROM fdss_users ORDER BY created_at DESC LIMIT 6");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_users[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FDSS</title>
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
                <h1>Admin Dashboard</h1>
                <p class="page-header-subtitle">System administration, users, and operational overview</p>
            </div>
            <div class="page-header-actions">
                <a href="../index.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Main Dashboard
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <?php
            $stats = [
                ['Total Users', $total_users, 'Active: ' . $active_users, 'bi-people-fill', '#0d6efd'],
                ['Org Admins', $org_admins, 'Auditors: ' . $auditors, 'bi-person-badge-fill', '#198754'],
                ['Stations', $active_stations, 'Active station records', 'bi-building-fill', '#6f42c1'],
                ['Trains', $trains, 'Coaches: ' . $coaches, 'bi-train-front-fill', '#fd7e14'],
                ['Pending Schedules', $pending_schedules, 'Completed: ' . $completed_schedules, 'bi-calendar-check-fill', '#dc3545'],
                ['Inspection Rows', $inspection_rows, 'Submitted component checks', 'bi-clipboard2-check-fill', '#0dcaf0'],
            ];
            ?>

            <?php foreach ($stats as $stat): ?>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="admin-stat">
                        <div class="admin-stat-icon" style="background:<?php echo e($stat[4]); ?>">
                            <i class="bi <?php echo e($stat[3]); ?>"></i>
                        </div>
                        <div>
                            <h6><?php echo e($stat[0]); ?></h6>
                            <h3><?php echo number_format($stat[1]); ?></h3>
                            <p><?php echo e($stat[2]); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-clock-history"></i> Recent Users</h5>
                        <a href="users.php" class="btn btn-sm btn-outline-primary">Manage</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($recent_users)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($user['user_name'] ?: $user['username']); ?></strong><br>
                                                <span class="text-muted small">@<?php echo e($user['username']); ?></span>
                                            </td>
                                            <td><?php echo e($user['email']); ?></td>
                                            <td><span class="badge text-bg-info"><?php echo e($user['role']); ?></span></td>
                                            <td>
                                                <span class="badge <?php echo $user['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                                    <?php echo e($user['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo e(date('d M Y', strtotime($user['created_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="admin-action-card mb-3">
                    <div>
                        <h5><i class="bi bi-person-plus text-primary"></i> User Management</h5>
                        <p>Create organization admins, auditors, and manage account status.</p>
                    </div>
                    <a href="users.php" class="btn btn-primary">Open Users</a>
                </div>

                <div class="admin-action-card mb-3">
                    <div>
                        <h5><i class="bi bi-file-earmark-bar-graph text-success"></i> Admin Reports</h5>
                        <p>View system totals for users, trains, coaches, schedules, and inspections.</p>
                    </div>
                    <a href="reports.php" class="btn btn-outline-success">Open Reports</a>
                </div>

                <div class="admin-action-card">
                    <div>
                        <h5><i class="bi bi-gear text-secondary"></i> System Settings</h5>
                        <p>Review database, PHP, and environment information.</p>
                    </div>
                    <a href="systems.php" class="btn btn-outline-secondary">Open Settings</a>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
