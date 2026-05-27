<?php
session_start();
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

$message = '';
$message_type = '';

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

$today = date('Y-m-d');
$advance_cutoff_date = date('Y-m-d', strtotime('+3 days'));

$selected_train = $_GET['train_info_id'] ?? 'all';
$selected_date = $advance_cutoff_date;

$schedule_uses_coach_id = false;
$schedule_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'coach_id'");

if ($schedule_column_check && $schedule_column_check->num_rows > 0) {
    $schedule_uses_coach_id = true;
}

$schedule_has_inspection_type = false;
$inspection_type_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'Inspection_Type'");

if ($inspection_type_column_check && $inspection_type_column_check->num_rows > 0) {
    $schedule_has_inspection_type = true;
}

/*
|--------------------------------------------------------------------------
| CREATE SCHEDULE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'create_schedule') {

        $coach_id = (int) ($_POST['coach_id'] ?? 0);
        $train_info_id = ($_POST['train_info_id'] ?? '') !== ''
            ? (int) $_POST['train_info_id']
            : null;
        $auditor_id = (int) ($_POST['auditor_id'] ?? 0);
        $assignment_date_time = trim($_POST['assignment_date_time'] ?? '');
        $priority = 'Normal';
        $special_remarks = '';
        $inspection_type = $_POST['inspection_type'] ?? '1_month';
        $inspection_day_map = [
            '1_month' => 30,
            '3_month' => 30,
            '6_month' => 30,
        ];

        if ($assignment_date_time !== '') {
            $assignment_date_time = str_replace('T', ' ', $assignment_date_time);

            if (strlen($assignment_date_time) === 16) {
                $assignment_date_time .= ':00';
            }
        }

        $last_inspection_date = null;

        $next_due_date = null;

        if ($assignment_date_time !== '' && isset($inspection_day_map[$inspection_type])) {
            $assignment_date = new DateTime(substr($assignment_date_time, 0, 10));
            $assignment_date->modify('+' . $inspection_day_map[$inspection_type] . ' days');
            $next_due_date = $assignment_date->format('Y-m-d');
        }

        if (
            $coach_id <= 0 ||
            $auditor_id <= 0 ||
            $assignment_date_time === '' ||
            $next_due_date === null
        ) {
            $message = "Please fill all required fields.";
            $message_type = "danger";
        } else {

            $coach_due_query = "SELECT coach_id, coach_no, next_inspection_date
                                FROM fdss_train_coach
                                WHERE user_id = ?
                                AND coach_id = ?
                                AND schedule_status = 0
                                AND next_inspection_date IS NOT NULL
                                AND next_inspection_date <= ?
                                LIMIT 1";

            $coach_due_stmt = $conn->prepare($coach_due_query);

            if (!$coach_due_stmt) {
                $message = "Coach Due Check SQL Error: " . $conn->error;
                $message_type = "danger";
            } else {
                $coach_due_stmt->bind_param(
                    "iis",
                    $user_id,
                    $coach_id,
                    $advance_cutoff_date
                );

                $coach_due_stmt->execute();
                $coach_due_result = $coach_due_stmt->get_result();

                if ($coach_due_result->num_rows === 0) {
                    $message = "Only overdue coaches or coaches due within the next 3 days can be scheduled.";
                    $message_type = "danger";
                    $coach_due_stmt->close();
                } else {
                    $coach_due = $coach_due_result->fetch_assoc();
                    $coach_no_for_schedule = $coach_due['coach_no'];
                    $current_inspection_date = $coach_due['next_inspection_date'];
                    $coach_due_stmt->close();

            if (empty($next_due_date) || $next_due_date <= $current_inspection_date) {
                $message = "Next Due Date must be greater than current inspection date.";
                $message_type = "danger";
            } else {

            $schedule_coach_column = $schedule_uses_coach_id ? 'coach_id' : 'coach_no';
            $inspection_type_column = $schedule_has_inspection_type ? "\n                Inspection_Type," : '';
            $inspection_type_placeholder = $schedule_has_inspection_type ? ', ?' : '';

            $insert_query = "INSERT INTO fdss_coach_schedule
            (
                $schedule_coach_column,
                train_info_id,
                last_inspection_date,
                next_due_date,
                status,
                auditor_id,
                assignment_date_time,
                priority,
                special_remarks,
                $inspection_type_column
                user_id
            )
            VALUES
            (
                ?, ?, ?, ?, 'Assigned', ?, ?, ?, ?$inspection_type_placeholder, ?
            )";

            $stmt = $conn->prepare($insert_query);

            if (!$stmt) {
                $message = "Schedule Insert SQL Error: " . $conn->error;
                $message_type = "danger";
            } else {

                if ($schedule_uses_coach_id && $schedule_has_inspection_type) {
                    $stmt->bind_param(
                        "iississssi",
                        $coach_id,
                        $train_info_id,
                        $last_inspection_date,
                        $next_due_date,
                        $auditor_id,
                        $assignment_date_time,
                        $priority,
                        $special_remarks,
                        $inspection_type,
                        $user_id
                    );
                } elseif ($schedule_uses_coach_id) {
                    $stmt->bind_param(
                        "iississsi",
                        $coach_id,
                        $train_info_id,
                        $last_inspection_date,
                        $next_due_date,
                        $auditor_id,
                        $assignment_date_time,
                        $priority,
                        $special_remarks,
                        $user_id
                    );
                } elseif ($schedule_has_inspection_type) {
                    $stmt->bind_param(
                        "sississssi",
                        $coach_no_for_schedule,
                        $train_info_id,
                        $last_inspection_date,
                        $next_due_date,
                        $auditor_id,
                        $assignment_date_time,
                        $priority,
                        $special_remarks,
                        $inspection_type,
                        $user_id
                    );
                } else {
                    $stmt->bind_param(
                        "sississsi",
                        $coach_no_for_schedule,
                        $train_info_id,
                        $last_inspection_date,
                        $next_due_date,
                        $auditor_id,
                        $assignment_date_time,
                        $priority,
                        $special_remarks,
                        $user_id
                    );
                }

                if ($stmt->execute()) {

	                    $updateCoachQuery = "UPDATE fdss_train_coach
	                                         SET schedule_status = 1,
	                                             next_inspection_date = ?
	                                         WHERE coach_id = ?
	                                         AND user_id = ?";

                    $updateStmt = $conn->prepare($updateCoachQuery);

                    if ($updateStmt) {

	                        $updateStmt->bind_param(
	                            "sii",
	                            $next_due_date,
	                            $coach_id,
	                            $user_id
	                        );

                        if ($updateStmt->execute()) {

                            if ($updateStmt->affected_rows > 0) {
                                $_SESSION['flash_message'] = "Inspection schedule created successfully!";
                                $_SESSION['flash_type'] = "success";
                            } else {
                                $_SESSION['flash_message'] = "Schedule created, but coach status was not updated. Please check coach_id or user_id.";
                                $_SESSION['flash_type'] = "warning";
                            }

                        } else {
                            $_SESSION['flash_message'] = "Schedule created, but coach status update failed: " . $updateStmt->error;
                            $_SESSION['flash_type'] = "warning";
                        }

                        $updateStmt->close();

                    } else {
                        $_SESSION['flash_message'] = "Schedule created, but coach update SQL failed: " . $conn->error;
                        $_SESSION['flash_type'] = "warning";
                    }

                    $stmt->close();

                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;

                } else {
                    $message = "Error creating schedule: " . $stmt->error;
                    $message_type = "danger";
                }

                $stmt->close();
            }
            }
                }
            }
        }
    }

    if ($action === 'create_round_trip_schedule') {

        $coach_id = (int) ($_POST['round_trip_coach_id'] ?? 0);
        $round_trip_train_value = $_POST['round_trip_train_info_id'] ?? '';
        $is_detached_round_trip = $round_trip_train_value === 'detached';
        $train_info_id = (!$is_detached_round_trip && $round_trip_train_value !== '')
            ? (int) $round_trip_train_value
            : null;
        $auditor_id = (int) ($_POST['round_trip_auditor_id'] ?? 0);
        $assignment_date_time = trim($_POST['round_trip_assignment_date_time'] ?? '');
        $priority = 'Normal';
        $special_remarks = '';
        $inspection_type = 'Round Trip';

        if ($assignment_date_time !== '') {
            $assignment_date_time = str_replace('T', ' ', $assignment_date_time);

            if (strlen($assignment_date_time) === 16) {
                $assignment_date_time .= ':00';
            }
        }

        $last_inspection_date = null;
        $next_due_date = $assignment_date_time !== ''
            ? substr($assignment_date_time, 0, 10)
            : null;

        if (
            $coach_id <= 0 ||
            (!$is_detached_round_trip && $train_info_id === null) ||
            $auditor_id <= 0 ||
            $assignment_date_time === '' ||
            $next_due_date === null
        ) {
            $message = "Please fill all round trip schedule fields.";
            $message_type = "danger";
        } else {

            if ($is_detached_round_trip) {
                $coach_query = "SELECT coach_id, coach_no
                                FROM fdss_train_coach
                                WHERE user_id = ?
                                AND coach_id = ?
                                AND status = 'Active'
                                AND (train_info_id IS NULL OR train_info_id = '' OR coach_status = 'Detached')
                                LIMIT 1";
            } else {
                $coach_query = "SELECT coach_id, coach_no
                                FROM fdss_train_coach
                                WHERE user_id = ?
                                AND coach_id = ?
                                AND train_info_id = ?
                                AND status = 'Active'
                                LIMIT 1";
            }

            $coach_stmt = $conn->prepare($coach_query);

            if (!$coach_stmt) {
                $message = "Round Trip Coach Check SQL Error: " . $conn->error;
                $message_type = "danger";
            } else {
                if ($is_detached_round_trip) {
                    $coach_stmt->bind_param(
                        "ii",
                        $user_id,
                        $coach_id
                    );
                } else {
                    $coach_stmt->bind_param(
                        "iii",
                        $user_id,
                        $coach_id,
                        $train_info_id
                    );
                }

                $coach_stmt->execute();
                $coach_result = $coach_stmt->get_result();

                if ($coach_result->num_rows === 0) {
                    $message = "Please select a valid active coach for the selected train.";
                    $message_type = "danger";
                    $coach_stmt->close();
                } else {
                    $coach = $coach_result->fetch_assoc();
                    $coach_no_for_schedule = $coach['coach_no'];
                    $coach_stmt->close();

                    $schedule_coach_column = $schedule_uses_coach_id ? 'coach_id' : 'coach_no';
                    $inspection_type_column = $schedule_has_inspection_type ? "\n                Inspection_Type," : '';
                    $inspection_type_placeholder = $schedule_has_inspection_type ? ', ?' : '';

                    $insert_query = "INSERT INTO fdss_coach_schedule
                    (
                        $schedule_coach_column,
                        train_info_id,
                        last_inspection_date,
                        next_due_date,
                        status,
                        auditor_id,
                        assignment_date_time,
                        priority,
                        special_remarks,
                        $inspection_type_column
                        user_id
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, 'Assigned', ?, ?, ?, ?$inspection_type_placeholder, ?
                    )";

                    $stmt = $conn->prepare($insert_query);

                    if (!$stmt) {
                        $message = "Round Trip Schedule Insert SQL Error: " . $conn->error;
                        $message_type = "danger";
                    } else {
                        if ($schedule_uses_coach_id && $schedule_has_inspection_type) {
                            $stmt->bind_param(
                                "iississssi",
                                $coach_id,
                                $train_info_id,
                                $last_inspection_date,
                                $next_due_date,
                                $auditor_id,
                                $assignment_date_time,
                                $priority,
                                $special_remarks,
                                $inspection_type,
                                $user_id
                            );
                        } elseif ($schedule_uses_coach_id) {
                            $stmt->bind_param(
                                "iississsi",
                                $coach_id,
                                $train_info_id,
                                $last_inspection_date,
                                $next_due_date,
                                $auditor_id,
                                $assignment_date_time,
                                $priority,
                                $special_remarks,
                                $user_id
                            );
                        } elseif ($schedule_has_inspection_type) {
                            $stmt->bind_param(
                                "sississssi",
                                $coach_no_for_schedule,
                                $train_info_id,
                                $last_inspection_date,
                                $next_due_date,
                                $auditor_id,
                                $assignment_date_time,
                                $priority,
                                $special_remarks,
                                $inspection_type,
                                $user_id
                            );
                        } else {
                            $stmt->bind_param(
                                "sississsi",
                                $coach_no_for_schedule,
                                $train_info_id,
                                $last_inspection_date,
                                $next_due_date,
                                $auditor_id,
                                $assignment_date_time,
                                $priority,
                                $special_remarks,
                                $user_id
                            );
                        }

                        if ($stmt->execute()) {
                            $_SESSION['flash_message'] = "Round Trip schedule created successfully!";
                            $_SESSION['flash_type'] = "success";
                            $stmt->close();

                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit;
                        }

                        $message = "Error creating round trip schedule: " . $stmt->error;
                        $message_type = "danger";
                        $stmt->close();
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH TRAINS
|--------------------------------------------------------------------------
*/

