<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$claim_id = (int) ($_GET['claim_id'] ?? 0);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function display_value($value) {
    $value = trim((string) $value);
    return $value !== '' ? $value : '-';
}

function format_form_date($value) {
    return $value ? date('d-m-Y', strtotime($value)) : '-';
}

function format_form_time($value) {
    return $value ? date('h:i A', strtotime($value)) : '-';
}

$claim = null;
$claim_table_exists = false;
$table_check = $conn->query("SHOW TABLES LIKE 'fdss_warranty_claim'");

if ($table_check && $table_check->num_rows > 0) {
    $claim_table_exists = true;
}

if ($claim_table_exists && $claim_id > 0) {
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
            s.special_remarks,
            t.train_no,
            t.train_name,
            c.coach_no,
            c.coach_type,
            c.`Type` AS coach_body_type,
            iu.serial_number,
            iu.model_number,
            iu.purchase_date,
            iu.Warranty_expire,
            im.item_code,
            im.item_name,
            im.category,
            manufacturer.company_name AS manufacturer_name,
            st.station_name,
            d.division_name,
            z.zone_name
        FROM fdss_warranty_claim wc
        LEFT JOIN fdss_coach_schedule s
            ON s.schedule_id = wc.schedule_id
        LEFT JOIN fdss_train_coach c
            ON c.coach_id = s.coach_id
        LEFT JOIN fdss_train_information t
            ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)
        LEFT JOIN fdds_inventory_unit iu
            ON iu.unit_id = wc.unit_id
        LEFT JOIN fdss_Inventory_Management im
            ON im.inventory_id = iu.inventory_id
        LEFT JOIN fdss_manufacturers manufacturer
            ON manufacturer.manufacturer_id = iu.manufacturer_id
        LEFT JOIN fdss_users owner_user
            ON owner_user.user_id = COALESCE(s.user_id, iu.user_id)
        LEFT JOIN fdss_stations st
            ON st.station_id = owner_user.station_id
        LEFT JOIN fdss_divisions d
            ON d.division_id = st.division_id
        LEFT JOIN fdss_zones z
            ON z.zone_id = d.zone_id
        WHERE wc.warranty_claim_id = ?
          AND COALESCE(s.user_id, iu.user_id) = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param('ii', $claim_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $claim = $result->fetch_assoc();
        $stmt->close();
    }
}

