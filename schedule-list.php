<?php
session_start();
require_once 'config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$current_page = basename($_SERVER['SCRIPT_NAME']);
$list_view_only = $current_page === 'schedule-list-view.php';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$message_type = '';

$selected_schedule_date = trim($_GET['schedule_date'] ?? '');

if (
    $selected_schedule_date !== '' &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_schedule_date)
) {
    $selected_schedule_date = '';
}

$rows_per_page_options = [
    '10' => '10',
    '20' => '20',
    '50' => '50',
    'all' => 'All'
];

$selected_rows_per_page = $_GET['rows_per_page'] ?? '10';

if (!array_key_exists($selected_rows_per_page, $rows_per_page_options)) {
    $selected_rows_per_page = '10';
}

$schedule_limit = $selected_rows_per_page === 'all'
    ? null
    : (int) $selected_rows_per_page;

$schedule_uses_coach_id = false;
$schedule_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'coach_id'");

if ($schedule_column_check && $schedule_column_check->num_rows > 0) {
    $schedule_uses_coach_id = true;
}

$schedule_uses_coach_no = false;
$schedule_coach_no_column_check = $conn->query("SHOW COLUMNS FROM fdss_coach_schedule LIKE 'coach_no'");

if ($schedule_coach_no_column_check && $schedule_coach_no_column_check->num_rows > 0) {
    $schedule_uses_coach_no = true;
}

