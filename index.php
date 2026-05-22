<?php 
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'ORG_ADMIN') {
    header('Location: login.php?access=denied&role=' . htmlspecialchars($_SESSION['role']));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function get_count($conn, $sql, $types = '', ...$params) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

$total_trains = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_information WHERE user_id = ?", "i", $user_id);
$total_auditors = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_users WHERE role = 'AUDITOR' AND created_by_user_id = ?", "i", $user_id);
$total_coaches = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ?", "i", $user_id);
$total_inventory_used = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_coach_inventory WHERE user_id = ?", "i", $user_id);

$fdss_coaches = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND coach_type LIKE '%FDSS%'", "i", $user_id);
$FSDS_coaches = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND coach_type LIKE '%FSDS%'", "i", $user_id);

$fdss_inventory_used = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    WHERE ci.user_id = ? AND c.coach_type LIKE '%FDSS%'
", "i", $user_id);

$FSDS_inventory_used = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    WHERE ci.user_id = ? AND c.coach_type LIKE '%FSDS%'
", "i", $user_id);

$total_oem_makes = get_count($conn, "
    SELECT COUNT(DISTINCT iu.manufacturer_id) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ? AND iu.manufacturer_id IS NOT NULL
", "i", $user_id);

$fdss_oem_makes = get_count($conn, "
    SELECT COUNT(DISTINCT iu.manufacturer_id) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ?
    AND c.coach_type LIKE '%FDSS%'
    AND iu.manufacturer_id IS NOT NULL
", "i", $user_id);

$FSDS_oem_makes = get_count($conn, "
    SELECT COUNT(DISTINCT iu.manufacturer_id) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ?
    AND c.coach_type LIKE '%FSDS%'
    AND iu.manufacturer_id IS NOT NULL
", "i", $user_id);

$fdss_under_warranty = get_count($conn, "
    SELECT COUNT(*) AS total 
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ? AND c.coach_type LIKE '%FDSS%' AND iu.Warranty_expire IS NOT NULL AND DATE(iu.Warranty_expire) >= CURDATE()
", "i", $user_id);

$FSDS_under_warranty = get_count($conn, "
    SELECT COUNT(*) AS total 
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ? AND c.coach_type LIKE '%FSDS%' AND iu.Warranty_expire IS NOT NULL AND DATE(iu.Warranty_expire) >= CURDATE()
", "i", $user_id);

$FSDS_out_warranty = get_count($conn, "
    SELECT COUNT(*) AS total 
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ? AND c.coach_type LIKE '%FSDS%' AND iu.Warranty_expire IS NOT NULL AND DATE(iu.Warranty_expire) < CURDATE()
", "i", $user_id);

$fdss_out_warranty = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdss_train_coach c ON c.coach_id = ci.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ? AND c.coach_type LIKE '%FDSS%' AND iu.Warranty_expire IS NOT NULL AND DATE(iu.Warranty_expire) < CURDATE()
", "i", $user_id);

$total_under_warranty = $fdss_under_warranty + $FSDS_under_warranty;
$total_out_warranty = $fdss_out_warranty + $FSDS_out_warranty;

$expired_inventory = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    WHERE ci.user_id = ? AND iu.Warranty_expire IS NOT NULL AND DATE(iu.Warranty_expire) < CURDATE()
", "i", $user_id);
$active_inventory = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_coach_inventory WHERE user_id = ? AND status = 'Active'", "i", $user_id);

$pending_schedules = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_coach_schedule WHERE user_id = ? AND status = 'Pending'", "i", $user_id);
$assigned_schedules = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_coach_schedule WHERE user_id = ? AND status = 'Assigned'", "i", $user_id);
$completed_schedules = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_coach_schedule WHERE user_id = ? AND status = 'Completed'", "i", $user_id);

$subscription_days = 0;
$sub_stmt = $conn->prepare("SELECT end_date FROM fdss_users WHERE user_id = ? LIMIT 1");
if ($sub_stmt) {
    $sub_stmt->bind_param("i", $user_id);
    $sub_stmt->execute();
    $sub_result = $sub_stmt->get_result();
    if ($sub_row = $sub_result->fetch_assoc()) {
        if (!empty($sub_row['end_date'])) {
            $today_date = new DateTime(date('Y-m-d'));
            $end_date = new DateTime($sub_row['end_date']);
            $subscription_days = max(0, (int)$today_date->diff($end_date)->format('%r%a'));
        }
    }
    $sub_stmt->close();
}

$weekly_assigned = [0, 0, 0, 0, 0, 0, 0];
$weekly_completed = [0, 0, 0, 0, 0, 0, 0];

$week_query = "
    SELECT DAYOFWEEK(assignment_date_time) AS day_no,
    SUM(CASE WHEN status IN ('Assigned','Pending') THEN 1 ELSE 0 END) AS assigned_total,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_total
    FROM fdss_coach_schedule
    WHERE user_id = ? AND assignment_date_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DAYOFWEEK(assignment_date_time)
";

$week_stmt = $conn->prepare($week_query);
if ($week_stmt) {
    $week_stmt->bind_param("i", $user_id);
    $week_stmt->execute();
    $week_result = $week_stmt->get_result();
    while ($row = $week_result->fetch_assoc()) {
        $day_no = (int)$row['day_no'];
        $index_map = [2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5, 1 => 6];
        if (isset($index_map[$day_no])) {
            $idx = $index_map[$day_no];
            $weekly_assigned[$idx] = (int)$row['assigned_total'];
            $weekly_completed[$idx] = (int)$row['completed_total'];
        }
    }
    $week_stmt->close();
}

$inventory_names = [];
$inventory_usage_counts = [];

$inventory_usage_query = "
    SELECT im.item_name AS tool_name, COUNT(*) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    INNER JOIN fdss_Inventory_Management im ON im.inventory_id = iu.inventory_id
    WHERE ci.user_id = ?
    GROUP BY im.item_name
    ORDER BY total DESC
    LIMIT 10
";

$inv_stmt = $conn->prepare($inventory_usage_query);
if ($inv_stmt) {
    $inv_stmt->bind_param("i", $user_id);
    $inv_stmt->execute();
    $inv_result = $inv_stmt->get_result();
    while ($row = $inv_result->fetch_assoc()) {
        $inventory_names[] = $row['tool_name'];
        $inventory_usage_counts[] = (int)$row['total'];
    }
    $inv_stmt->close();
}

$oem_names = [];
$oem_counts = [];

$oem_query = "
    SELECT COALESCE(m.company_name, CONCAT('Manufacturer #', iu.manufacturer_id)) AS manufacturer_name, COUNT(*) AS total
    FROM fdss_coach_inventory ci
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = ci.inventory_unit_id
    LEFT JOIN fdss_manufacturers m ON m.manufacturer_id = iu.manufacturer_id
    WHERE ci.user_id = ?
    AND iu.manufacturer_id IS NOT NULL
    GROUP BY iu.manufacturer_id, m.company_name
    ORDER BY total DESC
    LIMIT 8
";

$oem_stmt = $conn->prepare($oem_query);
if ($oem_stmt) {
    $oem_stmt->bind_param("i", $user_id);
    $oem_stmt->execute();
    $oem_result = $oem_stmt->get_result();
    while ($row = $oem_result->fetch_assoc()) {
        $oem_names[] = $row['manufacturer_name'];
        $oem_counts[] = (int)$row['total'];
    }
    $oem_stmt->close();
}

$detached_total = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND train_info_id IS NULL", "i", $user_id);
$detached_fdss = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND train_info_id IS NULL AND coach_type LIKE '%FDSS%'", "i", $user_id);
$detached_fsds = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND train_info_id IS NULL AND coach_type LIKE '%FSDS%'", "i", $user_id);

$intact_total = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND train_info_id IS NOT NULL", "i", $user_id);
$intact_fdss = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND train_info_id IS NOT NULL AND coach_type LIKE '%FDSS%'", "i", $user_id);
$intact_fsds = get_count($conn, "SELECT COUNT(*) AS total FROM fdss_train_coach WHERE user_id = ? AND train_info_id IS NOT NULL AND coach_type LIKE '%FSDS%'", "i", $user_id);

$ok_total = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection
    WHERE user_id = ? AND LOWER(Conditions) IN ('ok', 'good', 'working', 'active')
", "i", $user_id);
$ok_fdss = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection i
    INNER JOIN fdss_train_coach c ON c.coach_id = i.coach_id
    WHERE i.user_id = ? AND LOWER(i.Conditions) IN ('ok', 'good', 'working', 'active') AND c.coach_type LIKE '%FDSS%'
", "i", $user_id);
$ok_fsds = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection i
    INNER JOIN fdss_train_coach c ON c.coach_id = i.coach_id
    WHERE i.user_id = ? AND LOWER(i.Conditions) IN ('ok', 'good', 'working', 'active') AND c.coach_type LIKE '%FSDS%'
", "i", $user_id);

$broken_total = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection
    WHERE user_id = ? AND LOWER(Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed')
", "i", $user_id);
$broken_fdss = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection i
    INNER JOIN fdss_train_coach c ON c.coach_id = i.coach_id
    WHERE i.user_id = ? AND LOWER(i.Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed') AND c.coach_type LIKE '%FDSS%'
", "i", $user_id);
$broken_fsds = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection i
    INNER JOIN fdss_train_coach c ON c.coach_id = i.coach_id
    WHERE i.user_id = ? AND LOWER(i.Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed') AND c.coach_type LIKE '%FSDS%'
", "i", $user_id);

$warranty_claim_total = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection i
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = i.unit_id
    WHERE i.user_id = ?
    AND LOWER(i.Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed')
    AND iu.Warranty_expire IS NOT NULL
    AND DATE(iu.Warranty_expire) >= CURDATE()
", "i", $user_id);
$warranty_claim_fdss = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection i
    INNER JOIN fdss_train_coach c ON c.coach_id = i.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = i.unit_id
    WHERE i.user_id = ?
    AND LOWER(i.Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed')
    AND iu.Warranty_expire IS NOT NULL
    AND DATE(iu.Warranty_expire) >= CURDATE()
    AND c.coach_type LIKE '%FDSS%'
", "i", $user_id);
$warranty_claim_fsds = get_count($conn, "
    SELECT COUNT(*) AS total
    FROM fdds_coach_inspection i
    INNER JOIN fdss_train_coach c ON c.coach_id = i.coach_id
    INNER JOIN fdds_inventory_unit iu ON iu.unit_id = i.unit_id
    WHERE i.user_id = ?
    AND LOWER(i.Conditions) IN ('issue', 'faulty', 'bad', 'not working', 'failed')
    AND iu.Warranty_expire IS NOT NULL
    AND DATE(iu.Warranty_expire) >= CURDATE()
    AND c.coach_type LIKE '%FSDS%'
", "i", $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FDSS Dashboard - Indian Railways</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        body{background:#f4f6f9}
        .main-content{padding-top:12px}
        .page-header{margin-bottom:10px}
        .page-header h1{font-size:21px;margin-bottom:2px}
        .page-header-subtitle{font-size:12px}

        .compact-grid{
            display:grid;
            grid-template-columns:repeat(5, minmax(150px, 1fr));
            gap:9px;
            margin-bottom:12px;
        }

        .dash-box{
            background:#fff;
            border:1px solid #e7eaee;
            border-radius:10px;
            padding:10px;
            display:flex;
            align-items:center;
            gap:9px;
            min-height:78px;
            box-shadow:0 2px 8px rgba(0,0,0,.04);
        }

        .dash-icon{
            width:39px;
            height:39px;
            min-width:39px;
            border-radius:10px;
            background:#eef6fb;
            color:#3c8dbc;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
        }

        .dash-box.dark .dash-icon{background:#343a40;color:#fff}
        .dash-box.warning .dash-icon{background:#fff3cd;color:#856404}
        .dash-box.danger .dash-icon{background:#f8d7da;color:#842029}
        .dash-box.success .dash-icon{background:#d1e7dd;color:#0f5132}

        .dash-info h6{
            font-size:11px;
            margin:0;
            color:#6c757d;
            font-weight:700;
            line-height:1.15;
        }

        .dash-info h3{
            font-size:21px;
            margin:2px 0;
            font-weight:800;
            color:#1f2937;
            line-height:1;
        }

        .dash-info p{
            font-size:10.5px;
            margin:0;
            color:#6c757d;
            line-height:1.2;
        }

        .content-card{margin-bottom:10px}
        .content-card .card-header{padding:9px 12px}
        .content-card .card-header h5{font-size:14px;margin:0}
        .content-card .card-body{padding:10px}

        .chart-container{height:225px}
        .chart-container.small{height:225px}
        .chart-container.inventory{height:270px}
        .chart-container.oem{height:250px}

        .table th,.table td{font-size:12px;padding:7px}
        .quick-box{padding:9px;margin-bottom:7px}
        .quick-box h6{font-size:12px;margin-bottom:4px}
        .quick-box p{font-size:11px;margin-bottom:0}

        @media(max-width:1400px){.compact-grid{grid-template-columns:repeat(4,1fr)}}
        @media(max-width:1200px){.compact-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:992px){.compact-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:576px){.compact-grid{grid-template-columns:1fr}}
    </style>
</head>

<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">
    <?php include('includes/sidebar.php'); ?>
</div>

<main class="main-content">
    <div class="page-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p class="page-header-subtitle">Indian Railways FDSS - Station Level Monitoring Panel</p>
        </div>

        <div class="page-header-actions">
            <button class="btn btn-primary btn-sm" onclick="window.print()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    <div class="compact-grid">
        <div class="dash-box">
            <div class="dash-icon"><i class="bi bi-train-freight-front"></i></div>
            <div class="dash-info">
                <h6>Total Trains</h6>
                <h3><?php echo number_format($total_trains); ?></h3>
                <p>Registered trains</p>
            </div>
        </div>

        <div class="dash-box dark">
            <div class="dash-icon"><i class="bi bi-person-badge"></i></div>
            <div class="dash-info">
                <h6>Total Auditors</h6>
                <h3><?php echo number_format($total_auditors); ?></h3>
                <p>Active staff</p>
            </div>
        </div>

        <div class="dash-box">
            <div class="dash-icon"><i class="bi bi-box-seam"></i></div>
            <div class="dash-info">
                <h6>Total Coaches</h6>
                <h3><?php echo number_format($total_coaches); ?></h3>
                <p>FDSS: <?php echo number_format($fdss_coaches); ?> | FSDS: <?php echo number_format($FSDS_coaches); ?></p>
            </div>
        </div>

        <div class="dash-box warning">
            <div class="dash-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="dash-info">
                <h6>Subscription Pending</h6>
                <h3><?php echo number_format($subscription_days); ?></h3>
                <p>Days remaining</p>
            </div>
        </div>

        <div class="dash-box success">
            <div class="dash-icon"><i class="bi bi-boxes"></i></div>
            <div class="dash-info">
                <h6>Total Components Used</h6>
                <h3><?php echo number_format($total_inventory_used); ?></h3>
                <p>FDSS: <?php echo number_format($fdss_inventory_used); ?> | FSDS: <?php echo number_format($FSDS_inventory_used); ?></p>
            </div>
        </div>
         <div class="dash-box">
            <div class="dash-icon"><i class="bi bi-arrows-collapse"></i></div>
            <div class="dash-info">
                <h6> Coach Detached</h6>
                <h3><?php echo number_format($detached_total); ?></h3>
                <p>FDSS: <?php echo $detached_fdss; ?> | FSDS: <?php echo $detached_fsds; ?></p>
            </div>
        </div>

        <div class="dash-box success">
            <div class="dash-icon"><i class="bi bi-link-45deg"></i></div>
            <div class="dash-info">
                <h6> Coach Intact</h6>
                <h3><?php echo number_format($intact_total); ?></h3>
                <p>FDSS: <?php echo $intact_fdss; ?> | FSDS: <?php echo $intact_fsds; ?></p>
            </div>
        </div>

        <div class="dash-box">
            <div class="dash-icon"><i class="bi bi-building-gear"></i></div>
            <div class="dash-info">
                <h6>Total OEM Makes</h6>
                <h3><?php echo number_format($total_oem_makes); ?></h3>
                 <p>FDSS: <?php echo number_format($fdss_oem_makes); ?> | FSDS: <?php echo number_format($FSDS_oem_makes); ?></p>
            </div>
        </div>
          <div class="dash-box success">
            <div class="dash-icon"><i class="bi bi-emoji-smile"></i></div>
            <div class="dash-info">
                <h6>Components OK</h6>
                <h3><?php echo number_format($ok_total); ?></h3>
                <p>FDSS: <?php echo $ok_fdss; ?> | FSDS: <?php echo $ok_fsds; ?></p>
            </div>
        </div>
        

        <div class="dash-box danger">
            <div class="dash-icon"><i class="bi bi-tools"></i></div>
            <div class="dash-info">
                <h6>Components Broken /Defected</h6>
                <h3><?php echo number_format($broken_total); ?></h3>
                <p>FDSS: <?php echo $broken_fdss; ?> | FSDS: <?php echo $broken_fsds; ?></p>
            </div>
        </div>
         <div class="dash-box warning">
            <div class="dash-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="dash-info">
                <h6>Warranty Claim</h6>
                <h3><?php echo number_format($warranty_claim_total); ?></h3>
                <p>FDSS: <?php echo $warranty_claim_fdss; ?> | FSDS: <?php echo $warranty_claim_fsds; ?></p>
            </div>
        </div>

        <div class="dash-box success">
            <div class="dash-icon"><i class="bi bi-shield-check"></i></div>
            <div class="dash-info">
                <h6> Under Warranty</h6>
                <h3><?php echo number_format($total_under_warranty); ?></h3>
                <p>FDSS: <?php echo number_format($fdss_under_warranty); ?>   | FSDS: <?php echo number_format($FSDS_under_warranty); ?></p>
            </div>
        </div>
        <div class="dash-box danger">
            <div class="dash-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="dash-info">
                <h6>Out Of Warranty</h6>
                <h3><?php echo number_format($total_out_warranty); ?></h3>
                <p>FDSS: <?php echo number_format($fdss_out_warranty); ?> | FSDS: <?php echo number_format($FSDS_out_warranty); ?></p>
            </div>
        </div>

        <div class="dash-box info">
            <div class="dash-icon"><i class="bi bi-x-octagon"></i></div>
            <div class="dash-info">
                <h6> FDSS / FSDS</h6>
                <h3>0</h3>
                <p>FDSS: 0 | FSDS: 0</p>
            </div>
        </div>

        <div class="dash-box success">
            <div class="dash-icon"><i class="bi bi-check-circle"></i></div>
            <div class="dash-info">
                <h6>Components Count Type</h6>
                <h3><?php echo number_format($active_inventory); ?></h3>
                <p>FDSS: <?php echo number_format($fdss_inventory_used); ?> | FSDS: <?php echo number_format($FSDS_inventory_used); ?></p>
            </div>
        </div>

        

       

      

       
    </div>

    <div class="row g-2">
        <div class="col-lg-7">
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-graph-up"></i> Weekly Inspection Activity</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="dailyReportChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-pie-chart"></i> Warranty Status</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container small">
                        <canvas id="trainStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-2">
        <div class="col-lg-8">
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-bar-chart"></i> Components Usage Count</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container inventory">
                        <canvas id="inventoryUsageChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-list-check"></i> Schedule Summary</h5>
                </div>

                <div class="card-body">
                    <table class="table table-bordered table-hover mb-0">
                        <tbody>
                            <tr>
                                <td>Assigned</td>
                                <td><strong><?php echo number_format($assigned_schedules); ?></strong></td>
                                <td><span class="badge badge-info">Assigned</span></td>
                            </tr>
                            <tr>
                                <td>Completed</td>
                                <td><strong><?php echo number_format($completed_schedules); ?></strong></td>
                                <td><span class="badge badge-success">Done</span></td>
                            </tr>
                            <tr>
                                <td>Pending</td>
                                <td><strong><?php echo number_format($pending_schedules); ?></strong></td>
                                <td><span class="badge badge-warning">Pending</span></td>
                            </tr>
                            <tr>
                                <td>Expired Inventory</td>
                                <td><strong><?php echo number_format($expired_inventory); ?></strong></td>
                                <td><span class="badge badge-danger">Expired</span></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="content-card mt-2 mb-0">
                        <div class="card-header">
                            <h5><i class="bi bi-pie-chart-fill"></i> OEM Makers Type Chart</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container oem">
                                <canvas id="oemMakersChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="quick-box mt-2">
                        <h6><i class="bi bi-shield-check"></i> Warranty Coverage</h6>
                        <p>FDSS: <?php echo number_format($fdss_under_warranty); ?> | FSDS: <?php echo number_format($FSDS_under_warranty); ?></p>
                    </div>

                    <div class="quick-box">
                        <h6><i class="bi bi-exclamation-triangle"></i> Out of Warranty</h6>
                        <p>FDSS: <?php echo number_format($fdss_out_warranty); ?> | FSDS: <?php echo number_format($FSDS_out_warranty); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
new Chart(document.getElementById('dailyReportChart'), {
    type: 'bar',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [
            {
                label: 'Assigned',
                data: <?php echo json_encode($weekly_assigned); ?>,
                backgroundColor: '#3c8dbc'
            },
            {
                label: 'Completed',
                data: <?php echo json_encode($weekly_completed); ?>,
                backgroundColor: '#6c757d'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('trainStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Active Inventory', 'Expired Inventory', 'Out Warranty'],
        datasets: [{
            data: [
                <?php echo (int)$active_inventory; ?>,
                <?php echo (int)$expired_inventory; ?>,
                <?php echo (int)$total_out_warranty; ?>
            ],
            backgroundColor: ['#3c8dbc', '#dc3545', '#ffc107']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});

new Chart(document.getElementById('inventoryUsageChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($inventory_names); ?>,
        datasets: [{
            label: 'Used Count',
            data: <?php echo json_encode($inventory_usage_counts); ?>,
            backgroundColor: '#3c8dbc'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { legend: { position: 'bottom' } },
        scales: { x: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('oemMakersChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($oem_names); ?>,
        datasets: [{
            data: <?php echo json_encode($oem_counts); ?>,
            backgroundColor: [
                '#3c8dbc',
                '#343a40',
                '#ffc107',
                '#dc3545',
                '#198754',
                '#6f42c1',
                '#0dcaf0',
                '#adb5bd'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<script src="assets/js/layout.js"></script>

</body>
</html>

<?php
$conn->close();
?>
