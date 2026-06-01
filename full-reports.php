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

function display_report_value($value) {
    $value = trim((string) $value);
    return $value !== '' ? $value : '-';
}

function format_full_report_month($value) {
    return $value ? date('M Y', strtotime($value . '-01')) : '-';
}

function status_cell_class($value) {
    $value = strtoupper(trim((string) $value));

    if (in_array($value, ['OK', 'YES', 'GOOD', 'WORKING', 'ACTIVE'], true)) {
        return 'ok-cell';
    }

    if (in_array($value, ['NA', 'N/A', '-'], true)) {
        return 'na-cell';
    }

    return 'not-ok-cell';
}

function normalize_report_key($value) {
    return preg_replace('/[^a-z0-9]+/', '', strtolower((string) $value));
}

function parameter_header_lines($value, $words_per_line = 4) {
    $words = preg_split('/\s+/', trim((string) $value));

    if (!$words || $words === ['']) {
        return ['-'];
    }

    $lines = [];

    foreach (array_chunk($words, $words_per_line) as $chunk) {
        $lines[] = implode(' ', $chunk);
    }

    return $lines;
}

$current_month = date('Y-m');
$selected_month = trim($_GET['month'] ?? '');

if ($selected_month === '' && !empty($_GET['date'])) {
    $selected_month = date('Y-m', strtotime($_GET['date']));
}

if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
    $selected_month = $current_month;
}

$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