/*
|--------------------------------------------------------------------------
| UPDATE SCHEDULE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    if ($action === 'update_schedule') {

        $schedule_id = (int) ($_POST['schedule_id'] ?? 0);

        $auditor_id = (int) ($_POST['auditor_id'] ?? 0);

        $assignment_date_time = trim($_POST['assignment_date_time'] ?? '');

        $status = $_POST['status'] ?? 'Assigned';

        if (
            $schedule_id <= 0 ||
            $auditor_id <= 0 ||
            $assignment_date_time === ''
        ) {

            $message = "Please fill all required fields.";
            $message_type = "danger";

        } else {

            $status_check_query = "SELECT status
                                   FROM fdss_coach_schedule
                                   WHERE schedule_id = ?
                                   AND user_id = ?
                                   LIMIT 1";

            $status_check_stmt = $conn->prepare($status_check_query);
            $current_schedule_status = null;
            $schedule_coach_id = null;

            if ($status_check_stmt) {
                $status_check_stmt->bind_param("ii", $schedule_id, $user_id);
                $status_check_stmt->execute();
                $status_check_result = $status_check_stmt->get_result();

                if ($status_row = $status_check_result->fetch_assoc()) {
                    $current_schedule_status = $status_row['status'];
                }

                $status_check_stmt->close();
            }

            if ($current_schedule_status === null) {

                $message = "Schedule not found.";
                $message_type = "danger";

            } elseif ($current_schedule_status === 'Completed') {

                $message = "Completed schedule cannot be edited.";
                $message_type = "danger";

            } else {

            if ($schedule_uses_coach_id) {
                $coach_query = "SELECT coach_id
                                FROM fdss_coach_schedule
                                WHERE schedule_id = ?
                                AND user_id = ?
                                LIMIT 1";

                $coach_stmt = $conn->prepare($coach_query);

                if ($coach_stmt) {
                    $coach_stmt->bind_param("ii", $schedule_id, $user_id);
                    $coach_stmt->execute();
                    $coach_result = $coach_stmt->get_result();

                    if ($coach_row = $coach_result->fetch_assoc()) {
                        $schedule_coach_id = (int) $coach_row['coach_id'];
                    }

                    $coach_stmt->close();
                }
            }

            $update_query = "UPDATE fdss_coach_schedule SET

                auditor_id = ?,
                assignment_date_time = ?,
                status = ?

                WHERE schedule_id = ?
                AND user_id = ?
                AND status <> 'Completed'";

            $stmt = $conn->prepare($update_query);

            if ($stmt) {

                $stmt->bind_param(
                    "issii",
                    $auditor_id,
                    $assignment_date_time,
                    $status,
                    $schedule_id,
                    $user_id
                );

                if ($stmt->execute()) {

                    if ($status === 'Completed' && $schedule_coach_id > 0) {
                        $coach_status_query = "UPDATE fdss_train_coach
                                               SET schedule_status = 0
                                               WHERE coach_id = ?
                                               AND user_id = ?";

                        $coach_status_stmt = $conn->prepare($coach_status_query);

                        if ($coach_status_stmt) {
                            $coach_status_stmt->bind_param(
                                "ii",
                                $schedule_coach_id,
                                $user_id
                            );

                            $coach_status_stmt->execute();
                            $coach_status_stmt->close();
                        }
                    }

                    $message = "Schedule updated successfully!";
                    $message_type = "success";

                } else {

                    $message = "Error updating schedule.";
                    $message_type = "danger";
                }

                $stmt->close();

            } else {

                $message = "SQL Error: " . $conn->error;
                $message_type = "danger";
            }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete_schedule') {

        $schedule_id = (int) ($_POST['schedule_id'] ?? 0);

        $coach_no = trim($_POST['coach_no'] ?? '');

        $train_info_id = (int) ($_POST['train_info_id'] ?? 0);

        $status_check_query = "SELECT status
                               FROM fdss_coach_schedule
                               WHERE schedule_id = ?
                               AND user_id = ?
                               LIMIT 1";

        $status_check_stmt = $conn->prepare($status_check_query);
        $current_schedule_status = null;

        if ($status_check_stmt) {
            $status_check_stmt->bind_param("ii", $schedule_id, $user_id);
            $status_check_stmt->execute();
            $status_check_result = $status_check_stmt->get_result();

            if ($status_row = $status_check_result->fetch_assoc()) {
                $current_schedule_status = $status_row['status'];
            }

            $status_check_stmt->close();
        }

        if ($current_schedule_status === null) {

            $message = "Schedule not found.";
            $message_type = "danger";

        } elseif ($current_schedule_status === 'Completed') {

            $message = "Completed schedule cannot be deleted.";
            $message_type = "danger";

        } else {

        $delete_query = "DELETE FROM fdss_coach_schedule
                         WHERE schedule_id = ?
                         AND user_id = ?
                         AND status <> 'Completed'";

        $stmt = $conn->prepare($delete_query);

        if ($stmt) {

            $stmt->bind_param(
                "ii",
                $schedule_id,
                $user_id
            );

            if ($stmt->execute() && $stmt->affected_rows > 0) {

                /*
                |--------------------------------------------------------------------------
                | RESET COACH STATUS
                |--------------------------------------------------------------------------
                */

                $reset_query = "UPDATE fdss_train_coach
                                SET schedule_status = 0
                                WHERE coach_no = ?
                                AND train_info_id = ?
                                AND user_id = ?";

                $reset_stmt = $conn->prepare($reset_query);

                if ($reset_stmt) {

                    $reset_stmt->bind_param(
                        "sii",
                        $coach_no,
                        $train_info_id,
                        $user_id
                    );

                    $reset_stmt->execute();

                    $reset_stmt->close();
                }

                $message = "Schedule deleted successfully!";
                $message_type = "success";

            } else {

                $message = "Completed schedule cannot be deleted.";
                $message_type = "danger";
            }

            $stmt->close();
        }
        }
    }
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
| FETCH SCHEDULES
|--------------------------------------------------------------------------
*/

$schedules = [];

$where_clause = "WHERE s.user_id = ?";
$bind_types = "i";
$bind_values = [$user_id];

if ($selected_schedule_date !== '') {
    $where_clause .= " AND DATE(s.assignment_date_time) = ?";
    $bind_types .= "s";
    $bind_values[] = $selected_schedule_date;
}