$trains = [];

$train_query = "SELECT 
                    train_info_id,
                    train_no,
                    train_name
                FROM fdss_train_information
                WHERE user_id = ?
                AND status = 'Active'
                ORDER BY train_no ASC";

$stmt = $conn->prepare($train_query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $train_result = $stmt->get_result();

    while ($row = $train_result->fetch_assoc()) {
        $trains[] = $row;
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| FETCH AUDITORS
|--------------------------------------------------------------------------
*/

$auditors = [];

$auditor_query = "SELECT
                    user_id,
                    user_name,
                    full_name
                  FROM fdss_users
                  WHERE role = 'AUDITOR'
                  AND status = 'Active'
                  AND created_by_user_id = ?
                  ORDER BY user_name ASC";

$stmt = $conn->prepare($auditor_query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $auditor_result = $stmt->get_result();

    while ($row = $auditor_result->fetch_assoc()) {
        $auditors[] = $row;
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| FETCH DUE COACHES
|--------------------------------------------------------------------------
*/

$coaches = [];

$query = "SELECT 
            c.coach_id,
            c.coach_no,
            c.coach_type,
            c.next_inspection_date,
            t.train_no,
            t.train_name,
            t.train_info_id
          FROM fdss_train_coach c
          LEFT JOIN fdss_train_information t
            ON t.train_info_id = c.train_info_id
          WHERE c.user_id = ?
          AND c.status = 'Active'
          AND (t.status = 'Active' OR c.train_info_id IS NULL)
          AND c.schedule_status = 0
          AND c.next_inspection_date IS NOT NULL
          AND c.next_inspection_date <= ?";

if ($selected_train !== 'all') {
    $query .= " AND c.train_info_id = ?";
}

$query .= " ORDER BY c.next_inspection_date ASC";

$stmt = $conn->prepare($query);

if ($stmt) {

    if ($selected_train !== 'all') {

        $selected_train_id = (int) $selected_train;

        $stmt->bind_param(
            "isi",
            $user_id,
            $selected_date,
            $selected_train_id
        );

    } else {

        $stmt->bind_param(
            "is",
            $user_id,
            $selected_date
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $coaches[] = $row;
    }

    $stmt->close();

} else {
    $message = "Coach Fetch SQL Error: " . $conn->error;
    $message_type = "danger";
}

if ($schedule_has_inspection_type && !empty($coaches)) {
    foreach ($coaches as $index => $coach) {
        $coaches[$index]['inspection_type_last_dates'] = [
            '1_month' => 'NA',
            '3_month' => 'NA',
            '6_month' => 'NA',
        ];

        if ($schedule_uses_coach_id) {
            $type_date_query = "SELECT Inspection_Type, MAX(DATE(assignment_date_time)) AS last_inspection_date
                                FROM fdss_coach_schedule
                                WHERE user_id = ?
                                AND coach_id = ?
                                AND Inspection_Type IN ('1_month', '2_month', '3_month', '6_month')
                                GROUP BY Inspection_Type";
        } else {
            $type_date_query = "SELECT Inspection_Type, MAX(DATE(assignment_date_time)) AS last_inspection_date
                                FROM fdss_coach_schedule
                                WHERE user_id = ?
                                AND coach_no = ?
                                AND Inspection_Type IN ('1_month', '2_month', '3_month', '6_month')
                                GROUP BY Inspection_Type";
        }

        $type_date_stmt = $conn->prepare($type_date_query);

        if ($type_date_stmt) {
            if ($schedule_uses_coach_id) {
                $type_date_stmt->bind_param("ii", $user_id, $coach['coach_id']);
            } else {
                $type_date_stmt->bind_param("is", $user_id, $coach['coach_no']);
            }

            $type_date_stmt->execute();
            $type_date_result = $type_date_stmt->get_result();

            while ($type_row = $type_date_result->fetch_assoc()) {
                if (!empty($type_row['last_inspection_date'])) {
                    $inspection_type_key = $type_row['Inspection_Type'] === '2_month'
                        ? '6_month'
                        : $type_row['Inspection_Type'];

                    $coaches[$index]['inspection_type_last_dates'][$inspection_type_key] =
                        date('d M Y', strtotime($type_row['last_inspection_date']));
                }
            }

            $type_date_stmt->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH ACTIVE COACHES FOR ROUND TRIP
|--------------------------------------------------------------------------
*/

$round_trip_coaches = [];

$round_trip_coach_query = "SELECT
                            c.coach_id,
                            c.coach_no,
                            c.coach_type,
                            c.train_info_id,
                            c.coach_status
                           FROM fdss_train_coach c
                           LEFT JOIN fdss_train_information t
                            ON t.train_info_id = c.train_info_id
                           WHERE c.user_id = ?
                           AND c.status = 'Active'
                           AND (
                            t.status = 'Active'
                            OR c.train_info_id IS NULL
                            OR c.train_info_id = ''
                            OR c.coach_status = 'Detached'
                           )
                           ORDER BY c.coach_no ASC";

$stmt = $conn->prepare($round_trip_coach_query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $round_trip_coach_result = $stmt->get_result();

    while ($row = $round_trip_coach_result->fetch_assoc()) {
        $round_trip_coaches[] = $row;
    }

    $stmt->close();
} else {
    $message = "Round Trip Coach Fetch SQL Error: " . $conn->error;
    $message_type = "danger";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Inspection Schedule - FDSS Dashboard
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
               Tentative Pending  Schedule
            </h1>

            <p class="page-header-subtitle">
                Create inspection schedules for overdue coaches and coaches due within next 3 days.
            </p>

        </div>

        <button type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#roundTripScheduleModal">

            <i class="bi bi-arrow-repeat"></i>
            Round Trip Schedule

        </button>

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

        <div class="card-body">

            <form method="GET"
                  class="row g-2 align-items-end">

                <div class="col-lg-6 col-md-6">

                    <label class="form-label">
                        Filter by Train
                    </label>

                    <select class="form-select"
                            name="train_info_id">

                        <option value="all">
                            All Trains
                        </option>

                        <?php foreach ($trains as $train): ?>

                            <option value="<?php echo e($train['train_info_id']); ?>"
                                <?php echo ((string)$selected_train === (string)$train['train_info_id']) ? 'selected' : ''; ?>>

                                <?php echo e($train['train_no']); ?>
                                -
                                <?php echo e($train['train_name']); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-lg-3 col-md-6 d-grid">

                    <button class="btn btn-primary"
                            type="submit">

                        <i class="bi bi-search"></i>
                        Search

                    </button>

                </div>

                <div class="col-lg-3 col-md-6 d-grid">

                    <a class="btn btn-outline-secondary"
                       href="<?php echo basename($_SERVER['PHP_SELF']); ?>">

                        <i class="bi bi-arrow-clockwise"></i>
                        Reset

                    </a>

                </div>

            </form>

        </div>

    </div>

    <div class="row">

        <div class="col-lg-12">

            <div class="content-card">

                <div class="card-header">

                    <h5>

                        <i class="bi bi-calendar-check"></i>

                        Upcoming Inspection Due Coaches

                        <small class="text-muted">
                            (Overdue to <?php echo e($selected_date); ?>)
                        </small>

                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-wrapper">

                        <table class="table table-hover">

                            <thead>

                            <tr>

                                <th>Due Date</th>
                                <th>Coach No.</th>
                                <th>Train</th>
                                <th>Coach Type</th>
                                <th>Last Inspection</th>
                                <th>Action</th>

                            </tr>

                            </thead>

                            <tbody>

                            <?php if (empty($coaches)): ?>

                                <tr>

                                    <td colspan="6"
                                        class="text-center text-muted py-4">

                                        No overdue coaches or coaches due within next 3 days.

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($coaches as $coach): ?>

                                    <tr>

                                        <td>

                                            <strong>

                                                <?php echo date('d M Y', strtotime($coach['next_inspection_date'])); ?>

                                            </strong>

                                        </td>

                                        <td>

                                            <strong>

                                                <?php echo e($coach['coach_no']); ?>

                                            </strong>

                                        </td>

                                        <td>

                                            <strong>

                                                <?php echo e($coach['train_no'] ?: 'Detached'); ?>

                                            </strong>

                                            <br>

                                            <?php echo e($coach['train_name'] ?: '-'); ?>

                                        </td>

                                        <td>

                                            <?php echo e($coach['coach_type']); ?>

                                        </td>

                                        <td>

                                            <?php if (!$schedule_has_inspection_type): ?>

                                                <span class="text-muted">-</span>

                                            <?php else: ?>

                                                <?php
                                                $last_dates = $coach['inspection_type_last_dates'] ?? [
                                                    '1_month' => 'NA',
                                                    '3_month' => 'NA',
                                                    '6_month' => 'NA',
                                                ];
                                                ?>

                                                <p class="small mb-0 lh-base">
                                                    <strong>1 month</strong>
                                                    Date:
                                                    <?php echo e($last_dates['1_month']); ?>;
                                                    <strong>3 month</strong>
                                                    Date:
                                                    <?php echo e($last_dates['3_month']); ?>;
                                                    <strong>6 month</strong>
                                                    Date:
                                                    <?php echo e($last_dates['6_month']); ?>
                                                </p>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
	                                                onclick="openScheduleModal(
                                                    '<?php echo e($coach['coach_id']); ?>',
	                                                    '<?php echo e($coach['train_info_id']); ?>',
	                                                    '<?php echo e($coach['coach_no']); ?>',
	                                                    '<?php echo e($coach['next_inspection_date']); ?>'
                                                )"
                                                data-bs-toggle="modal"
                                                data-bs-target="#scheduleModal">

                                                Assign Auditor

                                            </button>

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

    </div>

</main>

</div>

<div class="modal fade"
     id="scheduleModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header text-white" style="background-color: #61a2cb;">

                <div>

                    <h5 class="modal-title mb-1">
                        <i class="bi bi-calendar-check me-2"></i>
                        Assign Auditor
                    </h5>

                    <small class="opacity-75">
                        Assign auditor and set next inspection cycle
                    </small>

                </div>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>

            </div>

            <form method="POST">

                <input type="hidden"
                       name="action"
                       value="create_schedule">

	                <input type="hidden"
	                       name="train_info_id"
	                       id="trainInfoId">

	                <input type="hidden"
	                       name="coach_id"
	                       id="coachId">

                <div class="modal-body p-4">

                    <div class="card border-0 bg-light mb-4">

                        <div class="card-body">

                            <div class="row g-3">

	                                <div class="col-md-6">

                                    <label class="form-label text-muted small">
                                        Coach Number
                                    </label>

                                    <input type="text"
                                           class="form-control fw-bold"
                                           id="coachNo"
                                           readonly>

                                </div>

	                                <div class="col-md-6">

                                    <label class="form-label text-muted small">
                                        Next Inspection Date
                                    </label>

	                                    <input type="date"
	                                           class="form-control fw-bold text-danger"
	                                           id="nextDueDate"
	                                           name="next_due_date"
                                               readonly
	                                           required>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Select Auditor
                            </label>

                            <select class="form-select"
                                    name="auditor_id"
                                    required>

                                <option value="">
                                    Choose Auditor
                                </option>

                                <?php foreach ($auditors as $auditor): ?>

                                    <option value="<?php echo e($auditor['user_id']); ?>">

                                        <?php
                                        echo e(
                                            !empty($auditor['full_name'])
                                            ? $auditor['full_name']
                                            : $auditor['user_name']
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Assignment Date & Time
                            </label>

	                            <input type="datetime-local"
	                                   class="form-control"
	                                   id="assignmentDateTime"
	                                   name="assignment_date_time"
	                                   required>

                        </div>

                    </div>

                    <div class="row g-3 mt-1">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Inspection Type
                            </label>

                            <select class="form-select"
                                    name="inspection_type"
                                    id="inspectionType"
                                    required>

                                <option value="1_month">
                                    1 Month Inspection
                                </option>

                                <option value="3_month">
                                    3 Month Inspection
                                </option>

                                <option value="6_month">
                                    6 Month Inspection
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="alert alert-info mt-4 mb-0">

                        <div class="d-flex align-items-start">

                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>

                            <div>

                                <strong>
                                    FDSS Inspection Notice
                                </strong>

                                <div class="small mt-1">

                                    Next Inspection Date will be calculated from Assignment Date & Time
                                    based on selected inspection type.

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer bg-light">

                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-primary px-4">

                        <i class="bi bi-check-circle me-1"></i>

                        Assign Auditor

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<div class="modal fade"
     id="roundTripScheduleModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header text-white" style="background-color: #61a2cb;">

                <div>

                    <h5 class="modal-title mb-1">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Round Trip Schedule
                    </h5>

                    <small class="opacity-75">
                        Assign auditor for round trip inspection
                    </small>

                </div>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>

            </div>

            <form method="POST">

                <input type="hidden"
                       name="action"
                       value="create_round_trip_schedule">

                <div class="modal-body p-4">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Train
                            </label>

                            <select class="form-select"
                                    name="round_trip_train_info_id"
                                    id="roundTripTrain"
                                    required>

                                <option value="">
                                    Choose Train
                                </option>

                                <option value="detached">
                                    Detached
                                </option>

                                <?php foreach ($trains as $train): ?>

                                    <option value="<?php echo e($train['train_info_id']); ?>">
                                        <?php echo e($train['train_no']); ?>
                                        -
                                        <?php echo e($train['train_name']); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Coach
                            </label>

                            <select class="form-select"
                                    name="round_trip_coach_id"
                                    id="roundTripCoach"
                                    required>

                                <option value="">
                                    Choose Coach
                                </option>

                                <?php foreach ($round_trip_coaches as $coach): ?>

                                    <?php
                                    $round_trip_train_id = (
                                        empty($coach['train_info_id']) ||
                                        $coach['coach_status'] === 'Detached'
                                    )
                                        ? 'detached'
                                        : $coach['train_info_id'];
                                    ?>

                                    <option value="<?php echo e($coach['coach_id']); ?>"
                                            data-train-id="<?php echo e($round_trip_train_id); ?>">
                                        <?php echo e($coach['coach_no']); ?>
                                        <?php if (!empty($coach['coach_type'])): ?>
                                            -
                                            <?php echo e($coach['coach_type']); ?>
                                        <?php endif; ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Select Auditor
                            </label>

                            <select class="form-select"
                                    name="round_trip_auditor_id"
                                    required>

                                <option value="">
                                    Choose Auditor
                                </option>

                                <?php foreach ($auditors as $auditor): ?>

                                    <option value="<?php echo e($auditor['user_id']); ?>">

                                        <?php
                                        echo e(
                                            !empty($auditor['full_name'])
                                            ? $auditor['full_name']
                                            : $auditor['user_name']
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Assignment Date & Time
                            </label>

                            <input type="datetime-local"
                                   class="form-control"
                                   name="round_trip_assignment_date_time"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Inspection Type
                            </label>

                            <input type="text"
                                   class="form-control fw-bold"
                                   name="round_trip_inspection_type"
                                   value="Round Trip"
                                   readonly>

                        </div>

                    </div>

                </div>

                <div class="modal-footer bg-light">

                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-primary px-4">

                        <i class="bi bi-check-circle me-1"></i>
                        Create Round Trip Schedule

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/layout.js"></script>

<script>

function openScheduleModal(coachId, trainInfoId, coachNo, nextDueDate) {
    document.getElementById('coachId').value = coachId;
    document.getElementById('trainInfoId').value = trainInfoId;
    document.getElementById('coachNo').value = coachNo;

    const assignmentDateTime = document.getElementById('assignmentDateTime');
    const nextDueDateInput = document.getElementById('nextDueDate');
    const inspectionType = document.getElementById('inspectionType');

    assignmentDateTime.value = nextDueDate + 'T10:00';
    inspectionType.value = '1_month';
    updateNextInspectionDate();
}

function updateNextInspectionDate() {
    const assignmentDateTime = document.getElementById('assignmentDateTime');
    const nextDueDateInput = document.getElementById('nextDueDate');
    const inspectionType = document.getElementById('inspectionType');
    const dayMap = {
        '1_month': 30,
        '3_month': 30,
        '6_month': 30,
    };

    if (!assignmentDateTime.value) {
        nextDueDateInput.value = '';
        return;
    }

    const nextDate = new Date(assignmentDateTime.value);
    nextDate.setDate(nextDate.getDate() + (dayMap[inspectionType.value] || 30));
    nextDueDateInput.value = formatLocalDate(nextDate);
}

function formatLocalDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

document.getElementById('assignmentDateTime')
    .addEventListener('change', updateNextInspectionDate);

document.getElementById('inspectionType')
    .addEventListener('change', updateNextInspectionDate);

function filterRoundTripCoaches() {
    const trainSelect = document.getElementById('roundTripTrain');
    const coachSelect = document.getElementById('roundTripCoach');

    if (!trainSelect || !coachSelect) {
        return;
    }

    const selectedTrainId = trainSelect.value;

    Array.from(coachSelect.options).forEach((option) => {
        if (!option.value) {
            option.hidden = false;
            option.disabled = false;
            return;
        }

        const shouldHide = selectedTrainId === '' || option.dataset.trainId !== selectedTrainId;

        option.hidden = shouldHide;
        option.disabled = shouldHide;
    });

    if (
        coachSelect.selectedOptions.length &&
        coachSelect.selectedOptions[0].hidden
    ) {
        coachSelect.value = '';
    }
}

document.getElementById('roundTripTrain')
    .addEventListener('change', filterRoundTripCoaches);

document.getElementById('roundTripScheduleModal')
    .addEventListener('shown.bs.modal', filterRoundTripCoaches);

</script>

</body>
</html>

<?php
$conn->close();
?>
