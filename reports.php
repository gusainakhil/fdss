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

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function display_value($value)
{
    $value = trim((string) $value);
    return $value !== '' ? $value : '-';
}

function format_report_date($value)
{
    return $value ? date('d M Y', strtotime($value)) : '-';
}

function format_report_time($value)
{
    return $value ? date('h:i A', strtotime($value)) : '-';
}

function status_badge($status)
{
    if ($status === 'Completed') {
        return '<span class="status-completed">Completed</span>';
    }

    if ($status === 'In Progress') {
        return '<span class="status-inprogress">In Progress</span>';
    }

    return '<span class="status-pending">' . e($status ?: 'Pending') . '</span>';
}

function condition_badge_class($condition)
{
    $condition = strtolower(trim((string) $condition));

    if (in_array($condition, ['ok', 'good', 'working', 'active'], true)) {
        return 'badge-ok';
    }

    if (in_array($condition, ['issue', 'faulty', 'bad', 'not working', 'failed'], true)) {
        return 'badge-issue';
    }

    return 'badge-na';
}

$today = date('Y-m-d');
$selected_date = trim($_GET['date'] ?? $today);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = $today;
}

$selected_train_filter = trim($_GET['train_info_id'] ?? '');
$is_direct_train_search = in_array($selected_train_filter, ['Detached', 'no_train'], true);
$selected_train = (!$is_direct_train_search && ctype_digit($selected_train_filter))
    ? (int) $selected_train_filter
    : 0;
$selected_coach = (int) ($_GET['coach_id'] ?? 0);
$selected_report_id = (int) ($_GET['report_id'] ?? 0);

