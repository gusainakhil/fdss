<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function e($v)    { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function dv($v)   { $v = trim((string) $v); return $v !== '' ? $v : '—'; }
function fd($v)   { return $v ? date('d-m-Y', strtotime($v)) : '—'; }

// ── Filters ──
$filter_type     = trim($_GET['type']     ?? 'all');   // all | fdss_fsds | additional
$filter_status   = trim($_GET['status']   ?? 'all');   // all | Working | Not_working | Replacement | warranty_claim_process
$filter_warranty = trim($_GET['warranty'] ?? 'all');   // all | active | expired
$filter_category = trim($_GET['category'] ?? 'all');   // all | FDSS | FSDS

$allowed_types     = ['all','fdss_fsds','additional'];
$allowed_statuses  = ['all','Working','Not_working','Replacement','warranty_claim_process'];
$allowed_cats      = ['all','FDSS','FSDS'];
$allowed_warr      = ['all','active','expired'];
if (!in_array($filter_type,     $allowed_types,    true)) $filter_type     = 'all';
if (!in_array($filter_status,   $allowed_statuses, true)) $filter_status   = 'all';
if (!in_array($filter_category, $allowed_cats,     true)) $filter_category = 'all';
if (!in_array($filter_warranty, $allowed_warr,     true)) $filter_warranty = 'all';

// ── Summary counts ──
$summary = ['total'=>0,'Working'=>0,'Not_working'=>0,'Replacement'=>0,'warranty_claim_process'=>0,
            'fdss_fsds'=>0,'additional'=>0,'warranty_active'=>0,'warranty_expired'=>0];

$sum_query = "
    SELECT
        iu.unit_status,
        iu.Token_id,
        iu.Warranty_expire
    FROM fdds_inventory_unit iu
    INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id AND im.user_id = iu.user_id
    WHERE iu.user_id = ?
";
$sum_stmt = $conn->prepare($sum_query);
if ($sum_stmt) {
    $sum_stmt->bind_param('i', $user_id);
    $sum_stmt->execute();
    $sum_res = $sum_stmt->get_result();
    while ($r = $sum_res->fetch_assoc()) {
        $summary['total']++;
        $us = $r['unit_status'] ?? 'Working';
        if (isset($summary[$us])) $summary[$us]++;
        if (!empty($r['Token_id'])) $summary['fdss_fsds']++;
        else                        $summary['additional']++;
        if (!empty($r['Warranty_expire']) && strtotime($r['Warranty_expire']) > time())
             $summary['warranty_active']++;
        else $summary['warranty_expired']++;
    }
    $sum_stmt->close();
}

// ── Main query ──
$where  = "WHERE iu.user_id = ?";
$params = [$user_id];
$types  = 'i';

if ($filter_type === 'fdss_fsds') {
    $where .= " AND iu.Token_id IS NOT NULL AND iu.Token_id != ''";
} elseif ($filter_type === 'additional') {
    $where .= " AND (iu.Token_id IS NULL OR iu.Token_id = '')";
}

if ($filter_status !== 'all') {
    $where  .= " AND iu.unit_status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}

if ($filter_category !== 'all') {
    $where  .= " AND UPPER(TRIM(iu.Category)) = ?";
    $params[] = strtoupper($filter_category);
    $types   .= 's';
}

if ($filter_warranty === 'active') {
    $where .= " AND iu.Warranty_expire IS NOT NULL AND iu.Warranty_expire > NOW()";
} elseif ($filter_warranty === 'expired') {
    $where .= " AND (iu.Warranty_expire IS NULL OR iu.Warranty_expire <= NOW())";
}

$query = "
    SELECT
        iu.unit_id,
        iu.serial_number,
        iu.model_number,
        iu.purchase_date,
        iu.Warranty_expire,
        iu.unit_status,
        iu.use_status,
        iu.Token_id,
        iu.Category       AS unit_category,
        iu.notes,
        iu.created_at,
        im.item_code,
        im.item_name,
        im.category       AS item_category,
        mfr.company_name  AS manufacturer_name,
        c.coach_no,
        c.coach_type,
        c.`Type`          AS coach_body_type,
        t.train_no,
        t.train_name,
        ci.warranty_replace_status
    FROM fdds_inventory_unit iu
    INNER JOIN fdss_Inventory_Management im
        ON im.inventory_id = iu.inventory_id AND im.user_id = iu.user_id
    LEFT JOIN fdss_manufacturers mfr
        ON mfr.manufacturer_id = iu.manufacturer_id
    LEFT JOIN fdss_coach_inventory ci
        ON ci.inventory_unit_id = iu.unit_id AND ci.user_id = iu.user_id
    LEFT JOIN fdss_train_coach c
        ON c.coach_id = ci.coach_id AND c.user_id = ci.user_id
    LEFT JOIN fdss_train_information t
        ON t.train_info_id = c.train_info_id AND t.user_id = c.user_id
    $where
    ORDER BY
        FIELD(iu.unit_status,'Not_working','Replacement','warranty_claim_process','Working'),
        (iu.Token_id IS NULL OR iu.Token_id = '') ASC,
        iu.created_at DESC
";

$items = [];
$stmt  = $conn->prepare($query);
if ($stmt) {
    $refs = [];
    foreach ($params as $k => $_) $refs[$k] = &$params[$k];
    $stmt->bind_param($types, ...$refs);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $items[] = $row;
    $stmt->close();
}

// ── URL helper for filter links ──
function filter_url($overrides = []) {
    $base = ['type'=>$_GET['type']??'all','status'=>$_GET['status']??'all',
             'warranty'=>$_GET['warranty']??'all','category'=>$_GET['category']??'all'];
    return 'All-inventory-unit.php?' . http_build_query([...$base, ...$overrides]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Inventory Units - FDSS Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        /* ── Stat cards ── */
        .stat-card {
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
            text-decoration: none;
            color: inherit;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.12); }
        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .stat-num  { font-size: 1.6rem; font-weight: 800; line-height: 1; }
        .stat-lbl  { font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
                     letter-spacing: .5px; color: #666; margin-top: 2px; }

        .card-total    { background:#f0f4ff; border:1.5px solid #c7d7ff; }
        .card-working  { background:#f0fff4; border:1.5px solid #b2dfcc; }
        .card-warranty { background:#fff8e1; border:1.5px solid #ffe082; }
        .card-notwork  { background:#fff0f0; border:1.5px solid #ffcdd2; }
        .card-replace  { background:#fff3e0; border:1.5px solid #ffcc80; }
        .card-fdss     { background:#fce4ec; border:1.5px solid #f48fb1; }
        .card-additional{ background:#e8f5e9; border:1.5px solid #a5d6a7; }

        .icon-total    { background:#4361ee22; color:#4361ee; }
        .icon-working  { background:#2d6a4f22; color:#2d6a4f; }
        .icon-warranty { background:#e6891522; color:#e68915; }
        .icon-notwork  { background:#d32f2f22; color:#d32f2f; }
        .icon-replace  { background:#e6560022; color:#e65600; }
        .icon-fdss     { background:#c2185b22; color:#c2185b; }
        .icon-additional{ background:#388e3c22; color:#388e3c; }

        /* ── Filter bar ── */
        .filter-pill {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1.5px solid transparent;
            transition: all .15s;
            display: inline-block;
        }
        .filter-pill:hover           { opacity: .85; }
        .filter-pill.active          { border-color: currentColor; }
        .pill-all      { background:#e9ecef; color:#495057; }
        .pill-working  { background:#d4edda; color:#155724; }
        .pill-notwork  { background:#f8d7da; color:#721c24; }
        .pill-replace  { background:#fff3cd; color:#856404; }
        .pill-warranty { background:#cff4fc; color:#055160; }
        .pill-fdss     { background:#fce4ec; color:#880e4f; }
        .pill-fsds     { background:#e3f2fd; color:#0d47a1; }
        .pill-additional{ background:#e8f5e9; color:#1b5e20; }
        .pill-active-w { background:#d4edda; color:#155724; }
        .pill-expired  { background:#f8d7da; color:#721c24; }

        /* ── Table ── */
        .inv-table {
            font-size: 0.76rem;
            border-collapse: collapse;
            width: 100%;
        }
        .inv-table th {
            background: #2c3e50;
            color: #fff;
            border: 1px solid #1a252f;
            padding: 7px 8px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            font-size: 0.72rem;
        }
        .inv-table td {
            border: 1px solid #dee2e6;
            padding: 5px 8px;
            vertical-align: middle;
        }
        .inv-table tbody tr:hover { background: #f8f9ff; }

        /* row accent by status */
        .row-notwork  { border-left: 3px solid #e53935; }
        .row-replace  { border-left: 3px solid #fb8c00; }
        .row-warranty { border-left: 3px solid #039be5; }
        .row-working  { border-left: 3px solid #43a047; }

        /* section header row */
        .section-row td {
            background: #f1f3f5 !important;
            font-weight: 700;
            font-size: 0.73rem;
            color: #343a40;
            letter-spacing: .5px;
            text-transform: uppercase;
            padding: 5px 10px !important;
            border-top: 2px solid #adb5bd !important;
        }

        /* badges */
        .b-fdss      { background:#fce4ec; color:#880e4f; }
        .b-fsds      { background:#e3f2fd; color:#0d47a1; }
        .b-additional{ background:#e8f5e9; color:#1b5e20; }
        .b-working   { background:#d4edda; color:#155724; }
        .b-notwork   { background:#f8d7da; color:#721c24; }
        .b-replace   { background:#fff3cd; color:#856404; }
        .b-warranty  { background:#cff4fc; color:#055160; }
        .b-active    { background:#d4edda; color:#155724; }
        .b-expired   { background:#f8d7da; color:#721c24; }
        .b-inuse     { background:#e2e3e5; color:#383d41; }
        .b-instock   { background:#d1e7dd; color:#0a3622; }
        .inv-badge {
            font-size: 0.68rem; font-weight: 700;
            padding: 2px 7px; border-radius: 20px;
            display: inline-block; white-space: nowrap;
        }

        /* print */
        @media print {
            .no-print, .page-header, nav, aside, footer { display:none !important; }
            body * { visibility: hidden; }
            #printArea, #printArea * { visibility: visible; }
            #printArea { position: fixed; top:0; left:0; width:100%; }
            @page { size: A3 landscape; margin: 8mm; }
            .inv-table { font-size: 5.5pt !important; }
            .inv-table th { padding: 3px 4px !important; font-size: 5pt !important; background:#333 !important; color:#fff !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .inv-table td { padding: 2px 4px !important; }
            .inv-badge { font-size:5pt !important; padding:0 3px !important; }
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
                <h1>All Inventory Units Report </h1>
                <p class="page-header-subtitle">
                    <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                    <span class="text-muted"> / </span>Complete Inventory Report
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success" id="exportExcelBtn">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </button>
                <button class="btn btn-dark" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <!-- ── STAT CARDS ── -->
        <div class="row g-3 mb-3 no-print">

            <div class="col-6 col-md-3 col-xl">
                <a href="<?php echo e(filter_url(['status'=>'all','type'=>'all'])); ?>" class="stat-card card-total d-block">
                    <div class="stat-icon icon-total"><i class="bi bi-boxes"></i></div>
                    <div>
                        <div class="stat-num"><?php echo $summary['total']; ?></div>
                        <div class="stat-lbl">Total Units</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl">
                <a href="<?php echo e(filter_url(['status'=>'Working'])); ?>" class="stat-card card-working d-block">
                    <div class="stat-icon icon-working"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-num"><?php echo $summary['Working']; ?></div>
                        <div class="stat-lbl">Working</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl">
                <a href="<?php echo e(filter_url(['status'=>'warranty_claim_process'])); ?>" class="stat-card card-warranty d-block">
                    <div class="stat-icon icon-warranty"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="stat-num"><?php echo $summary['warranty_claim_process']; ?></div>
                        <div class="stat-lbl">Warranty Claim</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl">
                <a href="<?php echo e(filter_url(['status'=>'Not_working'])); ?>" class="stat-card card-notwork d-block">
                    <div class="stat-icon icon-notwork"><i class="bi bi-x-circle-fill"></i></div>
                    <div>
                        <div class="stat-num"><?php echo $summary['Not_working']; ?></div>
                        <div class="stat-lbl">Not Working</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl">
                <a href="<?php echo e(filter_url(['status'=>'Replacement'])); ?>" class="stat-card card-replace d-block">
                    <div class="stat-icon icon-replace"><i class="bi bi-arrow-repeat"></i></div>
                    <div>
                        <div class="stat-num"><?php echo $summary['Replacement']; ?></div>
                        <div class="stat-lbl">Replacement</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl">
                <a href="<?php echo e(filter_url(['type'=>'fdss_fsds'])); ?>" class="stat-card card-fdss d-block">
                    <div class="stat-icon icon-fdss"><i class="bi bi-tag-fill"></i></div>
                    <div>
                        <div class="stat-num"><?php echo $summary['fdss_fsds']; ?></div>
                        <div class="stat-lbl">FDSS / FSDS</div>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3 col-xl">
                <a href="<?php echo e(filter_url(['type'=>'additional'])); ?>" class="stat-card card-additional d-block">
                    <div class="stat-icon icon-additional"><i class="bi bi-plus-circle-fill"></i></div>
                    <div>
                        <div class="stat-num"><?php echo $summary['additional']; ?></div>
                        <div class="stat-lbl">Additional</div>
                    </div>
                </a>
            </div>

        </div>

        <!-- ── FILTER BAR ── -->
        <div class="content-card no-print mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2 align-items-center">

                    <!-- Type -->
                    <span class="text-muted" style="font-size:0.72rem;font-weight:700;">TYPE:</span>
                    <a href="<?php echo e(filter_url(['type'=>'all'])); ?>"
                       class="filter-pill pill-all <?php echo $filter_type==='all'?'active':''; ?>">All</a>
                    <a href="<?php echo e(filter_url(['type'=>'fdss_fsds'])); ?>"
                       class="filter-pill pill-fdss <?php echo $filter_type==='fdss_fsds'?'active':''; ?>">FDSS / FSDS</a>
                    <a href="<?php echo e(filter_url(['type'=>'additional'])); ?>"
                       class="filter-pill pill-additional <?php echo $filter_type==='additional'?'active':''; ?>">Additional</a>

                    <div class="vr mx-1"></div>

                    <!-- Category -->
                    <span class="text-muted" style="font-size:0.72rem;font-weight:700;">CAT:</span>
                    <a href="<?php echo e(filter_url(['category'=>'all'])); ?>"
                       class="filter-pill pill-all <?php echo $filter_category==='all'?'active':''; ?>">All</a>
                    <a href="<?php echo e(filter_url(['category'=>'FDSS'])); ?>"
                       class="filter-pill pill-fdss <?php echo $filter_category==='FDSS'?'active':''; ?>">FDSS</a>
                    <a href="<?php echo e(filter_url(['category'=>'FSDS'])); ?>"
                       class="filter-pill pill-fsds <?php echo $filter_category==='FSDS'?'active':''; ?>">FSDS</a>

                    <div class="vr mx-1"></div>

                    <!-- Status -->
                    <span class="text-muted" style="font-size:0.72rem;font-weight:700;">STATUS:</span>
                    <a href="<?php echo e(filter_url(['status'=>'all'])); ?>"
                       class="filter-pill pill-all <?php echo $filter_status==='all'?'active':''; ?>">All</a>
                    <a href="<?php echo e(filter_url(['status'=>'Working'])); ?>"
                       class="filter-pill pill-working <?php echo $filter_status==='Working'?'active':''; ?>">Working</a>
                    <a href="<?php echo e(filter_url(['status'=>'warranty_claim_process'])); ?>"
                       class="filter-pill pill-warranty <?php echo $filter_status==='warranty_claim_process'?'active':''; ?>">Warranty Claim</a>
                    <a href="<?php echo e(filter_url(['status'=>'Not_working'])); ?>"
                       class="filter-pill pill-notwork <?php echo $filter_status==='Not_working'?'active':''; ?>">Not Working</a>
                    <a href="<?php echo e(filter_url(['status'=>'Replacement'])); ?>"
                       class="filter-pill pill-replace <?php echo $filter_status==='Replacement'?'active':''; ?>">Replacement</a>

                    <div class="vr mx-1"></div>

                    <!-- Warranty -->
                    <span class="text-muted" style="font-size:0.72rem;font-weight:700;">WARRANTY:</span>
                    <a href="<?php echo e(filter_url(['warranty'=>'all'])); ?>"
                       class="filter-pill pill-all <?php echo $filter_warranty==='all'?'active':''; ?>">All</a>
                    <a href="<?php echo e(filter_url(['warranty'=>'active'])); ?>"
                       class="filter-pill pill-active-w <?php echo $filter_warranty==='active'?'active':''; ?>">Active</a>
                    <a href="<?php echo e(filter_url(['warranty'=>'expired'])); ?>"
                       class="filter-pill pill-expired <?php echo $filter_warranty==='expired'?'active':''; ?>">Expired</a>

                    <div class="ms-auto text-muted" style="font-size:0.75rem;">
                        <strong><?php echo count($items); ?></strong> record<?php echo count($items)===1?'':'s'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TABLE ── -->
        <div class="content-card">
            <div id="printArea">

                <!-- print heading -->
                <div class="d-none d-print-block text-center py-2 fw-bold" style="font-size:12pt;border-bottom:2px solid #000;">
                    ALL INVENTORY UNITS — FDSS/FSDS DASHBOARD
                </div>

                <div class="table-responsive">
                    <table class="inv-table" id="invTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Serial No.</th>
                                <th>Model No.</th>
                                <th>OEM / Make</th>
                                <th>Purchase Date</th>
                                <th>Warranty Expire</th>
                                <th>Warranty</th>
                                <th>Unit Status</th>
                                <th>Use Status</th>
                                <th>Train</th>
                                <th>Coach No.</th>
                                <th>Notes</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="17" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No inventory units found for the selected filters.
                                </td>
                            </tr>

                        <?php else:
                            $prev_section = null;
                            foreach ($items as $idx => $item):
                                $has_token   = !empty($item['Token_id']);
                                $section     = $has_token ? 'fdss_fsds' : 'additional';
                                $cat         = strtoupper(trim((string)($item['unit_category'] ?? $item['item_category'] ?? '')));
                                $is_warranty = !empty($item['Warranty_expire']) && strtotime($item['Warranty_expire']) > time();

                                // Section separator
                                if ($section !== $prev_section && $filter_type === 'all'):
                                    $prev_section = $section;
                        ?>
                            <tr class="section-row">
                                <td colspan="17">
                                    <?php if ($section === 'fdss_fsds'): ?>
                                        <i class="bi bi-tag-fill me-1 text-danger"></i> FDSS / FSDS Attached Inventory
                                    <?php else: ?>
                                        <i class="bi bi-plus-circle-fill me-1 text-success"></i> Additional / Temporary Inventory
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif;

                                // Row accent class
                                $us = $item['unit_status'] ?? 'Working';
                                $row_cls = match($us) {
                                    'Not_working'            => 'row-notwork',
                                    'Replacement'            => 'row-replace',
                                    'warranty_claim_process' => 'row-warranty',
                                    default                  => 'row-working',
                                };

                                // Status badge
                                $us_cfg = match($us) {
                                    'Working'                => ['label'=>'Working',       'cls'=>'b-working'],
                                    'Not_working'            => ['label'=>'Not Working',   'cls'=>'b-notwork'],
                                    'Replacement'            => ['label'=>'Replacement',   'cls'=>'b-replace'],
                                    'warranty_claim_process' => ['label'=>'Warranty Claim','cls'=>'b-warranty'],
                                    default                  => ['label'=>$us,             'cls'=>'b-working'],
                                };
                        ?>
                            <tr class="<?php echo e($row_cls); ?>">

                                <td class="text-center fw-semibold"><?php echo $idx + 1; ?></td>

                                <!-- Type badge -->
                                <td class="text-center">
                                    <?php if ($has_token): ?>
                                        <span class="inv-badge b-<?php echo strtolower($cat); ?>"><?php echo e($cat ?: 'FDSS'); ?></span>
                                        <br><span style="font-size:0.6rem;color:#888;" title="<?php echo e($item['Token_id']); ?>">
                                            <?php echo e(substr($item['Token_id'], 0, 10)) . (strlen($item['Token_id'])>10?'…':''); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inv-badge b-additional">Additional</span>
                                    <?php endif; ?>
                                </td>

                                <td><?php echo e(dv($item['item_code'])); ?></td>

                                <td style="max-width:170px;white-space:normal;"><?php echo e(dv($item['item_name'])); ?></td>

                                <td class="text-center">
                                    <span class="inv-badge <?php echo $cat==='FDSS'?'b-fdss':($cat==='FSDS'?'b-fsds':'b-additional'); ?>">
                                        <?php echo e($cat ?: '—'); ?>
                                    </span>
                                </td>

                                <td><?php echo e(dv($item['serial_number'])); ?></td>

                                <td><?php echo e(dv($item['model_number'])); ?></td>

                                <td><?php echo e(dv($item['manufacturer_name'])); ?></td>

                                <td class="text-center" style="white-space:nowrap;"><?php echo e(fd($item['purchase_date'])); ?></td>

                                <td class="text-center" style="white-space:nowrap;">
                                    <?php echo e(fd($item['Warranty_expire'])); ?>
                                </td>

                                <td class="text-center">
                                    <?php if ($is_warranty): ?>
                                        <span class="inv-badge b-active">Active</span>
                                    <?php else: ?>
                                        <span class="inv-badge b-expired">Expired</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <span class="inv-badge <?php echo e($us_cfg['cls']); ?>">
                                        <?php echo e($us_cfg['label']); ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <?php $in_use = (int)($item['use_status']??0) === 1; ?>
                                    <span class="inv-badge <?php echo $in_use?'b-inuse':'b-instock'; ?>">
                                        <?php echo $in_use ? 'In Use' : 'In Stock'; ?>
                                    </span>
                                </td>

                                <td style="white-space:nowrap;">
                                    <?php if (!empty($item['train_no'])): ?>
                                        <strong><?php echo e($item['train_no']); ?></strong>
                                        <?php if (!empty($item['train_name'])): ?>
                                            <br><span style="font-size:0.65rem;color:#666;"><?php echo e($item['train_name']); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <?php echo e(dv($item['coach_no'])); ?>
                                    <?php if (!empty($item['coach_body_type'])): ?>
                                        <br><span style="font-size:0.62rem;color:#888;"><?php echo e($item['coach_body_type']); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td style="max-width:130px;white-space:normal;font-size:0.7rem;color:#555;">
                                    <?php echo e(!empty($item['notes']) ? $item['notes'] : '—'); ?>
                                </td>

                                <td class="text-center" style="white-space:nowrap;font-size:0.68rem;">
                                    <?php echo e(fd($item['created_at'])); ?>
                                </td>

                            </tr>
                        <?php endforeach; endif; ?>

                        </tbody>
                    </table>
                </div>

            </div><!-- /printArea -->
        </div>

    </main>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>
<script>
document.getElementById('exportExcelBtn')?.addEventListener('click', function () {
    const table = document.getElementById('invTable');
    if (!table) return;
    const title = 'All Inventory Units';
    const html  = `<html><head><meta charset="UTF-8"></head><body><h3>${title}</h3>${table.outerHTML}</body></html>`;
    const blob  = new Blob(["﻿" + html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const a     = document.createElement('a');
    a.href      = URL.createObjectURL(blob);
    a.download  = `inventory-units-${new Date().toISOString().slice(0,10)}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
});
</script>
</body>
</html>