if ($schedule_uses_coach_id) {
    $coach_no_select = $schedule_uses_coach_no
        ? "COALESCE(c.coach_no, s.coach_no) AS coach_no"
        : "c.coach_no";

    $query = "SELECT

                s.schedule_id,
                s.coach_id,
                $coach_no_select,
                COALESCE(s.train_info_id, c.train_info_id) AS train_info_id,
                s.last_inspection_date,
                s.status,
                s.assignment_date_time,

                u.user_name,
                u.full_name,
                u.user_id AS auditor_user_id,

                t.train_no,
                t.train_name

              FROM fdss_coach_schedule s

              LEFT JOIN fdss_train_coach c
                ON c.coach_id = s.coach_id
                AND c.user_id = s.user_id

              LEFT JOIN fdss_users u
                ON u.user_id = s.auditor_id

              LEFT JOIN fdss_train_information t
                ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)

              $where_clause

              ORDER BY s.assignment_date_time DESC, s.schedule_id DESC";
} else {
    $query = "SELECT

                s.schedule_id,
                NULL AS coach_id,
                s.coach_no,
                s.train_info_id,
                s.last_inspection_date,
                s.status,
                s.assignment_date_time,

                u.user_name,
                u.full_name,
                u.user_id AS auditor_user_id,

                t.train_no,
                t.train_name

              FROM fdss_coach_schedule s

              LEFT JOIN fdss_users u
                ON u.user_id = s.auditor_id

              LEFT JOIN fdss_train_information t
                ON t.train_info_id = s.train_info_id

              $where_clause

              ORDER BY s.assignment_date_time DESC, s.schedule_id DESC";
}

if ($schedule_limit !== null) {
    $query .= " LIMIT ?";
    $bind_types .= "i";
    $bind_values[] = $schedule_limit;
}

$stmt = $conn->prepare($query);

if ($stmt) {

    $bind_references = [];

    foreach ($bind_values as $key => $value) {
        $bind_references[$key] = &$bind_values[$key];
    }

    $stmt->bind_param($bind_types, ...$bind_references);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    $stmt->close();
} elseif ($message === '') {
    $message = "Schedule Fetch SQL Error: " . $conn->error;
    $message_type = "danger";
}

$calendar_schedules = [];
$calendar_events = [];
$calendar_month_map = [];

if ($schedule_uses_coach_id) {
    $calendar_coach_select = $schedule_uses_coach_no
        ? "COALESCE(c.coach_no, s.coach_no) AS coach_no"
        : "c.coach_no";

    $calendar_query = "SELECT
                            s.schedule_id,
                            s.status,
                            s.assignment_date_time,
                            s.train_info_id,
                            $calendar_coach_select,
                            u.user_name,
                            u.full_name,
                            u.user_id AS auditor_user_id,
                            t.train_no,
                            t.train_name
                       FROM fdss_coach_schedule s
                       LEFT JOIN fdss_train_coach c
                            ON c.coach_id = s.coach_id
                            AND c.user_id = s.user_id
                       LEFT JOIN fdss_users u
                            ON u.user_id = s.auditor_id
                       LEFT JOIN fdss_train_information t
                            ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)
                       WHERE s.user_id = ?
                       ORDER BY s.assignment_date_time ASC, s.schedule_id ASC";
} else {
    $calendar_query = "SELECT
                            s.schedule_id,
                            s.status,
                            s.assignment_date_time,
                            s.train_info_id,
                            s.coach_no,
                            u.user_name,
                            u.full_name,
                            u.user_id AS auditor_user_id,
                            t.train_no,
                            t.train_name
                       FROM fdss_coach_schedule s
                       LEFT JOIN fdss_users u
                            ON u.user_id = s.auditor_id
                       LEFT JOIN fdss_train_information t
                            ON t.train_info_id = s.train_info_id
                       WHERE s.user_id = ?
                       ORDER BY s.assignment_date_time ASC, s.schedule_id ASC";
}

$calendar_stmt = $conn->prepare($calendar_query);