$train_options = [];
$train_stmt = $conn->prepare("
    SELECT train_info_id, train_no, train_name
    FROM fdss_train_information
    WHERE user_id = ?
    ORDER BY train_no ASC
");

if ($train_stmt) {
    $train_stmt->bind_param("i", $user_id);
    $train_stmt->execute();
    $train_result = $train_stmt->get_result();

    while ($row = $train_result->fetch_assoc()) {
        $train_options[] = $row;
    }

    $train_stmt->close();
}

$coach_options = [];
$coach_query = "SELECT coach_id, coach_no, coach_type
                FROM fdss_train_coach
                WHERE user_id = ?";
$coach_types = "i";
$coach_values = [$user_id];

if ($selected_train > 0) {
    $coach_query .= " AND train_info_id = ?";
    $coach_types .= "i";
    $coach_values[] = $selected_train;
} elseif ($is_direct_train_search) {
    $coach_query .= " AND train_info_id IS NULL";
}

$coach_query .= " ORDER BY coach_no ASC";
$coach_stmt = $conn->prepare($coach_query);

if ($coach_stmt) {
    $coach_references = [];

    foreach ($coach_values as $key => $value) {
        $coach_references[$key] = &$coach_values[$key];
    }

    $coach_stmt->bind_param($coach_types, ...$coach_references);
    $coach_stmt->execute();
    $coach_result = $coach_stmt->get_result();

    while ($row = $coach_result->fetch_assoc()) {
        $coach_options[] = $row;
    }

    $coach_stmt->close();
}

$where_clause = "WHERE i.user_id = ? AND DATE(i.created_at) = ?";
$bind_types = "is";
$bind_values = [$user_id, $selected_date];

if ($selected_train > 0) {
    $where_clause .= " AND i.train_info_id = ?";
    $bind_types .= "i";
    $bind_values[] = $selected_train;
} elseif ($is_direct_train_search) {
    $where_clause .= " AND (i.train_info_id IS NULL OR i.train_info_id = 0)";
}

if ($selected_coach > 0) {
    $where_clause .= " AND i.coach_id = ?";
    $bind_types .= "i";
    $bind_values[] = $selected_coach;
}

$reports = [];
$reports_query = "
    SELECT
        MIN(i.inspection_id) AS report_id,
        i.schedule_id,
        i.train_info_id,
        i.coach_id,
        i.auditor_id,
        DATE(MIN(i.created_at)) AS inspection_date,
        MIN(i.created_at) AS start_time,
        MAX(i.updated_at) AS end_time,
        COALESCE(MAX(s.status), 'Pending') AS report_status,
        COUNT(*) AS tool_count,
        MAX(t.train_no) AS train_no,
        MAX(t.train_name) AS train_name,
        MAX(c.coach_no) AS coach_no,
        MAX(c.coach_type) AS coach_type,
        MAX(u.user_name) AS auditor_user_name,
        MAX(u.full_name) AS auditor_full_name
    FROM fdds_coach_inspection i
    LEFT JOIN fdss_train_information t ON t.train_info_id = i.train_info_id
    LEFT JOIN fdss_train_coach c ON c.coach_id = i.coach_id
    LEFT JOIN fdss_users u ON u.user_id = i.auditor_id
    LEFT JOIN fdss_coach_schedule s ON s.schedule_id = i.schedule_id
    $where_clause
    GROUP BY
        COALESCE(i.schedule_id, 0),
        i.train_info_id,
        i.coach_id,
        i.auditor_id,
        DATE(i.created_at)
    ORDER BY start_time DESC
";

$reports_stmt = $conn->prepare($reports_query);

if ($reports_stmt) {
    $bind_references = [];

    foreach ($bind_values as $key => $value) {
        $bind_references[$key] = &$bind_values[$key];
    }

    $reports_stmt->bind_param($bind_types, ...$bind_references);
    $reports_stmt->execute();
    $reports_result = $reports_stmt->get_result();

    while ($row = $reports_result->fetch_assoc()) {
        $reports[] = $row;
    }

    $reports_stmt->close();
}

$selected_report = null;
$selected_tools = [];

if ($selected_report_id > 0) {
    $header_query = "
        SELECT
            i.*,
            t.train_no,
            t.train_name,
            c.coach_no,
            c.coach_type,
            auditor.user_name AS auditor_user_name,
            auditor.full_name AS auditor_full_name,
            st.station_name,
            d.division_name,
            z.zone_name,
            s.status AS schedule_status,
            s.special_remarks
        FROM fdds_coach_inspection i
        LEFT JOIN fdss_coach_schedule s ON s.schedule_id = i.schedule_id
        LEFT JOIN fdss_train_information t ON t.train_info_id = i.train_info_id
        LEFT JOIN fdss_train_coach c ON c.coach_id = i.coach_id
        LEFT JOIN fdss_users auditor ON auditor.user_id = i.auditor_id
        LEFT JOIN fdss_users report_user ON report_user.user_id = i.user_id
        LEFT JOIN fdss_stations st ON st.station_id = report_user.station_id
        LEFT JOIN fdss_divisions d ON d.division_id = st.division_id
        LEFT JOIN fdss_zones z ON z.zone_id = d.zone_id
        WHERE i.inspection_id = ?
        AND i.user_id = ?
        LIMIT 1
    ";

    $header_stmt = $conn->prepare($header_query);

    if ($header_stmt) {
        $header_stmt->bind_param("ii", $selected_report_id, $user_id);
        $header_stmt->execute();
        $header_result = $header_stmt->get_result();
        $selected_report = $header_result->fetch_assoc();
        $header_stmt->close();
    }

    if ($selected_report) {
        $tool_where = "WHERE i.user_id = ?";
        $tool_types = "i";
        $tool_values = [$user_id];

        if (!empty($selected_report['schedule_id'])) {
            $tool_where .= " AND i.schedule_id = ?";
            $tool_types .= "i";
            $tool_values[] = (int) $selected_report['schedule_id'];
        } else {
            $tool_where .= " AND i.train_info_id = ? AND i.coach_id = ? AND DATE(i.created_at) = ?";
            $tool_types .= "iis";
            $tool_values[] = (int) $selected_report['train_info_id'];
            $tool_values[] = (int) $selected_report['coach_id'];
            $tool_values[] = date('Y-m-d', strtotime($selected_report['created_at']));

            if (!empty($selected_report['auditor_id'])) {
                $tool_where .= " AND i.auditor_id = ?";
                $tool_types .= "i";
                $tool_values[] = (int) $selected_report['auditor_id'];
            }
        }

        $tools_query = "
            SELECT
                i.tool_name,
                i.Conditions,
                i.remarks,
                i.Serial_No AS serial_number
            FROM fdds_coach_inspection i
            $tool_where
            ORDER BY i.inspection_id ASC
        ";

        $tools_stmt = $conn->prepare($tools_query);

        if ($tools_stmt) {
            $tool_references = [];

            foreach ($tool_values as $key => $value) {
                $tool_references[$key] = &$tool_values[$key];
            }

            $tools_stmt->bind_param($tool_types, ...$tool_references);
            $tools_stmt->execute();
            $tools_result = $tools_stmt->get_result();

            while ($row = $tools_result->fetch_assoc()) {
                $selected_tools[] = $row;
            }

            $tools_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditor Inspection Report - FDSS Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">

    <style>
        /* ── Print styles ── */
        @media print {
            /* Reset entire page to B&W */
            * {
                color: #000 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            /* Hide everything except the report */
            body * { visibility: hidden; }
            #printSection, #printSection * { visibility: visible; }

            /* Page setup */
            @page {
                size: A4 portrait;
                margin: 15mm 12mm 15mm 12mm;
            }

            body {
                padding: 0 !important;
                margin: 0 !important;
                font-family: 'Times New Roman', Times, serif !important;
                font-size: 11pt !important;
            }

            /* Position the report to fill the page */
            #printSection {
                position: fixed;
                top: 0; left: 0;
                width: 100%;
                padding: 0;
                border: none !important;
            }

            #printSection .card-body {
                padding: 0 !important;
            }

            /* Hide non-print elements */
            .no-print,
            #navbar-placeholder,
            #sidebar-placeholder,
            #footer-placeholder,
            .sidebar-container > .main-content > .page-header,
            #listSection,
            #reportSection > div:last-child { display: none !important; }

            /* ── Report header ── */
            .report-header-title {
                font-size: 18pt !important;
                font-weight: bold !important;
                letter-spacing: 2px !important;
                border-bottom: 2px solid #000 !important;
            }

            /* Emblem/logo row (if any) */
            .report-logo { display: block !important; }

            /* ── Info box ── */
            .report-info-box {
                border: 1.5px solid #000 !important;
                width: 100% !important;
                margin-bottom: 10px !important;
            }
            .report-info-box td {
                font-size: 10pt !important;
                padding: 5px 10px !important;
                border: none !important;
            }
            .report-info-box td:first-child {
                border-right: 1px solid #000 !important;
            }

            /* ── Section title ── */
            .section-title {
                font-size: 10pt !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                border-bottom: 1.5px solid #000 !important;
                padding-bottom: 3px !important;
                margin: 12px 0 6px !important;
            }

            /* ── Checklist table ── */
            .inspection-table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 9.5pt !important;
            }
            .inspection-table th {
                background: #e0e0e0 !important;
                border: 1px solid #000 !important;
                font-weight: bold !important;
                padding: 5px 6px !important;
                text-align: center !important;
            }
            .inspection-table td {
                border: 1px solid #000 !important;
                padding: 5px 6px !important;
                vertical-align: middle !important;
            }

            /* Condition badges → plain B&W boxes */
            .badge-ok, .badge-issue, .badge-na {
                background: #fff !important;
                color: #000 !important;
                border: 1px solid #000 !important;
                padding: 2px 8px !important;
                font-weight: bold !important;
                font-size: 9pt !important;
                border-radius: 0 !important;
                display: inline-block !important;
            }

            /* ── Signature table ── */
            .signature-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 4px !important;
            }
            .signature-table th {
                border: 1px solid #000 !important;
                padding: 5px !important;
                font-size: 9.5pt !important;
                font-weight: bold !important;
                background: #e0e0e0 !important;
                text-align: center !important;
            }
            .signature-table td {
                height: 70px !important;
                border: 1px solid #000 !important;
            }

            /* Page break control */
            .inspection-table { page-break-inside: avoid; }
        }

        /* ── Report card ── */
        #reportSection { display: none; }

        .report-header-title {
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-info-box {
            border: 1px solid #333;
            border-radius: 0;
        }
        .report-info-box td {
            padding: 5px 12px;
            font-size: 0.88rem;
            border: none;
        }
        .inspection-table th {
            background: #f0f0f0;
            font-size: 0.82rem;
            text-align: center;
            border: 1px solid #ccc;
        }
        .inspection-table td {
            border: 1px solid #ccc;
            font-size: 0.83rem;
            vertical-align: middle;
        }
        .section-title {
            font-weight: 700;
            font-size: 0.88rem;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            padding-bottom: 4px;
            margin: 18px 0 10px;
        }
        .signature-table th {
            background: #f5f5f5;
            font-size: 0.82rem;
            border: 1px solid #ccc;
            text-align: center;
        }
        .signature-table td {
            height: 56px;
            border: 1px solid #ccc;
        }
        .badge-ok   { background: #d4edda; color: #155724; font-weight: 600; padding: 3px 10px; border-radius: 4px; }
        .badge-issue{ background: #f8d7da; color: #721c24; font-weight: 600; padding: 3px 10px; border-radius: 4px; }
        .badge-na   { background: #e2e3e5; color: #383d41; font-weight: 600; padding: 3px 10px; border-radius: 4px; }

        /* ── Today list ── */
        .today-badge {
            background: #0d6efd;
            color: #fff;
            font-size: 0.75rem;
            padding: 2px 10px;
            border-radius: 20px;
        }
        .status-inprogress { background:#fff3cd; color:#856404; border-radius:20px; padding:2px 10px; font-size:0.78rem; font-weight:600; }
        .status-completed  { background:#d1e7dd; color:#0a3622; border-radius:20px; padding:2px 10px; font-size:0.78rem; font-weight:600; }
        .status-pending    { background:#f8d7da; color:#58151c; border-radius:20px; padding:2px 10px; font-size:0.78rem; font-weight:600; }
    </style>
</head>
<body>
<?php include('includes/navbar.php'); ?>
<!-- <div id="navbar-placeholder"></div> -->

<div class="sidebar-container">
    <!-- <div id="sidebar-placeholder"></div> -->
    <?php include('includes/sidebar.php'); ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <!-- PAGE HEADER -->
            <div class="page-header no-print">
                <div>
                    <h1>Auditor Inspection Report</h1>
                    <p class="page-header-subtitle">
                        <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                        <span class="text-muted"> / </span>Inspection Report
                    </p>
                </div>
            </div>

            <!-- ── FILTER CARD ── -->
            <div class="content-card no-print mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Select Train No.</label>
                            <select class="form-select"
                                    name="train_info_id"
                                    id="filterTrain"
                                    onchange="document.getElementById('filterCoach').value=''; this.form.submit();">
                                <option value="">-- All Trains --</option>
                                <option value="Detached"
                                        <?php echo $is_direct_train_search ? 'selected' : ''; ?>>
                                    Detached
                                </option>

                                <?php foreach ($train_options as $train): ?>

                                    <option value="<?php echo e($train['train_info_id']); ?>"
                                            <?php echo !$is_direct_train_search && $selected_train === (int) $train['train_info_id'] ? 'selected' : ''; ?>>
                                        <?php echo e($train['train_no'] . ' - ' . $train['train_name']); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Select Coach</label>
                            <select class="form-select" name="coach_id" id="filterCoach">
                                <option value="">-- All Coaches --</option>

                                <?php foreach ($coach_options as $coach): ?>

                                    <option value="<?php echo e($coach['coach_id']); ?>"
                                            <?php echo $selected_coach === (int) $coach['coach_id'] ? 'selected' : ''; ?>>
                                        <?php echo e($coach['coach_no'] . ($coach['coach_type'] ? ' / ' . $coach['coach_type'] : '')); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date</label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="date" id="filterDate" value="<?php echo e($selected_date); ?>">
                                <a class="btn btn-outline-secondary" href="reports.php?date=<?php echo e($today); ?>" title="Select Today">
                                    <i class="bi bi-calendar-check"></i> Today
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="bi bi-search"></i> Search Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── TODAY'S INSPECTIONS LIST ── -->
            <div id="listSection" class="no-print" style="<?php echo $selected_report ? 'display:none;' : ''; ?>">
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clipboard2-check me-2 text-primary"></i>
                            Inspection Reports
                            <span class="today-badge ms-2" id="listDateLabel"><?php echo e(format_report_date($selected_date)); ?></span>
                        </h5>
                        <span class="text-muted" style="font-size:0.85rem" id="recordCount">
                            Showing <?php echo count($reports); ?> record<?php echo count($reports) === 1 ? '' : 's'; ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="inspectionListTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Train No.</th>
                                        <th>Coach No.</th>
                                        <th>Auditor</th>
                                        <th>Date</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php if (empty($reports)): ?>

                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                No inspections found for selected filters.
                                            </td>
                                        </tr>

                                    <?php else: ?>

                                        <?php foreach ($reports as $index => $report): ?>

                                            <?php
                                            $auditor_name = !empty($report['auditor_full_name'])
                                                ? $report['auditor_full_name']
                                                : $report['auditor_user_name'];
                                            $report_url = 'reports.php?' . http_build_query([
                                                'date' => $selected_date,
                                                'train_info_id' => $is_direct_train_search ? 'Detached' : ($selected_train ?: null),
                                                'coach_id' => $selected_coach ?: null,
                                                'report_id' => $report['report_id']
                                            ]);
                                            ?>

                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <strong><?php echo e(display_value($report['train_no'])); ?></strong><br>
                                                    <?php echo e(display_value($report['train_name'])); ?>
                                                </td>
                                                <td><?php echo e(display_value($report['coach_no'])); ?></td>
                                                <td><?php echo e(display_value($auditor_name)); ?></td>
                                                <td><?php echo e(format_report_date($report['inspection_date'])); ?></td>
                                                <td><?php echo e(format_report_time($report['start_time'])); ?></td>
                                                <td><?php echo e(format_report_time($report['end_time'])); ?></td>
                                                <td><?php echo status_badge($report['report_status']); ?></td>
                                                <td>
                                                    <a class="btn btn-sm btn-primary" href="<?php echo e($report_url); ?>">
                                                        <i class="bi bi-eye"></i> View Report
                                                    </a>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── DETAILED INSPECTION REPORT ── -->
            <div id="reportSection" style="<?php echo $selected_report ? 'display:block;' : ''; ?>">
                <div id="printSection" class="content-card">
                    <div class="card-body">

                        <!-- Report Header -->
                        <div class="text-center mb-3" style="border-bottom:2px solid #333; padding-bottom:10px;">
                            <!-- IR wheel emblem (print-friendly SVG) -->
                            <div class="mb-1">
                                <!-- <svg width="52" height="52" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="display:inline-block;">
                                    <circle cx="50" cy="50" r="46" fill="none" stroke="#333" stroke-width="4"/>
                                    <circle cx="50" cy="50" r="10" fill="#333"/>
                                    
                                    <g stroke="#333" stroke-width="3">
                                        <line x1="50" y1="4" x2="50" y2="40"/>
                                        <line x1="50" y1="60" x2="50" y2="96"/>
                                        <line x1="4" y1="50" x2="40" y2="50"/>
                                        <line x1="60" y1="50" x2="96" y2="50"/>
                                        <line x1="17.2" y1="17.2" x2="40.9" y2="40.9"/>
                                        <line x1="59.1" y1="59.1" x2="82.8" y2="82.8"/>
                                        <line x1="82.8" y1="17.2" x2="59.1" y2="40.9"/>
                                        <line x1="40.9" y1="59.1" x2="17.2" y2="82.8"/>
                                        <line x1="7.7" y1="29.3" x2="38.2" y2="44.8"/>
                                        <line x1="61.8" y1="55.2" x2="92.3" y2="70.7"/>
                                        <line x1="29.3" y1="7.7" x2="44.8" y2="38.2"/>
                                        <line x1="55.2" y1="61.8" x2="70.7" y2="92.3"/>
                                    </g>
                                </svg> -->
                            </div>
                            <div class="report-header-title">
                                <?php echo e($selected_report ? display_value($selected_report['zone_name']) : 'Railway Zone'); ?>
                            </div>
                            <div style="font-size:1rem; font-weight:600; letter-spacing:0.5px; margin-top:2px;">Fire Detection &amp; Suppression System (FDSS)</div>
                            <div style="font-size:0.82rem; color:#555; margin-top:2px; letter-spacing:0.3px;">FDSS Equipment Inspection Report</div>
                        </div>

                        <!-- Train / Auditor Info -->
                        <div class="report-info-box mb-0">
                            <table class="w-100">
                                <tr>
                                    <td style="width:50%; border-right:1px solid #ccc;">
                                        <strong>Train No :</strong>
                                        <?php echo e($selected_report ? display_value($selected_report['train_no'] . ' - ' . $selected_report['train_name']) : '-'); ?><br>
                                        <strong>Coach No :</strong>
                                        <?php echo e($selected_report ? display_value($selected_report['coach_no']) : '-'); ?><br>
                                        <strong>Division :</strong>
                                        <?php echo e($selected_report ? display_value($selected_report['division_name']) : '-'); ?><br>
                                        <strong>Station :</strong>
                                        <?php echo e($selected_report ? display_value($selected_report['station_name']) : '-'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $selected_auditor_name = '';

                                        if ($selected_report) {
                                            $selected_auditor_name = !empty($selected_report['auditor_full_name'])
                                                ? $selected_report['auditor_full_name']
                                                : $selected_report['auditor_user_name'];
                                        }

                                        $selected_status = $selected_report['schedule_status'] ?? '-';
                                        ?>

                                        <strong>Auditor Name :</strong>
                                        <?php echo e(display_value($selected_auditor_name)); ?><br>
                                        <strong>Date :</strong>
                                        <?php echo e($selected_report ? format_report_date($selected_report['created_at']) : '-'); ?><br>
                                        <strong>Start Time :</strong>
                                        <?php echo e($selected_report ? format_report_time($selected_report['created_at']) : '-'); ?> &nbsp;
                                        <strong>End Time :</strong>
                                        <?php echo e($selected_report ? format_report_time($selected_report['updated_at']) : '-'); ?><br>
                                        <strong>Status :</strong>
                                        <?php echo e($selected_status); ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Tools Inspection Table -->
                        <div class="section-title mt-3">Tools / Equipment Inspection Checklist</div>
                        <div class="table-responsive">
                            <table class="table inspection-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">S.No.</th>
                                        <th>Tool Details</th>
                                        <th style="width:130px;">Serial No.</th>
                                        <th style="width:90px;">Condition</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($selected_tools)): ?>

                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                Select a report to view inspection details.
                                            </td>
                                        </tr>

                                    <?php else: ?>

                                        <?php foreach ($selected_tools as $index => $tool): ?>

                                            <?php $condition_class = condition_badge_class($tool['Conditions']); ?>

                                            <tr>
                                                <td class="text-center"><?php echo $index + 1; ?></td>
                                                <td><strong><?php echo e(display_value($tool['tool_name'])); ?></strong></td>
                                                <td class="text-center"><?php echo e(display_value($tool['serial_number'])); ?></td>
                                                <td class="text-center">
                                                    <span class="<?php echo e($condition_class); ?>">
                                                        <?php echo e(display_value($tool['Conditions'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo e(display_value($tool['remarks'])); ?></td>
                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="section-title">Special Remarks</div>
                        <div style="border:1px solid #ccc; padding:10px; min-height:52px; font-size:0.88rem;">
                            <?php echo e($selected_report ? display_value($selected_report['special_remarks']) : '-'); ?>
                        </div>

                        <!-- Verification / Signature -->
                        <div class="section-title">Verification / Signature</div>
                        <table class="table signature-table mb-0">
                            <thead>
                                <tr>
                                    <th>Auditor Signature</th>
                                    <th>Supervisor Signature</th>
                                    <th>Railway Officer Signature</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>

                    </div><!-- /card-body -->
                </div><!-- /printSection -->

                <!-- Report Actions -->
                <div class="d-flex justify-content-end gap-2 mt-3 no-print">
                    <a class="btn btn-outline-secondary"
                       href="reports.php?<?php echo e(http_build_query([
                           'date' => $selected_date,
                           'train_info_id' => $is_direct_train_search ? 'Detached' : ($selected_train ?: null),
                           'coach_id' => $selected_coach ?: null
                       ])); ?>">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button class="btn btn-dark" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </div>
            <!-- /reportSection -->

        </main>
    </div>

           <?php include('includes/footer.php'); ?>

    <!-- <div id="footer-placeholder"></div> -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <!-- Layout JS -->
    <script src="assets/js/layout.js"></script>

</body>
</html>
