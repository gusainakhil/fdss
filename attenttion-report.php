<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function e($v)        { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function dv($v)       { $v = trim((string) $v); return $v !== '' ? $v : ''; }
function fmt_date($v) { return $v ? date('d.m.Y', strtotime($v)) : ''; }
function fmt_my($v)   { return $v ? date('m/Y', strtotime($v))   : ''; }

// Station name for title
$station_name = '';
$stn_stmt = $conn->prepare("
    SELECT st.station_name
    FROM fdss_users u
    LEFT JOIN fdss_stations st ON st.station_id = u.station_id
    WHERE u.user_id = ? LIMIT 1
");
if ($stn_stmt) {
    $stn_stmt->bind_param('i', $user_id);
    $stn_stmt->execute();
    $stn_row      = $stn_stmt->get_result()->fetch_assoc();
    $station_name = $stn_row['station_name'] ?? '';
    $stn_stmt->close();
}

// Date filter
$today     = date('Y-m-d');
$first_day = date('Y-m-01');
$from_date = trim($_GET['from_date'] ?? $first_day);
$to_date   = trim($_GET['to_date']   ?? $today);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) $from_date = $first_day;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date))   $to_date   = $today;
if ($from_date > $to_date) [$from_date, $to_date] = [$to_date, $from_date];

// ── Main query from fdds_coach_inspection ──
$records = [];

$query = "
    SELECT
        ci.inspection_id,
        ci.schedule_id,
        ci.train_info_id,
        ci.coach_id,
        ci.auditor_id,
        ci.inventory_id,
        ci.tool_name,
        ci.Serial_No          AS insp_serial_no,
        ci.Conditions,
        ci.status             AS insp_status,
        ci.remarks,
        ci.created_at         AS insp_date,
        ci.updated_at,
        t.train_no,
        t.train_name,
        c.coach_no,
        c.coach_type,
        c.`Type`              AS coach_body_type,
        auditor.user_name     AS auditor_user_name,
        auditor.full_name     AS auditor_full_name,
        s.status              AS schedule_status,
        s.Inspection_Type,
        s.special_remarks,
        s.assignment_date_time,
        im.item_code,
        im.item_name,
        im.category,
        iu.serial_number      AS unit_serial,
        iu.model_number,
        iu.purchase_date,
        iu.Warranty_expire,
        mfr.company_name      AS manufacturer_name
    FROM fdds_coach_inspection ci
    LEFT JOIN fdss_train_information t
        ON t.train_info_id = ci.train_info_id
    LEFT JOIN fdss_train_coach c
        ON c.coach_id = ci.coach_id
    LEFT JOIN fdss_users auditor
        ON auditor.user_id = ci.auditor_id
    LEFT JOIN fdss_coach_schedule s
        ON s.schedule_id = ci.schedule_id
    LEFT JOIN fdss_Inventory_Management im
        ON im.inventory_id = ci.inventory_id
    LEFT JOIN fdds_inventory_unit iu
        ON iu.unit_id = ci.unit_id
    LEFT JOIN fdss_manufacturers mfr
        ON mfr.manufacturer_id = iu.manufacturer_id
    WHERE ci.user_id = ?
      AND DATE(ci.created_at) BETWEEN ? AND ?
    ORDER BY ci.created_at DESC , ci.inspection_id ASC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('iss', $user_id, $from_date, $to_date);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
}

// ── Helpers for per-row derived values ──
function warranty_status($rec) {
    $exp = $rec['Warranty_expire'] ?? '';
    if ($exp && strtotime($exp) > time()) return true;
    return false;
}

function condition_label($val) {
    $v = strtolower(trim((string) $val));
    if ($v === 'yes') return ['label' => 'OK / Working',  'cls' => 'cond-ok'];
    if ($v === 'no')  return ['label' => 'Issue / Faulty','cls' => 'cond-issue'];
    return ['label' => $val ?: '—', 'cls' => 'cond-na'];
}