if ($calendar_stmt) {
    $calendar_stmt->bind_param("i", $user_id);
    $calendar_stmt->execute();
    $calendar_result = $calendar_stmt->get_result();

    while ($row = $calendar_result->fetch_assoc()) {
        $calendar_schedules[] = $row;

        if (!empty($row['assignment_date_time'])) {
            $date_key = date('Y-m-d', strtotime($row['assignment_date_time']));
            $calendar_events[$date_key][] = $row;
            $calendar_month_map[date('Y-m', strtotime($row['assignment_date_time']))] = new DateTime(date('Y-m-01', strtotime($row['assignment_date_time'])));
        }
    }

    $calendar_stmt->close();
}

if (empty($calendar_month_map)) {
    $calendar_month_map[date('Y-m')] = new DateTime(date('Y-m-01'));
}

ksort($calendar_month_map);
$calendar_months = array_values($calendar_month_map);
$calendar_event_payload = [];

foreach ($calendar_events as $event_date => $events) {
    foreach ($events as $event) {
        $calendar_event_payload[$event_date][] = [
            'schedule_id' => (int) $event['schedule_id'],
            'coach_no' => $event['coach_no'],
            'train' => trim(($event['train_no'] ?? '') . (!empty($event['train_name']) ? ' - ' . $event['train_name'] : '')),
            'auditor' => !empty($event['full_name']) ? $event['full_name'] : $event['user_name'],
            'auditor_id' => (int) ($event['auditor_user_id'] ?? 0),
            'assignment_date_time' => $event['assignment_date_time'],
            'status' => $event['status'],
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Inspection Schedule List
    </title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <link href="assets/css/styles.css"
          rel="stylesheet">

    <style>
        .inspection-calendar {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            overflow-x: auto;
        }
        .calendar-title {
            background: #61a2cb;
            color: #ffffff;
            font-size: .92rem;
            font-weight: 700;
            padding: 9px 12px;
            text-transform: uppercase;
        }
        .calendar-weekdays,
        .calendar-grid {
            min-width: 640px;
            display: grid;
            grid-template-columns: repeat(7, minmax(82px, 1fr));
        }
        .calendar-weekdays > div {
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            color: #475569;
            font-size: .68rem;
            font-weight: 700;
            padding: 6px;
            text-align: center;
            text-transform: uppercase;
        }
        .calendar-day {
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            min-height: 82px;
            padding: 25px 5px 5px;
            position: relative;
        }
        .calendar-day:nth-child(7n),
        .calendar-weekdays > div:last-child {
            border-right: 0;
        }
        .calendar-day.is-muted {
            background: #f8fafc;
            color: #cbd5e1;
        }
        .calendar-day.is-today {
            background: #f0f9ff;
        }
        .calendar-date {
            background: #e0f2fe;
            border-bottom: 1px solid #bae6fd;
            color: #075985;
            font-size: .78rem;
            font-weight: 700;
            left: 0;
            line-height: 20px;
            padding-right: 7px;
            position: absolute;
            right: 0;
            text-align: right;
            top: 0;
        }
        .calendar-count {
            align-items: center;
            background: #eef7fb;
            border: 1px solid #c9e7f4;
            border-radius: 6px;
            color: #075985;
            display: flex;
            font-size: .72rem;
            font-weight: 700;
            gap: 6px;
            justify-content: center;
            line-height: 1.2;
            margin-top: 6px;
            min-height: 38px;
            padding: 6px;
            text-align: center;
            width: 100%;
        }
        .calendar-count:hover {
            background: #dff1f8;
            border-color: #61a2cb;
        }
        .calendar-count .count-number {
            font-size: 1rem;
        }
        .schedule-list-item {
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 10px 12px;
        }
        .schedule-list-item + .schedule-list-item {
            margin-top: 10px;
        }
        .schedule-list-title {
            color: #1e293b;
            font-size: .9rem;
            font-weight: 700;
        }
        .schedule-list-meta {
            color: #64748b;
            font-size: .78rem;
            margin-top: 2px;
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

            <h1>
                <?php echo $list_view_only ? 'Schedule List View' : 'Inspection Schedule List'; ?>
            </h1>

            <p class="page-header-subtitle">
                Manage and update all coach inspection schedules.
            </p>

        </div>

        <div class="page-header-actions">

            <?php if ($list_view_only): ?>

            <a class="btn btn-primary"
               href="schedule-list.php">

                <i class="bi bi-calendar3"></i>
                Calendar View

            </a>

            <?php else: ?>

            <a class="btn btn-primary"
               href="schedule-list-view.php">

                <i class="bi bi-list-ul"></i>
                List View

            </a>

            <?php endif; ?>

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

    <?php if (!$list_view_only): ?>

    <div class="content-card mb-4">

        <div class="card-header">

            <h5>
                <i class="bi bi-calendar3"></i>
                Schedule Calendar
            </h5>

        </div>

        <div class="card-body">

            <?php foreach ($calendar_months as $calendar_month): ?>

                <?php
                $month_start = new DateTime($calendar_month->format('Y-m-01'));
                $month_end = new DateTime($calendar_month->format('Y-m-t'));
                $grid_start = clone $month_start;
                $grid_start->modify('-' . ((int)$grid_start->format('N') - 1) . ' days');
                $grid_end = clone $month_end;
                $grid_end->modify('+' . (7 - (int)$grid_end->format('N')) . ' days');
                $day_cursor = clone $grid_start;
                $today = date('Y-m-d');
                ?>

                <div class="inspection-calendar mb-4">

                    <div class="calendar-title">
                        <?php echo e($calendar_month->format('F Y')); ?>
                    </div>

                    <div class="calendar-weekdays">
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                        <div>Sun</div>
                    </div>

                    <div class="calendar-grid">

                        <?php while ($day_cursor <= $grid_end): ?>

                            <?php
                            $date_key = $day_cursor->format('Y-m-d');
                            $is_current_month = $day_cursor->format('m') === $calendar_month->format('m');
                            $day_events = $calendar_events[$date_key] ?? [];
                            $assigned_count = 0;
                            $completed_count = 0;

                            foreach ($day_events as $event) {
                                if ($event['status'] === 'Completed') {
                                    $completed_count++;
                                } else {
                                    $assigned_count++;
                                }
                            }
                            ?>

                            <div class="calendar-day <?php echo $is_current_month ? '' : 'is-muted'; ?> <?php echo $date_key === $today ? 'is-today' : ''; ?>">

                                <div class="calendar-date">
                                    <?php echo e($day_cursor->format('j')); ?>
                                </div>

                                <?php if (!empty($day_events)): ?>

                                    <button
                                        type="button"
                                        class="calendar-count"
                                        onclick="openDateSchedules('<?php echo e($date_key); ?>')"
                                        data-bs-toggle="modal"
                                        data-bs-target="#dateSchedulesModal">

                                        <span class="count-number">
                                            <?php echo count($day_events); ?>
                                        </span>

                                        <span>
                                            Schedules
                                            <br>
                                            <small>
                                                <?php echo $assigned_count; ?> Assigned / <?php echo $completed_count; ?> Complete
                                            </small>
                                        </span>

                                    </button>

                                <?php endif; ?>

                            </div>

                            <?php $day_cursor->modify('+1 day'); ?>

                        <?php endwhile; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

    <?php endif; ?>

    <?php if ($list_view_only): ?>

    <div class="content-card" id="scheduleListView">

        <div class="card-header">

            <h5>

                <i class="bi bi-table"></i>

                Schedule List

            </h5>

        </div>

        <div class="card-body">

            <form method="GET"
                  class="row g-3 align-items-end mb-4">

                <div class="col-md-4 col-lg-3">

                    <label class="form-label">
                        Assignment Date
                    </label>

                    <input type="date"
                           class="form-control"
                           name="schedule_date"
                           value="<?php echo e($selected_schedule_date); ?>">

                </div>

                <div class="col-md-4 col-lg-3">

                    <label class="form-label">
                        Rows
                    </label>

                    <select class="form-select"
                            name="rows_per_page">

                        <?php foreach ($rows_per_page_options as $value => $label): ?>

                            <option value="<?php echo e($value); ?>"
                                    <?php echo $selected_rows_per_page === $value ? 'selected' : ''; ?>>

                                <?php echo e($label); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-4 col-lg-3 d-flex gap-2">

                    <button type="submit"
                            class="btn btn-primary">

                        <i class="bi bi-funnel"></i>
                        Filter

                    </button>

                    <a href="<?php echo e($current_page); ?>"
                       class="btn btn-outline-secondary">

                        Reset

                    </a>

                </div>

            </form>

            <div class="table-wrapper">

                <table class="table table-hover">

                    <thead>

                    <tr>

                        <th>Coach</th>
                        <th>Train Name/NO.</th>
                        <th>Auditor</th>
                        <th>Assignment Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($schedules)): ?>

                        <tr>

                            <td colspan="6"
                                class="text-center text-muted py-4">

                                No schedules found.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($schedules as $schedule): ?>

                            <?php $is_completed = $schedule['status'] === 'Completed'; ?>

                            <tr>

                                <td>

                                    <strong>

                                        <?php echo e($schedule['coach_no']); ?>

                                    </strong>

                                </td>

                                <td>

                                    <strong>

                                        <?php echo e($schedule['train_no']); ?>

                                    </strong>

                                    <br>

                                    <?php echo e($schedule['train_name']); ?>

                                </td>

                                <td>

                                    <?php
                                    echo e(
                                        !empty($schedule['full_name'])
                                        ? $schedule['full_name']
                                        : $schedule['user_name']
                                    );
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    echo $schedule['assignment_date_time']
                                        ? date('d M Y h:i A', strtotime($schedule['assignment_date_time']))
                                        : '-';
                                    ?>

                                </td>

                                <td>

                                    <?php

                                    $status_class = 'badge-warning';

                                    if ($schedule['status'] === 'Completed') {
                                        $status_class = 'badge-success';
                                    }

                                    if ($schedule['status'] === 'Pending') {
                                        $status_class = 'badge-secondary';
                                    }

                                    ?>

                                    <span class="badge <?php echo $status_class; ?>">

                                        <?php echo e($schedule['status']); ?>

                                    </span>

                                </td>

                                <td>

                                    <?php if ($is_completed): ?>

                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Completed schedule cannot be edited"
                                            disabled>

                                            <i class="bi bi-pencil"></i>

                                        </button>

                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Completed schedule cannot be deleted"
                                            disabled>

                                            <i class="bi bi-trash"></i>

                                        </button>

                                    <?php else: ?>

                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editSchedule(
                                            '<?php echo e($schedule['schedule_id']); ?>',
                                            '<?php echo e($schedule['auditor_user_id']); ?>',
                                            '<?php echo e($schedule['assignment_date_time']); ?>',
                                            '<?php echo e($schedule['status']); ?>'
                                        )"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal">

                                        <i class="bi bi-pencil"></i>

                                    </button>

                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="deleteSchedule(
                                            '<?php echo e($schedule['schedule_id']); ?>',
                                            '<?php echo e($schedule['coach_no']); ?>',
                                            '<?php echo e($schedule['train_info_id']); ?>'
                                        )">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <?php endif; ?>

</main>

</div>

<div class="modal fade"
     id="dateSchedulesModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">

        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header text-white"
                 style="background-color:#61a2cb;">

                <h5 class="modal-title" id="dateSchedulesTitle">
                    Date Schedules
                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">

                <div id="dateSchedulesList"></div>

            </div>

        </div>

    </div>

</div>

<!-- Edit Modal -->

<div class="modal fade"
     id="editModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header text-white"
                 style="background-color:#61a2cb;">

                <h5 class="modal-title">

                    Edit Inspection Schedule

                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>

            </div>

            <form method="POST">

                <input type="hidden"
                       name="action"
                       value="update_schedule">

                <input type="hidden"
                       name="schedule_id"
                       id="editScheduleId">

                <div class="modal-body p-4">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Auditor
                            </label>

                            <select class="form-select"
                                    name="auditor_id"
                                    id="editAuditor"
                                    required>

                                <option value="">
                                    Select Auditor
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

                            <label class="form-label">
                                Inspection Date & Time
                            </label>

                            <input type="datetime-local"
                                   class="form-control"
                                   name="assignment_date_time"
                                   id="editDateTime"
                                   required>

                        </div>

                    </div>

                    <div class="row g-3 mt-1">

                        <div class="col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select class="form-select"
                                    name="status"
                                    id="editStatus">

                                <option value="Pending">
                                    Pending
                                </option>

                                <option value="Assigned">
                                    Assigned
                                </option>

                                <option value="Completed">
                                    Completed
                                </option>

                            </select>

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
                            class="btn btn-primary">

                        Update Schedule

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
           value="delete_schedule">

    <input type="hidden"
           name="schedule_id"
           id="deleteScheduleId">

    <input type="hidden"
           name="coach_no"
           id="deleteCoachNo">

    <input type="hidden"
           name="train_info_id"
           id="deleteTrainInfoId">

</form>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/layout.js"></script>

<script>

const calendarEvents = <?php echo json_encode($calendar_event_payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDateTimeForInput(value) {
    return value ? value.replace(' ', 'T').slice(0, 16) : '';
}

function openDateSchedules(dateKey) {
    const events = calendarEvents[dateKey] || [];
    const title = document.getElementById('dateSchedulesTitle');
    const list = document.getElementById('dateSchedulesList');
    const formattedDate = new Date(dateKey + 'T00:00:00').toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });

    title.textContent = formattedDate + ' - ' + events.length + ' schedule(s)';

    if (!events.length) {
        list.innerHTML = '<p class="text-muted mb-0">No schedules found.</p>';
        return;
    }

    list.innerHTML = events.map((event) => {
        const isCompleted = event.status === 'Completed';
        const statusClass = isCompleted ? 'bg-success' : 'bg-warning text-dark';
        const actionButton = isCompleted
            ? `<button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                   <i class="bi bi-lock"></i> Completed
               </button>`
            : `<button type="button"
                       class="btn btn-sm btn-outline-primary"
                       data-bs-dismiss="modal"
                       onclick="editSchedule('${escapeHtml(event.schedule_id)}', '${escapeHtml(event.auditor_id)}', '${escapeHtml(event.assignment_date_time)}', '${escapeHtml(event.status)}')"
                       data-bs-toggle="modal"
                       data-bs-target="#editModal">
                   <i class="bi bi-pencil"></i> Edit
               </button>`;

        return `
            <div class="schedule-list-item">
                <div>
                    <div class="schedule-list-title">
                        ${escapeHtml(event.coach_no || '-')}
                        <span class="badge ${statusClass} ms-2">${escapeHtml(event.status)}</span>
                    </div>
                    <div class="schedule-list-meta">
                        ${escapeHtml(event.train || 'Detached')} | ${escapeHtml(event.auditor || '-')} | ${escapeHtml(event.assignment_date_time || '-')}
                    </div>
                </div>
                <div>
                    ${actionButton}
                </div>
            </div>
        `;
    }).join('');
}

function editSchedule(
    scheduleId,
    auditorId,
    assignmentDateTime,
    status
) {

    document.getElementById('editScheduleId').value = scheduleId;

    document.getElementById('editAuditor').value = auditorId;

    document.getElementById('editDateTime').value = formatDateTimeForInput(assignmentDateTime);

    document.getElementById('editStatus').value = status;
}

function deleteSchedule(
    scheduleId,
    coachNo,
    trainInfoId
) {

    if (confirm('Delete this schedule?')) {

        document.getElementById('deleteScheduleId').value = scheduleId;

        document.getElementById('deleteCoachNo').value = coachNo;

        document.getElementById('deleteTrainInfoId').value = trainInfoId;

        document.getElementById('deleteForm').submit();
    }
}

</script>

</body>
</html>

<?php
$conn->close();
?>
