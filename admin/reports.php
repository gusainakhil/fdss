<?php
require_once __DIR__ . '/_auth.php';

$active_page = 'reports';

$report_cards = [
    [
        'title' => 'Users by Role',
        'icon' => 'bi-people',
        'rows' => []
    ],
    [
        'title' => 'Schedules by Status',
        'icon' => 'bi-calendar-check',
        'rows' => []
    ],
    [
        'title' => 'Coaches by Type',
        'icon' => 'bi-box-seam',
        'rows' => []
    ],
    [
        'title' => 'Inspections by Condition',
        'icon' => 'bi-clipboard2-check',
        'rows' => []
    ],
];

$queries = [
    0 => "SELECT role AS label, COUNT(*) AS total FROM fdss_users GROUP BY role ORDER BY total DESC",
    1 => "SELECT status AS label, COUNT(*) AS total FROM fdss_coach_schedule GROUP BY status ORDER BY total DESC",
    2 => "SELECT COALESCE(coach_type, 'Unknown') AS label, COUNT(*) AS total FROM fdss_train_coach GROUP BY coach_type ORDER BY total DESC",
    3 => "SELECT COALESCE(Conditions, 'Unknown') AS label, COUNT(*) AS total FROM fdds_coach_inspection GROUP BY Conditions ORDER BY total DESC",
];

foreach ($queries as $index => $sql) {
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report_cards[$index]['rows'][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
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
                <h1>System Reports</h1>
                <p class="page-header-subtitle">Quick administrative summaries from live data</p>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($report_cards as $card): ?>
                <div class="col-lg-6">
                    <div class="content-card h-100">
                        <div class="card-header">
                            <h5><i class="bi <?php echo e($card['icon']); ?>"></i> <?php echo e($card['title']); ?></h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr><th>Name</th><th class="text-end">Total</th></tr>
                                </thead>
                                <tbody>
                                <?php if (empty($card['rows'])): ?>
                                    <tr><td colspan="2" class="text-center text-muted py-4">No data found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($card['rows'] as $row): ?>
                                        <tr>
                                            <td><?php echo e($row['label']); ?></td>
                                            <td class="text-end"><strong><?php echo number_format((int) $row['total']); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