function insp_status_chip($status) {
    $s = strtolower(trim((string) $status));
    if ($s === 'warranty') return ['label' => 'Warranty',   'cls' => 'chip-warranty'];
    if ($s === 'replace')  return ['label' => 'Replace',    'cls' => 'chip-replace'];
    return                        ['label' => 'Normal',     'cls' => 'chip-normal'];
}

// ── Excel / CSV export ──
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = 'Attention_Report_' . str_replace('-', '', $from_date) . '_to_' . str_replace('-', '', $to_date) . '.csv';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM

    fputcsv($out, ['ATTENTION PAID IN FDSS/FSDS @ COACHING DEPOT ' . strtoupper($station_name)]);
    fputcsv($out, ['Period: ' . fmt_date($from_date) . ' to ' . fmt_date($to_date)]);
    fputcsv($out, []);
    fputcsv($out, [
        'SL No.',
        'Inspection Date',
        'Train No.',
        'Train Name',
        'Coach Type',
        'Coach No.',
        'Auditor',
        'Schedule ID',
        'Inspection Type',
        'Tool / Component Name',
        'Inventory Item',
        'Item Category',
        'Make / Manufacturer',
        'Serial No (Inspection)',
        'Serial No (Unit)',
        'Model No',
        'Conditions',
        'Inspection Status',
        'Mfg / Purchase Date',
        'Warranty Expiry',
        'Warranty Status',
        'Remarks',
        'Special Remarks',
        'Schedule Status',
        'Last Updated',
    ]);

    foreach ($records as $idx => $rec) {
        $is_w    = warranty_status($rec);
        $cond    = condition_label($rec['Conditions']);
        $chip    = insp_status_chip($rec['insp_status']);
        $auditor = dv($rec['auditor_full_name']) ?: dv($rec['auditor_user_name']);
        fputcsv($out, [
            $idx + 1,
            fmt_date($rec['insp_date']),
            dv($rec['train_no']),
            dv($rec['train_name']),
            dv($rec['coach_body_type']),
            dv($rec['coach_no']),
            $auditor,
            dv($rec['schedule_id']),
            dv($rec['Inspection_Type']),
            dv($rec['tool_name']),
            dv($rec['item_name']),
            dv($rec['category']),
            dv($rec['manufacturer_name']),
            dv($rec['insp_serial_no']),
            dv($rec['unit_serial']),
            dv($rec['model_number']),
            $cond['label'],
            $chip['label'],
            fmt_date($rec['purchase_date']),
            fmt_date($rec['Warranty_expire']),
            $is_w ? 'Warranty' : 'Out of warranty',
            dv($rec['remarks']),
            dv($rec['special_remarks']),
            dv($rec['schedule_status']),
            fmt_date($rec['updated_at']),
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attention Report - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

    <style>
        .att-table {
            font-size: 0.8rem;
            border-collapse: collapse;
            width: 100%;
        }
        .att-table th {
            background: #f0f0f0;
            border: 1px solid #aaa;
            padding: 7px 8px;
            text-align: center;
            vertical-align: middle;
            font-size: 0.75rem;
            line-height: 1.3;
            white-space: nowrap;
        }
        .att-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            vertical-align: top;
            line-height: 1.5;
        }

        /* Row colour by warranty */
        .row-out-warranty td { color: #c0392b; font-weight: 500; }
        .row-warranty     td { color: #1a1a1a; }

        /* Warranty badge */
        .badge-warranty { background:#d4edda; color:#155724; font-weight:700; padding:2px 8px; border-radius:4px; font-size:0.72rem; display:inline-block; }
        .badge-out      { background:#f8d7da; color:#721c24; font-weight:700; padding:2px 8px; border-radius:4px; font-size:0.72rem; display:inline-block; }

        /* Condition badge */
        .cond-ok    { background:#d4edda; color:#155724; }
        .cond-issue { background:#f8d7da; color:#721c24; }
        .cond-na    { background:#e2e3e5; color:#383d41; }
        .cond-ok, .cond-issue, .cond-na {
            font-size:0.7rem; font-weight:700; padding:1px 7px;
            border-radius:20px; display:inline-block; white-space:nowrap;
        }

        /* Inspection status chip */
        .chip-warranty { background:#cff4fc; color:#055160; }
        .chip-replace  { background:#fff3cd; color:#856404; }
        .chip-normal   { background:#e2e3e5; color:#383d41; }
        .chip-warranty, .chip-replace, .chip-normal {
            font-size:0.68rem; font-weight:700; padding:1px 6px;
            border-radius:20px; display:inline-block; margin-top:2px;
        }

        .lbl { font-size:0.65rem; color:#888; font-weight:700; text-transform:uppercase; letter-spacing:0.3px; }
        .val { font-size:0.78rem; }

        .report-title-bar {
            background:#ffc107; color:#000; font-weight:800;
            font-size:1rem; text-align:center; letter-spacing:1px;
            padding:10px 12px; text-transform:uppercase;
            border:2px solid #e6a800;
        }
        .record-meta {
            font-size:0.8rem; color:#555;
            padding:5px 12px 4px; border-bottom:1px solid #eee;
        }

        /* ── Print ── */
        @media print {
            .no-print, .page-header,
            #navbar-placeholder, #sidebar-placeholder, #footer-placeholder
                { display:none !important; }
            body * { visibility:hidden; }
            #printSection, #printSection * { visibility:visible; }
            #printSection { position:fixed; top:0; left:0; width:100%; }
            @page { size:A3 landscape; margin:7mm; }

            .report-title-bar {
                background:#ffc107 !important;
                -webkit-print-color-adjust:exact; print-color-adjust:exact;
                font-size:10pt !important; padding:5px !important;
            }
            .att-table           { font-size:6pt !important; }
            .att-table th        { padding:3px 4px !important; font-size:5.5pt !important;
                                   background:#e0e0e0 !important;
                                   -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .att-table td        { padding:3px 4px !important; }
            .row-out-warranty td { color:#c0392b !important; }
            .badge-warranty, .badge-out,
            .cond-ok, .cond-issue, .cond-na,
            .chip-warranty, .chip-replace, .chip-normal
                                 { font-size:5pt !important; padding:0 3px !important; }
            .lbl                 { font-size:5pt !important; }
            .val                 { font-size:5.5pt !important; }
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="sidebar-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- PAGE HEADER -->
        <div class="page-header no-print">
            <div>
                <h1>Attention Report</h1>
                <p class="page-header-subtitle">
                    <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                    <span class="text-muted"> / </span>Attention Paid (FDSS Inspection)
                </p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <a href="attenttion-report.php?export=excel&from_date=<?php echo e($from_date); ?>&to_date=<?php echo e($to_date); ?>"
                   class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </a>
                <button class="btn btn-dark" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <!-- FILTER -->
        <div class="content-card no-print mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">From Date</label>
                        <input type="date" class="form-control" name="from_date"
                               value="<?php echo e($from_date); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">To Date</label>
                        <input type="date" class="form-control" name="to_date"
                               value="<?php echo e($to_date); ?>">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="attenttion-report.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- REPORT -->
        <div class="content-card">
            <div id="printSection">

                <div class="report-title-bar">
                    ATTENTION PAID IN FDSS/FSDS @ COACHING DEPOT
                    <?php echo e(strtoupper($station_name ?: 'DEPOT')); ?>
                </div>

                <div class="record-meta no-print d-flex justify-content-between">
                    <span>
                        <i class="bi bi-list-check me-1"></i>
                        <?php echo count($records); ?> record<?php echo count($records) === 1 ? '' : 's'; ?>
                    </span>
                    <span><?php echo e(fmt_date($from_date)); ?> &ndash; <?php echo e(fmt_date($to_date)); ?></span>
                </div>

                <div class="table-responsive">
                    <table class="att-table">
                        <thead>
                            <tr>
                                <th>SL.</th>
                                <th>Date</th>
                                <th>Train No.</th>
                                <th>Type &amp;<br>Coach No.</th>
                                <th>Make</th>
                                <th>Tool / Component &amp;<br>Defects Details</th>
                                <th>Condition</th>
                                <th>Warranty /<br>Out of Warranty</th>
                                <th>Details of Warranty Claimed<br><span style="font-weight:400;font-size:0.65rem;">(Part No &bull; SL No &bull; Model &bull; Mfg/Exp)</span></th>
                                <th>Remarks / Attention Paid</th>
                                <th>Auditor</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    No inspection records found for the selected date range.
                                </td>
                            </tr>

                        <?php else: ?>
                            <?php foreach ($records as $idx => $rec):
                                $is_w    = warranty_status($rec);
                                $cond    = condition_label($rec['Conditions']);
                                $chip    = insp_status_chip($rec['insp_status']);
                                $row_cls = $is_w ? 'row-warranty' : 'row-out-warranty';
                                $auditor = dv($rec['auditor_full_name']) ?: dv($rec['auditor_user_name']);

                                // Serial: prefer unit serial, fall back to inspection serial
                                $serial  = dv($rec['unit_serial']) ?: dv($rec['insp_serial_no']);
                            ?>
                            <tr class="<?php echo e($row_cls); ?>">

                                <!-- SL -->
                                <td class="text-center fw-semibold"><?php echo $idx + 1; ?></td>

                                <!-- Date -->
                                <td class="text-center" style="white-space:nowrap;">
                                    <?php echo e(fmt_date($rec['insp_date'])); ?>
                                </td>

                                <!-- Train No -->
                                <td>
                                    <strong><?php echo e(dv($rec['train_no']) ?: 'Detached'); ?></strong>
                                    <?php if (dv($rec['train_name']) !== ''): ?>
                                        <br><span class="lbl"><?php echo e($rec['train_name']); ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Type & Coach No -->
                                <td style="white-space:nowrap;">
                                    <?php if (dv($rec['coach_body_type']) !== ''): ?>
                                        <span class="lbl">Type</span>
                                        <span class="val"> <?php echo e($rec['coach_body_type']); ?></span><br>
                                    <?php endif; ?>
                                    <span class="lbl">Coach</span>
                                    <span class="val"> <?php echo e(dv($rec['coach_no']) ?: '—'); ?></span>
                                    <?php if (dv($rec['coach_type']) !== ''): ?>
                                        <br><span class="lbl">(<?php echo e($rec['coach_type']); ?>)</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Make -->
                                <td>
                                    <?php echo e(dv($rec['manufacturer_name']) ?: '—'); ?>
                                    <?php if (dv($rec['category']) !== ''): ?>
                                        <br><span class="lbl"><?php echo e($rec['category']); ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Tool / Component & Defect -->
                                <td>
                                    <?php if (dv($rec['tool_name']) !== ''): ?>
                                        <strong class="val"><?php echo e($rec['tool_name']); ?></strong><br>
                                    <?php endif; ?>
                                    <?php if (dv($rec['item_name']) !== '' && $rec['item_name'] !== $rec['tool_name']): ?>
                                        <span class="lbl">Item</span>
                                        <span class="val"><?php echo e($rec['item_name']); ?></span><br>
                                    <?php endif; ?>
                                    <?php if (dv($rec['Inspection_Type']) !== ''): ?>
                                        <span class="lbl">Insp Type</span>
                                        <span class="val"><?php echo e($rec['Inspection_Type']); ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Condition -->
                                <td class="text-center">
                                    <span class="<?php echo e($cond['cls']); ?>">
                                        <?php echo e($cond['label']); ?>
                                    </span>
                                </td>

                                <!-- Warranty / Out of Warranty -->
                                <td class="text-center">
                                    <?php if ($is_w): ?>
                                        <span class="badge-warranty">Warranty</span>
                                    <?php else: ?>
                                        <span class="badge-out">Out of warranty</span>
                                    <?php endif; ?>
                                    <br>
                                    <span class="<?php echo e($chip['cls']); ?>">
                                        <?php echo e($chip['label']); ?>
                                    </span>
                                    <?php if (dv($rec['Warranty_expire']) !== ''): ?>
                                        <br><span class="lbl">Exp: <?php echo e(fmt_date($rec['Warranty_expire'])); ?></span>
                                    <?php endif; ?>
                                </td>

                                <!-- Details of warranty claimed -->
                                <td>
                                    <?php if (dv($rec['item_code']) !== ''): ?>
                                        <span class="lbl">Part No</span>
                                        <span class="val"> <?php echo e($rec['item_code']); ?></span><br>
                                    <?php endif; ?>
                                    <?php if ($serial !== ''): ?>
                                        <span class="lbl">SL No</span>
                                        <span class="val"> <?php echo e($serial); ?></span><br>
                                    <?php endif; ?>
                                    <?php if (dv($rec['insp_serial_no']) !== '' && dv($rec['unit_serial']) !== '' && $rec['insp_serial_no'] !== $rec['unit_serial']): ?>
                                        <span class="lbl">Insp SL</span>
                                        <span class="val"> <?php echo e($rec['insp_serial_no']); ?></span><br>
                                    <?php endif; ?>
                                    <?php if (dv($rec['model_number']) !== ''): ?>
                                        <span class="lbl">Model</span>
                                        <span class="val"> <?php echo e($rec['model_number']); ?></span><br>
                                    <?php endif; ?>
                                    <?php if (dv($rec['purchase_date']) !== ''): ?>
                                        <span class="lbl">Mfg</span>
                                        <span class="val"> <?php echo e(fmt_my($rec['purchase_date'])); ?></span><br>
                                    <?php endif; ?>
                                    <?php if (dv($rec['Warranty_expire']) !== ''): ?>
                                        <span class="lbl">Exp</span>
                                        <span class="val"> <?php echo e(fmt_date($rec['Warranty_expire'])); ?></span>
                                    <?php endif; ?>
                                    <?php
                                    $detail_empty = dv($rec['item_code']) === ''
                                                 && $serial === ''
                                                 && dv($rec['model_number']) === ''
                                                 && dv($rec['purchase_date']) === ''
                                                 && dv($rec['Warranty_expire']) === '';
                                    if ($detail_empty): ?>—<?php endif; ?>
                                </td>

                                <!-- Remarks / Attention Paid -->
                                <td>
                                    <?php if (dv($rec['remarks']) !== ''): ?>
                                        <span class="val"><?php echo e($rec['remarks']); ?></span>
                                    <?php endif; ?>
                                    <?php if (dv($rec['special_remarks']) !== ''): ?>
                                        <div class="mt-1">
                                            <span class="lbl">Schedule Note</span><br>
                                            <span class="val"><?php echo e($rec['special_remarks']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (dv($rec['schedule_status']) !== ''): ?>
                                        <div class="mt-1">
                                            <span class="lbl">Sched Status</span>
                                            <span class="val"> <?php echo e($rec['schedule_status']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (dv($rec['remarks']) === '' && dv($rec['special_remarks']) === ''): ?>—<?php endif; ?>
                                </td>

                                <!-- Auditor -->
                                <td style="white-space:nowrap;">
                                    <?php echo e($auditor ?: '—'); ?>
                                    <?php if (dv($rec['schedule_id']) !== ''): ?>
                                        <br><span class="lbl">Sch #<?php echo e($rec['schedule_id']); ?></span>
                                    <?php endif; ?>
                                    <?php if (dv($rec['assignment_date_time']) !== ''): ?>
                                        <br><span class="lbl"><?php echo e(fmt_date($rec['assignment_date_time'])); ?></span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div><!-- /printSection -->
        </div>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>
</body>
</html>
