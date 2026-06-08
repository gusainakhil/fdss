<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$train_info_id = isset($_GET['train_info_id']) ? (int) $_GET['train_info_id'] : 0;
$coach_no = trim($_GET['coach_no'] ?? '');

if ($train_info_id < 0 || $coach_no === '') {
    die("Invalid coach details.");
}

if ($train_info_id <= 0) {
    $train_info_id = null;
}

$message = '';
$message_type = '';
$unit_has_use_status = false;
$unit_has_inventory_parameter_id = false;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$use_status_check = $conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'use_status'");
if ($use_status_check && $use_status_check->num_rows > 0) {
    $unit_has_use_status = true;
}

$unit_status_check = $conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'unit_status'");
$unit_has_unit_status = ($unit_status_check && $unit_status_check->num_rows > 0);

$parameter_column_check = $conn->query("SHOW COLUMNS FROM fdds_inventory_unit LIKE 'inventory_parameter_id'");
if ($parameter_column_check && $parameter_column_check->num_rows > 0) {
    $unit_has_inventory_parameter_id = true;
}

/*
|--------------------------------------------------------------------------
| FETCH COACH DETAILS
|--------------------------------------------------------------------------
*/

$coach_query = "SELECT 
                    c.coach_id,
                    c.coach_no,
                    c.coach_type,
                    c.`Type` AS coach_body_type,
                    c.status,
                    c.train_info_id,
                    t.train_no,
                    t.train_name
                FROM fdss_train_coach c
                LEFT JOIN fdss_train_information t 
                    ON t.train_info_id = c.train_info_id
                WHERE c.coach_no = ?
                AND c.user_id = ?";

if ($train_info_id !== null) {
    $coach_query .= " AND c.train_info_id = ?";
}

$coach_query .= "\n                LIMIT 1";

$stmt = $conn->prepare($coach_query);

if ($train_info_id !== null) {
    $stmt->bind_param("sii", $coach_no, $user_id, $train_info_id);
} else {
    $stmt->bind_param("si", $coach_no, $user_id);
}

$stmt->execute();

$coach_result = $stmt->get_result();

if ($coach_result->num_rows === 0) {
    die("Coach not found.");
}

$coach = $coach_result->fetch_assoc();

$stmt->close();

// Determine inventory category from coach_type (FDSS or FSDS)
$coach_category = strtoupper(trim($coach['coach_type'] ?? ''));
if (!in_array($coach_category, ['FDSS', 'FSDS'], true)) {
    $coach_category = ''; // show all if coach_type doesn't match
}

/*
|--------------------------------------------------------------------------
| FETCH MASTER INVENTORY ITEMS
|--------------------------------------------------------------------------
*/

$master_inventory = [];

$master_query = "SELECT inventory_id, item_code, item_name, category
                 FROM fdss_Inventory_Management
                 WHERE user_id = ?";
if ($coach_category !== '') {
    $master_query .= " AND UPPER(TRIM(category)) = ?";
}
$master_query .= " ORDER BY item_name ASC";

