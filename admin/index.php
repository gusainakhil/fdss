<?php
require_once __DIR__ . '/_auth.php';

$active_page = 'dashboard';

$total_zones = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_zones");
$total_divisions = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_divisions");
$total_stations = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_stations");
$total_trains = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_information");
$total_org_admins = admin_count($conn, "SELECT COUNT(*) AS total FROM fdss_users WHERE role = 'ORG_ADMIN'");

$upcoming_end_dates = [];
$stmt = $conn->prepare(
    "SELECT
        u.user_id,
        u.username,
        u.user_name,
        u.email,
        u.phone,
        u.status,
        u.end_date,
        DATEDIFF(u.end_date, CURDATE()) AS days_left,
        st.station_name,
        d.division_name,
        z.zone_name
    FROM fdss_users u
    LEFT JOIN fdss_stations st ON st.station_id = u.station_id
    LEFT JOIN fdss_divisions d ON d.division_id = st.division_id
    LEFT JOIN fdss_zones z ON z.zone_id = d.zone_id
    WHERE u.role = 'ORG_ADMIN'
      AND u.end_date IS NOT NULL
      AND u.end_date >= CURDATE()
    ORDER BY u.end_date ASC
    LIMIT 10"
);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $upcoming_end_dates[] = $row;
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
<body class="admin-page-body">
<?php include __DIR__ . '/navbar.php'; ?>

<div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p class="page-header-subtitle">Zones, divisions, stations, trains and ORG admin validity overview</p>
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
                ['Total Zones', $total_zones, 'Railway zone records', 'bi-diagram-3-fill', 'var(--admin-primary)'],
                ['Total Divisions', $total_divisions, 'Division records', 'bi-signpost-split-fill', 'var(--admin-teal)'],
                ['Total Stations', $total_stations, 'Station records', 'bi-buildings-fill', '#6a6fb8'],
                ['Total Trains', $total_trains, 'Train records', 'bi-train-front-fill', 'var(--admin-amber)'],
                ['Total Users', $total_org_admins, 'ORG admin users', 'bi-person-badge-fill', 'var(--admin-red)'],
            ];
            ?>

            <?php foreach ($stats as $stat): ?>
                <div class="col-xl col-md-4 col-sm-6">
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

        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-calendar-event"></i> Upcoming End Dates</h5>
                <a href="users.php" class="btn btn-sm btn-outline-primary">Manage ORG Admins</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Station</th>
                            <th>Status</th>
                            <th>End Date</th>
                            <th class="text-end">Days Left</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($upcoming_end_dates)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No upcoming end-date records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($upcoming_end_dates as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($user['user_name'] ?: $user['username']); ?></strong><br>
                                        <span class="text-muted small">@<?php echo e($user['username']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo e($user['email']); ?><br>
                                        <span class="text-muted small"><?php echo e($user['phone'] ?: '-'); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo e($user['station_name'] ?: '-'); ?></strong><br>
                                        <span class="text-muted small">
                                            <?php echo e(trim(($user['division_name'] ?: '-') . ' / ' . ($user['zone_name'] ?: '-'))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                            <?php echo e($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e(date('d M Y', strtotime($user['end_date']))); ?></td>
                                    <td class="text-end">
                                        <strong><?php echo number_format((int) $user['days_left']); ?></strong>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
