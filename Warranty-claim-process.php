<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function display_value($value) {
    $value = trim((string) $value);
    return $value !== '' ? $value : '-';
}

function format_report_date($value) {
    return $value ? date('d M Y', strtotime($value)) : '-';
}

function format_report_datetime($value) {
    return $value ? date('d M Y, h:i A', strtotime($value)) : '-';
}

function status_class($status) {
    $status = strtolower(trim((string) $status));

    if (in_array($status, ['completed', 'closed', 'resolved'], true)) {
        return 'bg-success';
    }

    if (in_array($status, ['rejected', 'cancelled'], true)) {
        return 'bg-danger';
    }

    if (in_array($status, ['in progress', 'claim process', 'processing'], true)) {
        return 'bg-warning text-dark';
    }

    return 'bg-secondary';
}

$claims = [];
$claim_table_exists = false;
$table_check = $conn->query("SHOW TABLES LIKE 'fdss_warranty_claim'");

if ($table_check && $table_check->num_rows > 0) {
    $claim_table_exists = true;
}

if ($claim_table_exists) {
    $query = "
        SELECT
            wc.warranty_claim_id,
            wc.schedule_id,
            wc.unit_id,
            wc.defectiveCause,
            wc.otherObservation,
            wc.referenceNo,
            wc.suggestion,
            wc.status AS claim_status,
            wc.created_at AS claim_created_at,
            s.status AS schedule_status,
            s.assignment_date_time,
            s.Inspection_Type,
            s.priority,
            s.special_remarks,
            t.train_no,
            t.train_name,
            c.coach_no,
            c.coach_type,
            c.`Type` AS coach_body_type,
            auditor.user_name AS auditor_user_name,
            auditor.full_name AS auditor_full_name,
            iu.serial_number,
            iu.model_number,
            iu.purchase_date,
            iu.Warranty_expire,
            im.item_code,
            im.item_name,
            im.category,
            manufacturer.company_name AS manufacturer_name
        FROM fdss_warranty_claim wc
        LEFT JOIN fdss_coach_schedule s
            ON s.schedule_id = wc.schedule_id
        LEFT JOIN fdss_train_coach c
            ON c.coach_id = s.coach_id
        LEFT JOIN fdss_train_information t
            ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)
        LEFT JOIN fdss_users auditor
            ON auditor.user_id = s.auditor_id
        LEFT JOIN fdds_inventory_unit iu
            ON iu.unit_id = wc.unit_id
        LEFT JOIN fdss_Inventory_Management im
            ON im.inventory_id = iu.inventory_id
        LEFT JOIN fdss_manufacturers manufacturer
            ON manufacturer.manufacturer_id = iu.manufacturer_id
        WHERE COALESCE(s.user_id, iu.user_id) = ?
        ORDER BY wc.created_at DESC, wc.warranty_claim_id DESC
    ";

    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $claims[] = $row;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warranty Claims - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

    <style>
        .claims-table {
            font-size: 0.85rem;
        }

        .claims-table th {
            background: #f8f9fa;
            vertical-align: middle;
            white-space: nowrap;
        }

        .claims-table td {
            vertical-align: middle;
        }

        @media print {
            .navbar,
            .sidebar,
            .page-header,
            .no-print,
            footer {
                display: none !important;
            }

            .sidebar-container,
            .main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .content-card {
                box-shadow: none !important;
                border: none !important;
            }

            .claims-table {
                font-size: 9px !important;
            }

            .claims-table th,
            .claims-table td {
                border: 1px solid #000 !important;
                color: #000 !important;
            }
        }
    </style>
</head>
<body>
<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Warranty Claims</h1>
                <p class="page-header-subtitle">
                    <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                    <span class="text-muted"> / </span>Warranty Claim Process
                </p>
            </div>
            <div class="no-print">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <div class="content-card">
            <div class="card-body d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <strong>Total Warranty Claims:</strong>
                    <span class="badge bg-primary ms-1"><?php echo count($claims); ?></span>
                </div>
                <div class="text-muted">
                    Schedule-wise warranty claim details
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Warranty Claim List</h5>
            </div>
            <div class="card-body">
                <?php if (!$claim_table_exists): ?>
                    <div class="alert alert-warning mb-0">
                        fdss_warranty_claim table was not found.
                    </div>
                <?php elseif (empty($claims)): ?>
                    <div class="alert alert-info mb-0">
                        No warranty claims found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover claims-table">
                            <thead>
                                <tr>
                                    <th>SL.</th>
                                    <th>Claim ID</th>
                                    <th>Schedule</th>
                                    <th>Train</th>
                                    <th>Coach</th>
                                    <th>Component</th>
                                    <th>OEM / Make</th>
                                    <th>Warranty Expire</th>
                                    <th>Status</th>
                                    <th>Claim Date</th>
                                    <th class="no-print">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($claims as $index => $claim): ?>
                                    <?php
                                        $auditor_name = !empty($claim['auditor_full_name'])
                                            ? $claim['auditor_full_name']
                                            : $claim['auditor_user_name'];
                                        $coach_text = trim(display_value($claim['coach_no']) . ' / ' . display_value($claim['coach_body_type']));
                                        $train_text = trim(display_value($claim['train_no']) . ' - ' . display_value($claim['train_name']));
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $index + 1; ?></td>
                                        <td><?php echo e(display_value($claim['warranty_claim_id'])); ?></td>
                                        <td>
                                            <strong>#<?php echo e(display_value($claim['schedule_id'])); ?></strong><br>
                                            <?php echo e(format_report_datetime($claim['assignment_date_time'])); ?><br>
                                            <span class="text-muted"><?php echo e(display_value($claim['Inspection_Type'])); ?></span>
                                        </td>
                                        <td><?php echo e($train_text); ?></td>
                                        <td><?php echo e($coach_text); ?></td>
                                        <td>
                                            <strong><?php echo e(display_value($claim['item_name'])); ?></strong><br>
                                            <span class="text-muted"><?php echo e(display_value($claim['serial_number'])); ?></span>
                                        </td>
                                        <td><?php echo e(display_value($claim['manufacturer_name'])); ?></td>
                                        <td><?php echo e(format_report_date($claim['Warranty_expire'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo e(status_class($claim['claim_status'])); ?>">
                                                <?php echo e(display_value($claim['claim_status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo e(format_report_datetime($claim['claim_created_at'])); ?></td>
                                        <td class="no-print">
                                            <a class="btn btn-sm btn-primary" href="Warranty-claim-view.php?claim_id=<?php echo e($claim['warranty_claim_id']); ?>">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>
</body>
</html>