$stmt = $conn->prepare($master_query);
if ($coach_category !== '') {
    $stmt->bind_param("is", $user_id, $coach_category);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();

$master_result = $stmt->get_result();

while ($row = $master_result->fetch_assoc()) {
    $master_inventory[] = $row;
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| FETCH AVAILABLE INVENTORY UNITS
|--------------------------------------------------------------------------
*/

// Fetch available token batches (only token_id NOT NULL = proper FDSS/FSDS items)
$token_batches = [];

$token_q = "SELECT
                iu.token_id,
                iu.serial_number,
                iu.model_number,
                iu.purchase_date,
                COUNT(iu.unit_id) AS unit_count
            FROM fdds_inventory_unit iu
            INNER JOIN fdss_Inventory_Management im
                ON im.inventory_id = iu.inventory_id
                AND im.user_id = iu.user_id
            WHERE iu.user_id = ?
              AND iu.token_id IS NOT NULL
              AND iu.token_id != ''";

if ($coach_category !== '') $token_q .= " AND UPPER(TRIM(im.category)) = ?";
if ($unit_has_use_status)   $token_q .= " AND iu.use_status = 0";
if ($unit_has_unit_status)  $token_q .= " AND iu.unit_status = 'Working'";

$token_q .= " GROUP BY iu.token_id, iu.serial_number, iu.model_number, iu.purchase_date
              ORDER BY iu.token_id ASC";

$t_stmt = $conn->prepare($token_q);
if ($coach_category !== '') {
    $t_stmt->bind_param("is", $user_id, $coach_category);
} else {
    $t_stmt->bind_param("i", $user_id);
}
$t_stmt->execute();
$t_res = $t_stmt->get_result();
while ($row = $t_res->fetch_assoc()) {
    $token_batches[] = $row;
}
$t_stmt->close();

/*
|--------------------------------------------------------------------------
| ADD / UPDATE / DELETE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | ADD INVENTORY
    |--------------------------------------------------------------------------
    */

    if ($action === 'add_inventory') {

        $batch_token = preg_replace('/[^A-Za-z0-9]/', '', trim($_POST['batch_token'] ?? ''));
        $status      = $_POST['status'] ?? 'Active';

        if ($batch_token === '') {
            $message = "Please select a batch token.";
            $message_type = "danger";
        } else {
            // Find all available units for this token
            $find_q = "SELECT iu.unit_id
                        FROM fdds_inventory_unit iu
                        INNER JOIN fdss_Inventory_Management im
                            ON im.inventory_id = iu.inventory_id AND im.user_id = iu.user_id
                        WHERE iu.user_id = ?
                          AND iu.token_id = ?";
            if ($coach_category !== '') $find_q .= " AND UPPER(TRIM(im.category)) = ?";
            if ($unit_has_use_status)   $find_q .= " AND iu.use_status = 0";
            if ($unit_has_unit_status)  $find_q .= " AND iu.unit_status = 'Working'";

            $find_stmt = $conn->prepare($find_q);
            if ($coach_category !== '') {
                $find_stmt->bind_param("iss", $user_id, $batch_token, $coach_category);
            } else {
                $find_stmt->bind_param("is", $user_id, $batch_token);
            }
            $find_stmt->execute();
            $find_res = $find_stmt->get_result();
            $unit_ids = [];
            while ($r = $find_res->fetch_assoc()) {
                $unit_ids[] = (int)$r['unit_id'];
            }
            $find_stmt->close();

            if (empty($unit_ids)) {
                $message = "No available units found for this token. They may already be assigned or not Working.";
                $message_type = "danger";
            } else {
                $conn->begin_transaction();
                try {
                    $ins = $conn->prepare("INSERT INTO fdss_coach_inventory (coach_id, inventory_unit_id, user_id, status) VALUES (?, ?, ?, ?)");
                    $upd = $unit_has_use_status ? $conn->prepare("UPDATE fdds_inventory_unit SET use_status = 1 WHERE unit_id = ? AND user_id = ?") : null;

                    $assigned = 0;
                    foreach ($unit_ids as $uid) {
                        // Skip if already assigned
                        $dup = $conn->prepare("SELECT id FROM fdss_coach_inventory WHERE inventory_unit_id = ? AND user_id = ? LIMIT 1");
                        $dup->bind_param("ii", $uid, $user_id);
                        $dup->execute();
                        if ($dup->get_result()->num_rows > 0) { $dup->close(); continue; }
                        $dup->close();

                        $ins->bind_param("iiis", $coach['coach_id'], $uid, $user_id, $status);
                        if (!$ins->execute()) throw new Exception("Insert failed: " . $ins->error);

                        if ($upd) {
                            $upd->bind_param("ii", $uid, $user_id);
                            $upd->execute();
                        }
                        $assigned++;
                    }
                    $ins->close();
                    if ($upd) $upd->close();

                    $conn->commit();
                    $message = "$assigned unit(s) from token {$batch_token} assigned to coach successfully.";
                    $message_type = "success";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = $e->getMessage();
                    $message_type = "danger";
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE INVENTORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'edit_inventory') {

        $coach_inventory_id = (int) ($_POST['coach_inventory_id'] ?? 0);
        $inventory_unit_id = (int) ($_POST['inventory_unit_id'] ?? 0);
        $status = $_POST['status'] ?? 'Active';

        if ($coach_inventory_id <= 0 || $inventory_unit_id <= 0) {
            $message = "Please select an inventory unit.";
            $message_type = "danger";
        } else {
            $current_unit_id = 0;
            $current_query = "SELECT inventory_unit_id
                              FROM fdss_coach_inventory
                              WHERE id = ?
                              AND coach_id = ?
                              AND user_id = ?
                              LIMIT 1";
            $current_stmt = $conn->prepare($current_query);
            $current_stmt->bind_param("iii", $coach_inventory_id, $coach['coach_id'], $user_id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();

            if ($current_row = $current_result->fetch_assoc()) {
                $current_unit_id = (int) $current_row['inventory_unit_id'];
            }

            $current_stmt->close();

            $duplicate_query = "SELECT id
                                FROM fdss_coach_inventory
                                WHERE inventory_unit_id = ?
                                AND user_id = ?
                                AND id != ?
                                LIMIT 1";
            $duplicate_stmt = $conn->prepare($duplicate_query);
            $duplicate_stmt->bind_param("iii", $inventory_unit_id, $user_id, $coach_inventory_id);
            $duplicate_stmt->execute();
            $duplicate_result = $duplicate_stmt->get_result();

            if ($current_unit_id <= 0) {
                $message = "Inventory assignment not found.";
                $message_type = "danger";
            } elseif ($duplicate_result->num_rows > 0) {
                $message = "This inventory unit is already assigned to a coach.";
                $message_type = "danger";
            } else {
                $can_update = true;

                if ($unit_has_use_status && $inventory_unit_id !== $current_unit_id) {
                    $unit_check_query = "SELECT unit_id
                                         FROM fdds_inventory_unit
                                         WHERE unit_id = ?
                                         AND user_id = ?
                                         AND use_status = 0
                                         LIMIT 1";
                    $unit_check_stmt = $conn->prepare($unit_check_query);
                    $unit_check_stmt->bind_param("ii", $inventory_unit_id, $user_id);
                    $unit_check_stmt->execute();
                    $unit_check_result = $unit_check_stmt->get_result();

                    if ($unit_check_result->num_rows === 0) {
                        $message = "Selected inventory unit is already in use.";
                        $message_type = "danger";
                        $can_update = false;
                        $unit_check_stmt->close();
                    } else {
                        $unit_check_stmt->close();
                    }
                }

                if ($can_update) {
                    $update_query = "UPDATE fdss_coach_inventory SET
                                    inventory_unit_id = ?,
                                    status = ?
                                 WHERE id = ?
                                 AND coach_id = ?
                                 AND user_id = ?";

                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param(
                        "isiii",
                        $inventory_unit_id,
                        $status,
                        $coach_inventory_id,
                        $coach['coach_id'],
                        $user_id
                    );

                    if ($stmt->execute()) {
                        if ($unit_has_use_status && $inventory_unit_id !== $current_unit_id) {
                            $release_query = "UPDATE fdds_inventory_unit SET use_status = 0 WHERE unit_id = ? AND user_id = ?";
                            $release_stmt = $conn->prepare($release_query);
                            $release_stmt->bind_param("ii", $current_unit_id, $user_id);
                            $release_stmt->execute();
                            $release_stmt->close();

                            $use_update_query = "UPDATE fdds_inventory_unit SET use_status = 1 WHERE unit_id = ? AND user_id = ?";
                            $use_update_stmt = $conn->prepare($use_update_query);
                            $use_update_stmt->bind_param("ii", $inventory_unit_id, $user_id);
                            $use_update_stmt->execute();
                            $use_update_stmt->close();
                        }

                        $message = "Inventory assignment updated successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error updating inventory.";
                        $message_type = "danger";
                    }

                    $stmt->close();
                }
            }

            $duplicate_stmt->close();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE INVENTORY
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete_inventory') {

        $coach_inventory_id = (int) ($_POST['coach_inventory_id'] ?? 0);
        $deleted_unit_id = 0;

        if ($unit_has_use_status) {
            $deleted_unit_query = "SELECT inventory_unit_id
                                   FROM fdss_coach_inventory
                                   WHERE id = ?
                                   AND coach_id = ?
                                   AND user_id = ?
                                   LIMIT 1";
            $deleted_unit_stmt = $conn->prepare($deleted_unit_query);
            $deleted_unit_stmt->bind_param("iii", $coach_inventory_id, $coach['coach_id'], $user_id);
            $deleted_unit_stmt->execute();
            $deleted_unit_result = $deleted_unit_stmt->get_result();

            if ($deleted_unit_row = $deleted_unit_result->fetch_assoc()) {
                $deleted_unit_id = (int) $deleted_unit_row['inventory_unit_id'];
            }

            $deleted_unit_stmt->close();
        }

        $delete_query = "DELETE FROM fdss_coach_inventory
                         WHERE id = ?
                         AND coach_id = ?
                         AND user_id = ?";

        $stmt = $conn->prepare($delete_query);

        $stmt->bind_param("iii", $coach_inventory_id, $coach['coach_id'], $user_id);

        if ($stmt->execute()) {
            if ($unit_has_use_status && $deleted_unit_id > 0) {
                $release_query = "UPDATE fdds_inventory_unit SET use_status = 0 WHERE unit_id = ? AND user_id = ?";
                $release_stmt = $conn->prepare($release_query);
                $release_stmt->bind_param("ii", $deleted_unit_id, $user_id);
                $release_stmt->execute();
                $release_stmt->close();
            }

            $message = "Inventory deleted successfully!";
            $message_type = "success";

        } else {

            $message = "Error deleting inventory.";
            $message_type = "danger";
        }

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| REFRESH AVAILABLE INVENTORY UNITS AFTER CHANGES
|--------------------------------------------------------------------------
*/

// Refresh token batches after POST changes
$token_batches = [];
$t_stmt2 = $conn->prepare($token_q);
if ($coach_category !== '') {
    $t_stmt2->bind_param("is", $user_id, $coach_category);
} else {
    $t_stmt2->bind_param("i", $user_id);
}
$t_stmt2->execute();
$t_res2 = $t_stmt2->get_result();
while ($row = $t_res2->fetch_assoc()) {
    $token_batches[] = $row;
}
$t_stmt2->close();

/*
|--------------------------------------------------------------------------
| FETCH INVENTORY ITEMS
|--------------------------------------------------------------------------
*/

$inventory_items = [];

$list_query = "SELECT 
                    ci.id AS coach_inventory_id,
                    ci.inventory_unit_id,
                    ci.status,
                    ci.created_at,
                    ci.updated_at,
                    iu.inventory_id,
                    iu.serial_number,
                    iu.model_number,
                    iu.purchase_date,
                    iu.Warranty_expire,
                    iu.notes,
                    im.item_name AS tool_name,
                    m.company_name
               FROM fdss_coach_inventory ci
               INNER JOIN fdds_inventory_unit iu 
                    ON iu.unit_id = ci.inventory_unit_id
               INNER JOIN fdss_Inventory_Management im
                    ON im.inventory_id = iu.inventory_id
               LEFT JOIN fdss_manufacturers m 
                    ON m.manufacturer_id = iu.manufacturer_id
               WHERE ci.coach_id = ?
               AND ci.user_id = ?";

if ($coach_category !== '') {
    $list_query .= " AND UPPER(TRIM(im.category)) = ?";
}
$list_query .= "\n               ORDER BY ci.id DESC";

$stmt = $conn->prepare($list_query);

if (!$stmt) {
    die("Inventory List SQL Error: " . $conn->error);
}

if ($coach_category !== '') {
    $stmt->bind_param("iis", $coach['coach_id'], $user_id, $coach_category);
} else {
    $stmt->bind_param("ii", $coach['coach_id'], $user_id);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $inventory_items[] = $row;
}

$stmt->close();

$schedule_rows = [];
$schedule_summary = [
    'total' => 0,
    'pending' => 0,
    'assigned' => 0,
    'completed' => 0,
];

$schedule_has_coach_id = false;
$schedule_has_coach_no = false;
$schedule_has_inspection_type = false;
$schedule_has_special_remarks = false;

$coach_id_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'coach_id'");
if ($coach_id_column_check && $coach_id_column_check->num_rows > 0) {
    $schedule_has_coach_id = true;
}

$coach_no_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'coach_no'");
if ($coach_no_column_check && $coach_no_column_check->num_rows > 0) {
    $schedule_has_coach_no = true;
}

$inspection_type_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'Inspection_Type'");
if ($inspection_type_column_check && $inspection_type_column_check->num_rows > 0) {
    $schedule_has_inspection_type = true;
}

$special_remarks_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'special_remarks'");
if ($special_remarks_column_check && $special_remarks_column_check->num_rows > 0) {
    $schedule_has_special_remarks = true;
}

$schedule_select_columns = [
    's.schedule_id',
    $schedule_has_coach_id ? 's.coach_id' : 'NULL AS coach_id',
    $schedule_has_coach_no ? 'COALESCE(s.coach_no, c.coach_no) AS coach_no' : 'c.coach_no AS coach_no',
    's.assignment_date_time',
    's.last_inspection_date',
    's.status',
];

if ($schedule_has_inspection_type) {
    $schedule_select_columns[] = 's.Inspection_Type';
} else {
    $schedule_select_columns[] = 'NULL AS Inspection_Type';
}

if ($schedule_has_special_remarks) {
    $schedule_select_columns[] = 's.special_remarks';
} else {
    $schedule_select_columns[] = 'NULL AS special_remarks';
}

$schedule_select_columns[] = 'COALESCE(t.train_no, "") AS train_no';
$schedule_select_columns[] = 'COALESCE(t.train_name, "") AS train_name';

$schedule_query = "SELECT\n                    " . implode(",\n                    ", $schedule_select_columns) . "\n               FROM fdss_coach_schedule s\n               LEFT JOIN fdss_train_coach c\n                    ON c.coach_id = s.coach_id\n                    AND c.user_id = s.user_id\n               LEFT JOIN fdss_train_information t\n                    ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)\n               WHERE s.user_id = ?";

$summary_query = "SELECT\n                    COUNT(*) AS total,\n                    SUM(CASE WHEN s.status = 'Pending' THEN 1 ELSE 0 END) AS pending,\n                    SUM(CASE WHEN s.status = 'Assigned' THEN 1 ELSE 0 END) AS assigned,\n                    SUM(CASE WHEN s.status = 'Completed' THEN 1 ELSE 0 END) AS completed\n               FROM fdss_coach_schedule s\n               LEFT JOIN fdss_train_coach c\n                    ON c.coach_id = s.coach_id\n                    AND c.user_id = s.user_id\n               WHERE s.user_id = ?";

if ($schedule_has_coach_id && $schedule_has_coach_no) {
    $schedule_query .= " AND (s.coach_id = ? OR s.coach_no = ?)";
    $summary_query .= " AND (s.coach_id = ? OR s.coach_no = ?)";
} elseif ($schedule_has_coach_id) {
    $schedule_query .= " AND s.coach_id = ?";
    $summary_query .= " AND s.coach_id = ?";
} elseif ($schedule_has_coach_no) {
    $schedule_query .= " AND s.coach_no = ?";
    $summary_query .= " AND s.coach_no = ?";
} else {
    $schedule_query .= " AND 0";
    $summary_query .= " AND 0";
}

$schedule_stmt = $conn->prepare($schedule_query);
$summary_stmt = $conn->prepare($summary_query);

if ($schedule_stmt) {
    if ($schedule_has_coach_id && $schedule_has_coach_no) {
        $schedule_stmt->bind_param("iis", $user_id, $coach['coach_id'], $coach['coach_no']);
    } elseif ($schedule_has_coach_id) {
        $schedule_stmt->bind_param("ii", $user_id, $coach['coach_id']);
    } elseif ($schedule_has_coach_no) {
        $schedule_stmt->bind_param("is", $user_id, $coach['coach_no']);
    } else {
        $schedule_stmt->bind_param("i", $user_id);
    }
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();
    while ($row = $schedule_result->fetch_assoc()) {
        $schedule_rows[] = $row;
    }
    $schedule_stmt->close();
}

if ($summary_stmt) {
    if ($schedule_has_coach_id && $schedule_has_coach_no) {
        $summary_stmt->bind_param("iis", $user_id, $coach['coach_id'], $coach['coach_no']);
    } elseif ($schedule_has_coach_id) {
        $summary_stmt->bind_param("ii", $user_id, $coach['coach_id']);
    } elseif ($schedule_has_coach_no) {
        $summary_stmt->bind_param("is", $user_id, $coach['coach_no']);
    } else {
        $summary_stmt->bind_param("i", $user_id);
    }
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();
    if ($summary_row = $summary_result->fetch_assoc()) {
        $schedule_summary = [
            'total' => (int) $summary_row['total'],
            'pending' => (int) $summary_row['pending'],
            'assigned' => (int) $summary_row['assigned'],
            'completed' => (int) $summary_row['completed'],
        ];
    }
    $summary_stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Coach Inventory Details - FDSS Dashboard
    </title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link href="assets/css/styles.css"
          rel="stylesheet">

</head>

<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">

<?php include('includes/sidebar.php'); ?>

<main class="main-content">

    <div class="page-header">

        <div>

            <h1>
                Coach Inventory Detail
                <?php if ($coach_category !== ''): ?>
                    <span class="badge <?= $coach_category === 'FDSS' ? 'bg-danger' : 'bg-info text-dark' ?> ms-2" style="font-size:0.6em;vertical-align:middle"><?= e($coach_category) ?></span>
                <?php endif; ?>
            </h1>

            <p class="page-header-subtitle">
                <?= $coach_category !== '' ? e($coach_category) . ' inventory' : 'Inventory' ?> for coach <?= e($coach['coach_no']) ?>
            </p>

        </div>

        <div class="page-header-actions">

            <button class="btn btn-primary"
                    id="addInventoryBtn"
                    data-bs-toggle="modal"
                    data-bs-target="#inventoryModal">

                <i class="bi bi-plus-circle"></i>
                Add <?= $coach_category !== '' ? e($coach_category) : 'FDSS/FSDS' ?>

            </button>

            <a class="btn btn-secondary"
               href="reports.php?coach_id=<?php echo e($coach['coach_id']); ?>&date=<?php echo e(date('Y-m-d')); ?>">

                <i class="bi bi-eye"></i>
                View Reports

            </a>

            <a class="btn btn-primary"
               href="coaches.php">

                <i class="bi bi-arrow-left"></i>
                Back To Coaches

            </a>

        </div>

    </div>

    <?php if ($message): ?>

        <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show">

            <?php echo e($message); ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>

        </div>

    <?php endif; ?>

    <div class="content-card">

        <div class="card-header">
            <h5>Coach Detail Summary</h5>
        </div>

        <div class="card-body">

            <div class="row g-3">

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Coach Number</h6>

                        <p>
                            <strong>
                                <?php echo e($coach['coach_no']); ?> - <?php echo e($coach['coach_body_type'] ?: '-'); ?>
                            </strong>
                        </p>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Assigned Train</h6>

                        <p>

                            <strong>

                                <?php if (!empty($coach['train_info_id']) && !empty($coach['train_no'])): ?>
                                    <?php echo e($coach['train_no']); ?> - <?php echo e($coach['train_name']); ?>
                                <?php else: ?>
                                    Detached
                                <?php endif; ?>

                            </strong>

                        </p>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Equipment Type</h6>

                        <p>
                            <strong>
                                <?php echo e($coach['coach_type']); ?>
                            </strong>
                        </p>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6">

                    <div class="quick-box">

                        <h6>Overall FDSS Status</h6>

                        <p>

                            <span class="badge <?php echo ($coach['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">

                                <?php echo e($coach['status']); ?>

                            </span>

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="content-card">

        <div class="card-header">

            <h5>

                Coach Components Installed In

                <?php echo e($coach['coach_no']); ?> - <?php echo e($coach['coach_body_type'] ?: '-'); ?>

            </h5>

        </div>

        <div class="card-body">

            <div class="table-wrapper">

                <table class="table table-hover">

                    <thead>

                    <tr> 

                        <th>Inventory Name</th>
                        <th>OEM</th>
                        <th>Model Number</th>
                        <th>Serial Number</th>
                        <th>Assigned Coach</th>
                        <th>Purchase Date</th>
                        <th>Warranty Expire</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <!-- <th>Actions</th> -->

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($inventory_items)): ?>

                        <tr>

                            <td colspan="10"
                                class="text-center text-muted py-4">

                                No inventory found for this coach.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($inventory_items as $item): ?>

                            <tr>

                                <td>
                                    <?php echo e($item['tool_name']); ?>
                                </td>

                                <td>
                                    <?php echo e($item['company_name'] ?? '-'); ?>
                                </td>

                                <td>
                                    <?php echo e($item['model_number']); ?>
                                </td>

                                <td>
                                    <?php echo e($item['serial_number']); ?>
                                </td>

                                <td>

                                    <?php echo e($coach['coach_no']); ?>

                                    <?php if (!empty($coach['train_info_id']) && !empty($coach['train_no'])): ?>
                                        (Train: <?php echo e($coach['train_no']); ?>)
                                    <?php else: ?>
                                        (Detached)
                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php echo $item['purchase_date']
                                        ? date('d M Y', strtotime($item['purchase_date']))
                                        : '-'; ?>

                                </td>

                                <td>

                                    <?php echo $item['Warranty_expire']
                                        ? date('d M Y', strtotime($item['Warranty_expire']))
                                        : '-'; ?>

                                </td>

                                <td>
                                    <?php echo e($item['notes'] ?: '-'); ?>
                                </td>

                                <td>

                                    <span class="badge <?php echo ($item['status'] === 'Inactive') ? 'badge-danger' : 'badge-success'; ?>">

                                        <?php echo e($item['status']); ?>

                                    </span>

                                </td>

                                <!-- <td>

                                    <?php
                                        $info = $item['tool_name'];
                                        if (!empty($item['serial_number'])) $info .= ' | SN: ' . $item['serial_number'];
                                        if (!empty($item['model_number']))  $info .= ' | Model: ' . $item['model_number'];
                                    ?>
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editInventory(
                                            '<?php echo e($item['coach_inventory_id']); ?>',
                                            '<?php echo e($item['inventory_id']); ?>',
                                            '<?php echo e($item['inventory_unit_id']); ?>',
                                            '<?php echo e($item['status']); ?>',
                                            '<?php echo e(addslashes($info)); ?>'
                                        )"
                                        data-bs-toggle="modal"
                                        data-bs-target="#inventoryModal">

                                        <i class="bi bi-pencil"></i>

                                    </button> -->
<!-- 
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="deleteInventory('<?php echo e($item['coach_inventory_id']); ?>')">

                                        <i class="bi bi-trash"></i>

                                    </button> -->

                                <!-- </td> -->

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div class="content-card">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <h5>Coach Schedule Details</h5>

                <a class="btn btn-outline-primary btn-sm"
                   href="reports.php?coach_id=<?php echo e($coach['coach_id']); ?>&date=<?php echo e(date('Y-m-d')); ?>">
                    <i class="bi bi-eye"></i>
                    View Reports
                </a>

            </div>

        </div>

        <div class="card-body">

            <div class="row g-3 mb-3">

                <div class="col-md-3 col-sm-6">
                    <div class="quick-box">
                        <h6>Total Schedules</h6>
                        <p><strong><?php echo number_format($schedule_summary['total']); ?></strong></p>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="quick-box">
                        <h6>Completed</h6>
                        <p><strong><?php echo number_format($schedule_summary['completed']); ?></strong></p>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="quick-box">
                        <h6>Assigned</h6>
                        <p><strong><?php echo number_format($schedule_summary['assigned']); ?></strong></p>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="quick-box">
                        <h6>Pending</h6>
                        <p><strong><?php echo number_format($schedule_summary['pending']); ?></strong></p>
                    </div>
                </div>

            </div>

            <div class="table-wrapper">

                <table class="table table-hover">

                    <thead>

                    <tr>
                        <th>#</th>
                        <th>Schedule ID</th>
                        <th>Train</th>
                        <th>Assignment Date</th>
                        <th>Last Inspection</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($schedule_rows)): ?>

                        <tr>

                            <td colspan="9" class="text-center text-muted py-4">

                                No schedule records found for this coach.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($schedule_rows as $index => $schedule): ?>

                            <tr>

                                <td><?php echo e($index + 1); ?></td>

                                <td><?php echo e($schedule['schedule_id']); ?></td>

                                <td>
                                    <?php echo e($schedule['train_no'] ?: '-'); ?>
                                    <?php if ($schedule['train_name']): ?>
                                        - <?php echo e($schedule['train_name']); ?>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo $schedule['assignment_date_time'] ? date('d M Y h:i A', strtotime($schedule['assignment_date_time'])) : '-'; ?>
                                </td>

                                <td>
                                    <?php echo $schedule['last_inspection_date'] ? date('d M Y', strtotime($schedule['last_inspection_date'])) : '-'; ?>
                                </td>

                                <td>
                                    <span class="badge <?php echo ($schedule['status'] === 'Completed') ? 'badge-success' : (($schedule['status'] === 'Assigned') ? 'badge-info' : 'badge-warning'); ?>">
                                        <?php echo e($schedule['status'] ?: '-'); ?>
                                    </span>
                                </td>

                                <td><?php echo e($schedule['Inspection_Type'] ?: '-'); ?></td>

                                <td><?php echo e($schedule['special_remarks'] ?: '-'); ?></td>

                                <td>
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="reports.php?coach_id=<?php echo e($coach['coach_id']); ?>&date=<?php echo e(date('Y-m-d')); ?>">
                                        <i class="bi bi-arrow-right-circle"></i>
                                        View Schedule
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

</main>

</div>

<div class="modal fade"
     id="inventoryModal"
     tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title"
                    id="inventoryModalTitle">

                    Add Coach Component 

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>

            </div>

            <form method="POST">

                <input type="hidden"
                       name="action"
                       id="formAction"
                       value="add_inventory">

                <input type="hidden"
                       name="coach_inventory_id"
                       id="coachInventoryId">

                <div class="modal-body">

                    <!-- ADD mode: token batch selector -->
                    <div id="addTokenField">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Select <?= $coach_category !== '' ? e($coach_category) : 'FDSS/FSDS' ?> Batch (Token)
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="batchTokenSelect" name="batch_token">
                                <option value="">— Select Token —</option>
                                <?php foreach ($token_batches as $tb): ?>
                                    <?php
                                        $lbl  = 'Token: ' . $tb['token_id'];
                                        $lbl .= ' | ' . $tb['unit_count'] . ' item(s)';
                                        $lbl .= $tb['serial_number'] ? ' | SN: ' . $tb['serial_number'] : '';
                                        $lbl .= $tb['model_number']  ? ' | Model: ' . $tb['model_number'] : '';
                                        $lbl .= $tb['purchase_date'] ? ' | Purchased: ' . $tb['purchase_date'] : '';
                                    ?>
                                    <option value="<?= e($tb['token_id']) ?>"><?= e($lbl) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($token_batches)): ?>
                                <div class="form-text text-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    No available <?= e($coach_category ?: 'FDSS/FSDS') ?> batches (unit_status=Working, use_status=0).
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- EDIT mode: show current assignment info (read-only) -->
                    <div id="editUnitInfo" style="display:none">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assignment</label>
                            <p id="editUnitInfoText" class="form-control-plaintext border rounded px-3 py-2 bg-light small"></p>
                            <input type="hidden" name="inventory_unit_id" id="editUnitIdHidden">
                        </div>
                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select class="form-select"
                                id="inventoryStatus"
                                name="status">

                            <option value="Active">
                                Active
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="inventorySubmitBtn">

                        Save

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<form method="POST"
      id="deleteForm"
      style="display:none;">

    <input type="hidden"
           name="action"
           value="delete_inventory">

    <input type="hidden"
           name="coach_inventory_id"
           id="deleteInventoryId">

</form>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/layout.js"></script>

<script>

const inventoryModalTitle = document.getElementById('inventoryModalTitle');
const inventorySubmitBtn  = document.getElementById('inventorySubmitBtn');
const addTokenField       = document.getElementById('addTokenField');
const editUnitInfo        = document.getElementById('editUnitInfo');
const editUnitInfoText    = document.getElementById('editUnitInfoText');
const catLabel            = '<?= $coach_category !== '' ? e($coach_category) : 'FDSS/FSDS' ?>';

function resetInventoryForm() {
    document.getElementById('coachInventoryId').value = '';
    document.getElementById('editUnitIdHidden').value = '';
    document.getElementById('batchTokenSelect').value = '';
    document.getElementById('inventoryStatus').value  = 'Active';
    document.getElementById('formAction').value       = 'add_inventory';
    addTokenField.style.display = '';
    editUnitInfo.style.display  = 'none';
    document.getElementById('batchTokenSelect').required = true;
    inventoryModalTitle.textContent = 'Add ' + catLabel + ' Batch';
    inventorySubmitBtn.textContent  = 'Assign All Units';
}

function editInventory(id, inventoryId, unitId, status, infoText) {
    document.getElementById('coachInventoryId').value  = id;
    document.getElementById('editUnitIdHidden').value  = unitId;
    document.getElementById('inventoryStatus').value   = status;
    document.getElementById('formAction').value        = 'edit_inventory';
    editUnitInfoText.textContent                       = infoText || ('Unit ID: ' + unitId);
    addTokenField.style.display = 'none';
    editUnitInfo.style.display  = '';
    document.getElementById('batchTokenSelect').required = false;
    inventoryModalTitle.textContent = 'Edit Assignment';
    inventorySubmitBtn.textContent  = 'Update';
}

function deleteInventory(id) {
    if (confirm('Remove this assignment from the coach?')) {
        document.getElementById('deleteInventoryId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('addInventoryBtn')
    .addEventListener('click', resetInventoryForm);

document.getElementById('inventoryModal')
    .addEventListener('hidden.bs.modal', resetInventoryForm);

</script>

</body>
</html>

<?php
$conn->close();
?>
