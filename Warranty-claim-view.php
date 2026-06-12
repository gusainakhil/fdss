<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id  = (int) $_SESSION['user_id'];
$claim_id = (int) ($_GET['claim_id'] ?? 0);

$message      = '';
$message_type = '';

// Auto-add claim_complete_date column if missing
$col_check = $conn->query("SHOW TABLES LIKE 'fdss_warranty_claim'");
if ($col_check && $col_check->num_rows > 0) {
    if (!$conn->query("SHOW COLUMNS FROM fdss_warranty_claim LIKE 'claim_complete_date'")->num_rows) {
        $conn->query("ALTER TABLE fdss_warranty_claim ADD COLUMN `claim_complete_date` DATE NULL");
    }
    if (!$conn->query("SHOW COLUMNS FROM fdss_warranty_claim LIKE 'complete_remark'")->num_rows) {
        $conn->query("ALTER TABLE fdss_warranty_claim ADD COLUMN `complete_remark` TEXT NULL");
    }
}

// Handle complete with new inventory unit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_with_new_unit') {
    $post_claim_id       = (int) ($_POST['claim_id'] ?? 0);
    $claim_complete_date = trim($_POST['claim_complete_date'] ?? '');
    $complete_remark     = trim($_POST['complete_remark'] ?? '');
    $old_unit_id         = (int) ($_POST['old_unit_id'] ?? 0);
    $inv_id              = (int) ($_POST['inventory_id'] ?? 0);
    $inv_param_id        = (int) ($_POST['inventory_parameter_id'] ?? 0);
    $mfr_id              = (int) ($_POST['manufacturer_id'] ?? 0);
    $category            = trim($_POST['category'] ?? '');
    $token_id            = trim($_POST['token_id'] ?? '');
    $new_serial          = trim($_POST['new_serial_number'] ?? '');
    $new_model           = trim($_POST['new_model_number'] ?? '');
    $new_purchase        = trim($_POST['new_purchase_date'] ?? '');
    $new_warranty        = trim($_POST['new_warranty_expire'] ?? '');
    $new_notes           = trim($_POST['new_notes'] ?? '');

    if ($post_claim_id <= 0 || $inv_id <= 0 || $new_serial === '' || $new_model === '') {
        $message      = 'Serial number and model number are required.';
        $message_type = 'danger';
    } else {
        $conn->begin_transaction();
        try {
            $inv_param_val  = $inv_param_id > 0 ? $inv_param_id : null;
            $mfr_val        = $mfr_id > 0 ? $mfr_id : null;
            $purchase_val   = $new_purchase !== '' ? $new_purchase : null;
            $warranty_val   = $new_warranty !== '' ? $new_warranty : null;
            $notes_val      = $new_notes !== '' ? $new_notes : null;
            $category_val   = $category !== '' ? $category : null;

            $esc_serial   = $conn->real_escape_string($new_serial);
            $esc_model    = $conn->real_escape_string($new_model);
            $sql_param_id = $inv_param_id > 0  ? (int) $inv_param_id : 'NULL';
            $sql_mfr_id   = $mfr_id > 0        ? (int) $mfr_id       : 'NULL';
            $sql_purchase = $purchase_val !== null ? "'" . $conn->real_escape_string($purchase_val) . "'" : 'NULL';
            $sql_warranty = $warranty_val !== null ? "'" . $conn->real_escape_string($warranty_val) . "'" : 'NULL';
            $sql_notes    = $notes_val    !== null ? "'" . $conn->real_escape_string($notes_val)    . "'" : 'NULL';
            $sql_category = $category_val !== null ? "'" . $conn->real_escape_string($category_val) . "'" : 'NULL';
            $sql_token    = $token_id !== '' ? "'" . $conn->real_escape_string($token_id) . "'" : 'NULL';

            $insert_sql = "INSERT INTO fdds_inventory_unit
                (inventory_id, inventory_parameter_id, user_id, serial_number, model_number,
                 purchase_date, Warranty_expire, manufacturer_id, notes, Category,
                 Token_id, unit_status, use_status)
            VALUES ({$inv_id}, {$sql_param_id}, {$user_id}, '{$esc_serial}', '{$esc_model}',
                 {$sql_purchase}, {$sql_warranty}, {$sql_mfr_id}, {$sql_notes}, {$sql_category},
                 {$sql_token}, 'Working', 0)";

            if (!$conn->query($insert_sql)) {
                throw new Exception("Insert failed: {$conn->error}");
            }

            if ($old_unit_id > 0) {
                $upd_unit = $conn->prepare("UPDATE fdds_inventory_unit SET unit_status='Not_working' WHERE unit_id = ? AND user_id = ?");
                if ($upd_unit) {
                    $upd_unit->bind_param('ii', $old_unit_id, $user_id);
                    $upd_unit->execute();
                    $upd_unit->close();
                }
            }

            $date_val   = $claim_complete_date !== '' ? $claim_complete_date : null;
            $remark_val = $complete_remark !== '' ? $complete_remark : null;
            $upd = $conn->prepare("
                UPDATE fdss_warranty_claim
                SET status = 'complete',
                    claim_complete_date = ?,
                    complete_remark = ?
                WHERE warranty_claim_id = ?
                  AND COALESCE(
                      (SELECT user_id FROM fdss_coach_schedule WHERE schedule_id = fdss_warranty_claim.schedule_id LIMIT 1),
                      (SELECT user_id FROM fdds_inventory_unit WHERE unit_id = fdss_warranty_claim.unit_id LIMIT 1)
                  ) = ?
            ");
            if (!$upd) {
                throw new Exception("Update prepare failed: {$conn->error}");
            }
            $upd->bind_param('ssii', $date_val, $remark_val, $post_claim_id, $user_id);
            if (!$upd->execute()) {
                throw new Exception("Update execute failed: {$upd->error}");
            }
            $upd->close();

            $conn->commit();
            $message      = 'New inventory unit added and claim marked as complete.';
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message      = 'Error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

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
            wc.POH_Date,
            wc.Production_unit,
            wc.created_at AS claim_created_at,
            wc.claim_complete_date,
            wc.complete_remark,
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
            iu.inventory_id AS iu_inventory_id,
            iu.manufacturer_id AS iu_mfr_id,
            iu.inventory_parameter_id AS iu_param_id,
            iu.Category AS unit_category,
            iu.notes AS unit_notes,
            iu.Token_id AS unit_token_id,
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
            <div class="no-print d-flex gap-2 align-items-center">
                <?php if ($claim): ?>
                    <span class="badge <?php echo $claim['claim_status'] === 'complete' ? 'bg-success' : 'bg-warning text-dark'; ?> fs-6">
                        <?php echo e(ucfirst($claim['claim_status'] ?? 'claim process')); ?>
                    </span>
                <?php endif; ?>
                <a href="Warranty-claim-process.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <?php if ($claim && $claim['claim_status'] !== 'complete'): ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeModal">
                        <i class="bi bi-check-circle"></i> Mark Complete
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show no-print" role="alert">
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

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
                                <td class="value-cell"><?php echo e($claim['Production_unit']); ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">c. POH Date</td>
                                <td class="value-cell"><?php echo e(format_form_date($claim['POH_Date'])); ?></td>
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
                                    <td><?php echo e(format_form_date($claim['claim_created_at'])); ?></td>
                                    <td><?php echo e(display_value($claim['referenceNo'])); ?></td>
                                    <td><?php echo e(format_form_date($claim['claim_complete_date'])); ?></td>
                                    <td><?php echo e(ucfirst(display_value($claim['claim_status']))); ?> <?php if (!empty($claim['complete_remark'])): ?><br><small><?php echo e($claim['complete_remark']); ?></small><?php endif; ?></td>
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

<?php if ($claim && $claim['claim_status'] !== 'complete'): ?>
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i>Add Replacement Inventory &amp; Complete Claim</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="Warranty-claim-view.php?claim_id=<?php echo e($claim_id); ?>">
                <input type="hidden" name="action"                  value="complete_with_new_unit">
                <input type="hidden" name="claim_id"                value="<?php echo e($claim_id); ?>">
                <input type="hidden" name="old_unit_id"             value="<?php echo e($claim['unit_id']); ?>">
                <input type="hidden" name="inventory_id"            value="<?php echo e($claim['iu_inventory_id']); ?>">
                <input type="hidden" name="inventory_parameter_id"  value="<?php echo e($claim['iu_param_id']); ?>">
                <input type="hidden" name="manufacturer_id"         value="<?php echo e($claim['iu_mfr_id']); ?>">
                <input type="hidden" name="category"                value="<?php echo e($claim['unit_category']); ?>">
                <input type="hidden" name="token_id"               value="<?php echo e($claim['unit_token_id'] ?? ''); ?>">

                <div class="modal-body">

                    <!-- Claim date + remark -->
                    <div class="row g-3 mb-4 pb-3 border-bottom">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Claim Complete Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="claim_complete_date"
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Remark</label>
                            <textarea class="form-control" name="complete_remark" rows="2"
                                      placeholder="Enter any remark..."></textarea>
                        </div>
                    </div>

                    <!-- Two columns -->
                    <div class="row g-3">

                        <!-- LEFT: Old inventory -->
                        <div class="col-md-6">
                            <div class="card border-secondary h-100">
                                <div class="card-header bg-secondary text-white fw-semibold">
                                    <i class="bi bi-archive me-1"></i> Old Inventory (Current)
                                </div>
                                <div class="card-body p-3">
                                    <table class="table table-sm table-bordered mb-0" style="font-size:0.88rem">
                                        <tbody>
                                            <tr>
                                                <th class="text-muted" style="width:42%">Item</th>
                                                <td><?php echo e(display_value($claim['item_name'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Serial No.</th>
                                                <td><?php echo e(display_value($claim['serial_number'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Model No.</th>
                                                <td><?php echo e(display_value($claim['model_number'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Purchase Date</th>
                                                <td><?php echo e(format_form_date($claim['purchase_date'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Warranty Expire</th>
                                                <td><?php echo e(format_form_date($claim['Warranty_expire'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Manufacturer</th>
                                                <td><?php echo e(display_value($claim['manufacturer_name'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Category</th>
                                                <td><?php echo e(display_value($claim['unit_category'])); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Notes</th>
                                                <td><?php echo e(display_value($claim['unit_notes'])); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p class="text-muted mt-2 mb-0" style="font-size:0.78rem">
                                        <i class="bi bi-info-circle"></i>
                                        This unit will be marked as <strong>Replacement</strong> on submit.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: New inventory form -->
                        <div class="col-md-6">
                            <div class="card border-success h-100">
                                <div class="card-header bg-success text-white fw-semibold">
                                    <i class="bi bi-plus-circle me-1"></i> New Replacement Inventory
                                </div>
                                <div class="card-body p-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Serial Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="new_serial_number"
                                               placeholder="Enter new serial number" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Model Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="new_model_number"
                                               placeholder="Enter new model number" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Purchase Date</label>
                                        <input type="date" class="form-control bg-light" name="new_purchase_date"
                                               value="<?php echo e($claim['purchase_date'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Warranty Expiry</label>
                                        <input type="date" class="form-control bg-light" name="new_warranty_expire"
                                               value="<?php echo $claim['Warranty_expire'] ? date('Y-m-d', strtotime($claim['Warranty_expire'])) : ''; ?>" readonly>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-semibold">Notes</label>
                                        <textarea class="form-control" name="new_notes" rows="2"
                                                  placeholder="Optional notes..."><?php echo e($claim['unit_notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Add Inventory &amp; Complete Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>
</body>
</html>