$place = display_value($claim['station_name'] ?? '');
$reporting_division = display_value(trim((string) ($claim['division_name'] ?? '')) . ' / ' . trim((string) ($claim['station_name'] ?? '')) . ' / ' . trim((string) ($claim['zone_name'] ?? '')));
$coach_type = display_value(trim((string) ($claim['coach_no'] ?? '')) . ' / ' . trim((string) ($claim['coach_body_type'] ?? '')));
$make_serial = display_value(trim((string) ($claim['manufacturer_name'] ?? '')) . ' / ' . trim((string) ($claim['serial_number'] ?? '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warranty Claim Form - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

    <style>
        .claim-form-sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            color: #111;
            padding: 34px 36px;
            border: 1px solid #d8d8d8;
        }

        .claim-form-title {
            text-align: center;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            margin-bottom: 14px;
            text-transform: uppercase;
        }

        .claim-form-topline {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .claim-main-table,
        .office-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        .claim-main-table td,
        .office-table th,
        .office-table td {
            border: 1px solid #333;
            padding: 7px 9px;
            vertical-align: middle;
        }

        .claim-main-table .sl-cell {
            width: 45px;
            text-align: center;
        }

        .claim-main-table .label-cell {
            width: 46%;
        }

        .claim-main-table .value-cell {
            min-height: 34px;
            font-weight: 600;
        }

        .note-block {
            margin-top: 14px;
            font-size: 0.82rem;
        }

        .signature-block {
            margin: 50px 0 24px;
            text-align: right;
            font-weight: 800;
        }

        .office-title {
            font-weight: 800;
            margin: 18px 0 10px;
        }

        .office-table th {
            text-align: left;
            font-weight: 800;
        }

        .office-table td {
            height: 38px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm;
            }

            .navbar,
            .sidebar,
            .page-header,
            .no-print,
            footer {
                display: none !important;
            }

            body,
            .sidebar-container,
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .content-card {
                border: none !important;
                box-shadow: none !important;
            }

            .content-card .card-body {
                padding: 0 !important;
            }

            .claim-form-sheet {
                max-width: none !important;
                border: none !important;
                padding: 0 !important;
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
                <h1>Warranty Claim Form</h1>
                <p class="page-header-subtitle">
                    <a href="Warranty-claim-process.php" class="text-primary text-decoration-none">Warranty Claims</a>
                    <span class="text-muted"> / </span>Claim #<?php echo e($claim_id); ?>
                </p>
            </div>
            <div class="no-print d-flex gap-2">
                <a href="Warranty-claim-process.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <div class="content-card">
            <div class="card-body">
                <?php if (!$claim_table_exists): ?>
                    <div class="alert alert-warning mb-0">fdss_warranty_claim table was not found.</div>
                <?php elseif (!$claim): ?>
                    <div class="alert alert-info mb-0">Warranty claim not found.</div>
                <?php else: ?>
                    <div class="claim-form-sheet">
                        <div class="claim-form-title">Sick Analysis Form For Warranty Claim</div>

                        <div class="claim-form-topline">
                            <div>PLACE:- <?php echo e($place); ?></div>
                            <div>DATE:- <?php echo e(format_form_date($claim['claim_created_at'])); ?></div>
                        </div>

                        <table class="claim-main-table">
                            <tr>
                                <td class="sl-cell">1</td>
                                <td class="label-cell">Reporting Division/Depot and Railways</td>
                                <td class="value-cell"><?php echo e($reporting_division); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell" rowspan="4">2</td>
                                <td class="label-cell">a. Coach no. and Type</td>
                                <td class="value-cell"><?php echo e($coach_type); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">b. Production unit/Shop</td>
                                <td class="value-cell">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">c. POH Date</td>
                                <td class="value-cell">-</td>
                            </tr>
                            <tr>
                                <td class="label-cell">d. Return Date</td>
                                <td class="value-cell"><?php echo e(format_form_date($claim['assignment_date_time'])); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell">3</td>
                                <td class="label-cell">Defective Item</td>
                                <td class="value-cell"><?php echo e(display_value($claim['item_name'])); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell">4</td>
                                <td class="label-cell">Make &amp; SL No. Given by Manufacturer</td>
                                <td class="value-cell"><?php echo e($make_serial); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell">5</td>
                                <td class="label-cell">Date of manufacturing</td>
                                <td class="value-cell"><?php echo e(format_form_date($claim['purchase_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell" rowspan="2">6</td>
                                <td class="label-cell">a. Date of Failure</td>
                                <td class="value-cell"><?php echo e(format_form_date($claim['claim_created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">b. Timings</td>
                                <td class="value-cell"><?php echo e(format_form_time($claim['claim_created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell">7</td>
                                <td class="label-cell">Defective Found/Cause of Failure</td>
                                <td class="value-cell"><?php echo e(display_value($claim['defectiveCause'])); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell">8</td>
                                <td class="label-cell">Other Observations and Remarks</td>
                                <td class="value-cell"><?php echo e(display_value($claim['otherObservation'])); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell">9</td>
                                <td class="label-cell">Suggestion if any</td>
                                <td class="value-cell"><?php echo e(display_value($claim['suggestion'])); ?></td>
                            </tr>
                            <tr>
                                <td class="sl-cell">10</td>
                                <td class="label-cell">Reference No.</td>
                                <td class="value-cell"><?php echo e(display_value($claim['referenceNo'])); ?></td>
                            </tr>
                        </table>

                        <div class="note-block">
                            <strong>Note:</strong> * marked should be filled correctly, without these warranties could not claimable.
                            The photo of defective parts and the name place of the manufacturer should be sent to
                            <!-- <u>ssestoresbyl@gmail.com</u>. -->
                        </div>

                        <div class="signature-block">SSE/C&amp;W/SMVB</div>

                        <div class="office-title">For Office Use Only:-</div>
                        <table class="office-table">
                            <thead>
                                <tr>
                                    <th>SL<br>NO</th>
                                    <th>Warranty claimed<br>on</th>
                                    <th>Complain reference<br>No</th>
                                    <th>Warranty<br>closed On</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td></td>
                                    <td><?php echo e(display_value($claim['referenceNo'])); ?></td>
                                    <td></td>
                                    <td><?php echo e(display_value($claim['claim_status'])); ?></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
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