$report_header = [
    'station_name' => '-',
    'division_name' => '-',
    'zone_name' => 'Railway Zone'
];
$header_stmt = $conn->prepare("
    SELECT
        st.station_name,
        d.division_name,
        z.zone_name
    FROM fdss_users u
    LEFT JOIN fdss_stations st ON st.station_id = u.station_id
    LEFT JOIN fdss_divisions d ON d.division_id = st.division_id
    LEFT JOIN fdss_zones z ON z.zone_id = d.zone_id
    WHERE u.user_id = ?
    LIMIT 1
");

if ($header_stmt) {
    $header_stmt->bind_param('i', $user_id);
    $header_stmt->execute();
    $header_result = $header_stmt->get_result();
    $header_row = $header_result->fetch_assoc();

    if ($header_row) {
        $report_header['station_name'] = display_report_value($header_row['station_name'] ?? '');
        $report_header['division_name'] = display_report_value($header_row['division_name'] ?? '');
        $report_header['zone_name'] = display_report_value($header_row['zone_name'] ?? 'Railway Zone');
    }

    $header_stmt->close();
}

$report_types = [];
$type_stmt = $conn->prepare("
    SELECT DISTINCT UPPER(TRIM(coach_type)) AS coach_type
    FROM fdss_train_coach
    WHERE user_id = ?
      AND coach_type IS NOT NULL
      AND TRIM(coach_type) <> ''
    ORDER BY coach_type ASC
");

if ($type_stmt) {
    $type_stmt->bind_param('i', $user_id);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result();

    while ($row = $type_result->fetch_assoc()) {
        $report_types[] = $row['coach_type'];
    }

    $type_stmt->close();
}

if (empty($report_types)) {
    $report_types = ['FDSS', 'FSDS'];
}

$selected_type = strtoupper(trim($_GET['type'] ?? $report_types[0]));

if (!in_array($selected_type, $report_types, true)) {
    $selected_type = $report_types[0];
}

$parameter_category = $selected_type === 'FDSS' ? 'FDSSPARA' : ($selected_type === 'FSDS' ? 'FSDSPARA' : $selected_type . 'PARA');
$parameters = [];

$parameter_query = "
    SELECT inventory_id, item_name
    FROM fdss_Inventory_Management
    WHERE user_id = ?
      AND UPPER(TRIM(category)) = ?
    ORDER BY inventory_id ASC
";
$parameter_stmt = $conn->prepare($parameter_query);

if ($parameter_stmt) {
    $parameter_stmt->bind_param('is', $user_id, $parameter_category);
    $parameter_stmt->execute();
    $parameter_result = $parameter_stmt->get_result();

    while ($row = $parameter_result->fetch_assoc()) {
        $parameters[] = $row;
    }

    $parameter_stmt->close();
}

if (empty($parameters)) {
    $fallback_parameter_query = "
        SELECT inventory_id, item_name
        FROM fdss_Inventory_Management
        WHERE UPPER(TRIM(category)) = ?
        ORDER BY inventory_id ASC
    ";
    $fallback_parameter_stmt = $conn->prepare($fallback_parameter_query);

    if ($fallback_parameter_stmt) {
        $fallback_parameter_stmt->bind_param('s', $parameter_category);
        $fallback_parameter_stmt->execute();
        $fallback_parameter_result = $fallback_parameter_stmt->get_result();

        while ($row = $fallback_parameter_result->fetch_assoc()) {
            $parameters[] = $row;
        }

        $fallback_parameter_stmt->close();
    }
}

$parameter_by_id = [];
$parameter_by_name = [];

foreach ($parameters as $parameter) {
    $parameter_by_id[(int) $parameter['inventory_id']] = $parameter;
    $parameter_by_name[normalize_report_key($parameter['item_name'])] = $parameter;
}

$report_rows = [];
$schedule_query = "
    SELECT
        s.schedule_id,
        COALESCE(s.train_info_id, c.train_info_id) AS train_info_id,
        s.coach_id,
        s.special_remarks,
        s.assignment_date_time,
        t.train_no,
        t.train_name,
        c.coach_no,
        c.coach_type,
        c.`Type` AS coach_body_type,
        COALESCE(
        (
            SELECT COALESCE(NULLIF(m3.company_name, ''), CONCAT('Manufacturer ID: ', iu3.manufacturer_id))
            FROM fdds_coach_inspection i3
            INNER JOIN fdds_inventory_unit iu3
                ON iu3.unit_id = i3.unit_id
            LEFT JOIN fdss_manufacturers m3
                ON m3.manufacturer_id = iu3.manufacturer_id
            WHERE i3.schedule_id = s.schedule_id
              AND i3.user_id = s.user_id
              AND iu3.manufacturer_id IS NOT NULL
            ORDER BY i3.inspection_id ASC
            LIMIT 1
        ),
        (
            SELECT COALESCE(NULLIF(m2.company_name, ''), CONCAT('Manufacturer ID: ', iu2.manufacturer_id))
            FROM fdss_coach_inventory ci2
            INNER JOIN fdds_inventory_unit iu2
                ON iu2.unit_id = ci2.inventory_unit_id
            LEFT JOIN fdss_manufacturers m2
                ON m2.manufacturer_id = iu2.manufacturer_id
            WHERE ci2.coach_id = s.coach_id
              AND ci2.user_id = s.user_id
              AND iu2.manufacturer_id IS NOT NULL
            ORDER BY ci2.id ASC
            LIMIT 1
        )
        ) AS assigned_make
    FROM fdss_coach_schedule s
    INNER JOIN fdss_train_coach c
        ON c.coach_id = s.coach_id
        AND c.user_id = s.user_id
    LEFT JOIN fdss_train_information t
        ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)
        AND t.user_id = s.user_id
    WHERE s.user_id = ?
      AND s.auditor_id IS NOT NULL
      AND s.assignment_date_time IS NOT NULL
      AND DATE(s.assignment_date_time) BETWEEN ? AND ?
      AND UPPER(TRIM(c.coach_type)) = ?
    ORDER BY t.train_no ASC, c.coach_no ASC, s.assignment_date_time ASC, s.schedule_id ASC
";
$schedule_stmt = $conn->prepare($schedule_query);

if ($schedule_stmt) {
    $schedule_stmt->bind_param('isss', $user_id, $month_start, $month_end, $selected_type);
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();

    while ($row = $schedule_result->fetch_assoc()) {
        $group_key = (string) (int) $row['schedule_id'];
        $report_rows[$group_key] = [
            'train' => trim((string) ($row['train_no'] ?? '')),
            'coach' => trim((string) ($row['coach_no'] ?? '')),
            'coach_body_type' => trim((string) ($row['coach_body_type'] ?? '')),
            'make' => trim((string) ($row['assigned_make'] ?? '')),
            'values' => [],
            'remarks' => trim((string) ($row['special_remarks'] ?? ''))
        ];
    }

    $schedule_stmt->close();
}

$detail_query = "
    SELECT
        i.schedule_id,
        i.inventory_id,
        i.tool_name,
        i.Conditions,
        COALESCE(NULLIF(m.company_name, ''), CONCAT('Manufacturer ID: ', iu.manufacturer_id)) AS make_name
    FROM fdds_coach_inspection i
    INNER JOIN fdss_coach_schedule s
        ON s.schedule_id = i.schedule_id
        AND s.user_id = i.user_id
    INNER JOIN fdss_train_coach c
        ON c.coach_id = s.coach_id
        AND c.user_id = s.user_id
    LEFT JOIN fdss_Inventory_Management im
        ON im.inventory_id = i.inventory_id
    LEFT JOIN fdds_inventory_unit iu
        ON iu.unit_id = i.unit_id
    LEFT JOIN fdss_manufacturers m
        ON m.manufacturer_id = iu.manufacturer_id
    WHERE i.user_id = ?
      AND s.auditor_id IS NOT NULL
      AND s.assignment_date_time IS NOT NULL
      AND DATE(s.assignment_date_time) BETWEEN ? AND ?
      AND UPPER(TRIM(c.coach_type)) = ?
    ORDER BY s.schedule_id ASC, i.inspection_id ASC
";
$detail_stmt = $conn->prepare($detail_query);

if ($detail_stmt) {
    $detail_stmt->bind_param('isss', $user_id, $month_start, $month_end, $selected_type);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();

    while ($row = $detail_result->fetch_assoc()) {
        $group_key = (string) (int) $row['schedule_id'];

        if (!isset($report_rows[$group_key])) {
            continue;
        }

        if ($report_rows[$group_key]['make'] === '' && trim((string) $row['make_name']) !== '') {
            $report_rows[$group_key]['make'] = trim((string) $row['make_name']);
        }

        $parameter = $parameter_by_id[(int) $row['inventory_id']] ?? null;

        if (!$parameter) {
            $parameter = $parameter_by_name[normalize_report_key($row['tool_name'])] ?? null;
        }

        if (!$parameter) {
            continue;
        }

        $parameter_id = (int) $parameter['inventory_id'];
        $report_rows[$group_key]['values'][$parameter_id] = display_report_value($row['Conditions']);

    }

    $detail_stmt->close();
}

$report_rows = array_values($report_rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">

    <style>
        .print-report-header {
            display: none;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            * {
                color: #000 !important;
                background: #fff !important;
                box-shadow: none !important;
                text-shadow: none !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            #navbar-placeholder,
            #sidebar-placeholder,
            #footer-placeholder,
            .navbar,
            .navbar-custom,
            .sidebar,
            footer,
            .page-header,
            .report-filter-card,
            .no-print {
                display: none !important;
            }

            html,
            body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 10px !important;
                line-height: 1.25 !important;
                overflow: visible !important;
            }

            .sidebar-container,
            .main-content {
                display: block !important;
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .content-card {
                border: none !important;
                border-radius: 0 !important;
                margin: 0 0 6px !important;
                padding: 0 !important;
                box-shadow: none !important;
                page-break-inside: auto;
            }

            .content-card .card-body {
                padding: 0 !important;
            }

            .print-report-header {
                display: block !important;
                border: 1px solid #000 !important;
                margin-bottom: 6px !important;
                padding: 6px 8px !important;
                text-align: center !important;
                page-break-inside: avoid;
                page-break-after: avoid;
            }

            .print-report-header h2 {
                margin: 0 0 2px !important;
                font-size: 15px !important;
                font-weight: 800 !important;
                text-transform: uppercase !important;
            }

            .print-report-header .print-subtitle {
                font-size: 11px !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
            }

            .print-report-header .print-caption {
                font-size: 9px !important;
                font-weight: 600 !important;
                margin-top: 1px !important;
                text-transform: uppercase !important;
            }

            .print-report-meta {
                border: 1px solid #000 !important;
                margin-top: 6px !important;
                text-align: left !important;
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .print-report-meta td {
                border: 1px solid #000 !important;
                padding: 4px 6px !important;
                font-size: 10px !important;
                vertical-align: top !important;
            }

            .print-report-meta td:first-child {
                width: 50% !important;
            }

            .report-summary {
                display: none !important;
            }

            .report-title-strip {
                border: 1px solid #000 !important;
                border-bottom: none !important;
                font-size: 12px !important;
                padding: 5px 8px !important;
                text-align: center !important;
                page-break-after: avoid;
            }

            .table-responsive {
                overflow: visible !important;
                width: 100% !important;
            }

            table.report-grid {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                page-break-inside: auto;
            }

            .report-grid {
                font-size: 8px !important;
            }

            .report-grid thead {
                display: table-header-group;
            }

            .report-grid tfoot {
                display: table-footer-group;
            }

            .report-grid tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .report-grid th,
            .report-grid td {
                border: 1px solid #000 !important;
                padding: 3px 4px !important;
                vertical-align: middle !important;
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
                white-space: normal !important;
            }

            .report-grid th {
                font-size: 7.5px !important;
                font-weight: 800 !important;
                text-align: center !important;
            }

            .report-grid th:nth-child(1),
            .report-grid td:nth-child(1) {
                width: 24px !important;
            }

            .report-grid th:nth-child(2),
            .report-grid td:nth-child(2) {
                width: 58px !important;
            }

            .report-grid th:nth-child(3),
            .report-grid td:nth-child(3) {
                width: 96px !important;
            }

            .report-grid th:nth-child(4),
            .report-grid td:nth-child(4) {
                width: 70px !important;
            }

            .report-grid th:last-child,
            .report-grid td:last-child {
                width: 130px !important;
            }

            .ok-cell,
            .na-cell,
            .not-ok-cell {
                font-weight: 800 !important;
            }
        }

        .report-filter-card {
            border-top: 3px solid #222;
        }

        .report-title-strip {
            background: #fff;
            color: #111;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            border: 1px solid #1d1d1d;
            border-bottom: none;
            padding: 10px 14px;
            font-size: 1.05rem;
            text-align: center;
        }

        .report-grid {
            font-size: 12px;
            margin-bottom: 0;
        }

        .report-grid thead th {
            background: #f4f4f4;
            color: #111;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            border: 1px solid #2f2f2f;
        }

        .report-grid thead th.parameter-heading {
            min-width: 120px;
            max-width: 160px;
            line-height: 1.18;
            white-space: normal;
        }

        .parameter-heading-line {
            display: block;
        }

        .report-grid td {
            vertical-align: middle;
            white-space: nowrap;
            border: 1px solid #2f2f2f;
        }

        .report-grid td:last-child,
        .report-grid th:last-child {
            min-width: 220px;
            white-space: normal;
        }

        .ok-cell,
        .na-cell {
            color: #000;
            font-weight: 700;
        }

        .not-ok-cell {
            color: #000;
            font-weight: 700;
            background: #e9e9e9;
        }

        .report-summary strong {
            color: #111;
        }
    </style>
</head>
<body>
<?php include('includes/navbar.php'); ?>
<!-- <div id="navbar-placeholder"></div> -->

<div class="sidebar-container">
    <!-- <div id="sidebar-placeholder"></div> -->
    <?php include('includes/sidebar.php'); ?>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Reports</h1>
                    <p class="page-header-subtitle">
                        <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                        <span class="text-muted"> / </span>FDSS/FSDS Reports
                    </p>
                </div>
            </div>

            <div class="content-card report-filter-card no-print">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Select Month</label>
                            <input type="month" class="form-control" id="reportMonth" name="month" value="<?php echo e($selected_month); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Report Type</label>
                            <select class="form-select" id="reportType" name="type">
                                <?php foreach ($report_types as $type): ?>
                                    <option value="<?php echo e($type); ?>" <?php echo $type === $selected_type ? 'selected' : ''; ?>>
                                        <?php echo e($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100" id="btnGenerate">
                                <i class="bi bi-funnel"></i> Generate Report
                            </button>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="content-card">
                <div class="card-body d-flex flex-wrap justify-content-between gap-2 report-summary">
                    <div>
                        <strong>Selected Type:</strong>
                        <span class="badge badge-info ms-1" id="selectedType"><?php echo e($selected_type); ?></span>
                    </div>
                    <div>
                        <strong>Month:</strong>
                        <span id="selectedMonth"><?php echo e(format_full_report_month($selected_month)); ?></span>
                    </div>
                    <div>
                        <strong>Total Coach:</strong>
                        <span id="recordCount"><?php echo count($report_rows); ?></span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="print-report-header">
                    <h2><?php echo e($report_header['zone_name']); ?></h2>
                    <div class="print-subtitle">Fire Detection &amp; Suppression System (<?php echo e($selected_type); ?>)</div>
                    <div class="print-caption">Monthly Status Report</div>
                    <table class="print-report-meta">
                        <tr>
                            <td>
                                <strong>Division :</strong> <?php echo e($report_header['division_name']); ?><br>
                                <strong>Station :</strong> <?php echo e($report_header['station_name']); ?><br>
                                <strong>Division :</strong> SMVB Coaching Depot
                            </td>
                            <td>
                                <strong>Report Type :</strong> <?php echo e($selected_type); ?><br>
                                <strong>Month :</strong> <?php echo e(format_full_report_month($selected_month)); ?><br>
                                <strong>Total Coach :</strong> <?php echo count($report_rows); ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="report-title-strip" id="reportTitleStrip">
                    STATUS OF <?php echo e($selected_type); ?> <?php echo e($report_header['station_name']); ?>
                </div>
                <div class="table-responsive">
                    <table class="table report-grid" id="reportTable">
                        <thead>
                            <tr>
                                <th>SL.</th>
                                <th>Train No.</th>
                                <th>Coach Type</th>
                                <th>Make</th>
                                <?php foreach ($parameters as $parameter): ?>
                                    <th class="parameter-heading" title="<?php echo e($parameter['item_name']); ?>">
                                        <?php foreach (parameter_header_lines($parameter['item_name']) as $line): ?>
                                            <span class="parameter-heading-line"><?php echo e($line); ?></span>
                                        <?php endforeach; ?>
                                    </th>
                                <?php endforeach; ?>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <?php if (empty($parameters)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No <?php echo e($parameter_category); ?> parameters found.
                                    </td>
                                </tr>
                            <?php elseif (empty($report_rows)): ?>
                                <tr>
                                    <td colspan="<?php echo count($parameters) + 5; ?>" class="text-center text-muted py-4">
                                        No report data found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($report_rows as $index => $row): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $index + 1; ?></td>
                                        <td class="text-center"><?php echo e(display_report_value($row['train'])); ?></td>
                                        <td><?php echo e(display_report_value($row['coach'] . ($row['coach_body_type'] ? ' / ' . $row['coach_body_type'] : ''))); ?></td>
                                        <td class="text-center">
                                            <?php echo e(display_report_value($row['make'])); ?>
                                        </td>
                                        <?php foreach ($parameters as $parameter): ?>
                                            <?php
                                                $value = $row['values'][(int) $parameter['inventory_id']] ?? '-';
                                                $cell_class = status_cell_class($value);
                                            ?>
                                            <td class="text-center <?php echo e($cell_class); ?>">
                                                <?php echo e($value); ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td><?php echo e(display_report_value($row['remarks'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

           <?php include('includes/footer.php'); ?>

    <!-- <div id="footer-placeholder"></div> -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/layout.js"></script>

</body>
</html>
