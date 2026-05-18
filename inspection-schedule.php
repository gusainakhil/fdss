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
        $priority = $_POST['priority'] ?? 'Normal';
        $special_remarks = trim($_POST['special_remarks'] ?? '');

        if ($assignment_date_time !== '') {
            $assignment_date_time = str_replace('T', ' ', $assignment_date_time);

            if (strlen($assignment_date_time) === 16) {
                $assignment_date_time .= ':00';
            }
        }

        $last_inspection_date = null;

        $next_due_date = !empty($_POST['next_due_date'])
            ? $_POST['next_due_date']
            : null;

        if (
            $coach_id <= 0 ||
            $auditor_id <= 0 ||
            $assignment_date_time === ''
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
                user_id
            )
            VALUES
            (
                ?, ?, ?, ?, 'Assigned', ?, ?, ?, ?, ?
            )";

            $stmt = $conn->prepare($insert_query);

            if (!$stmt) {
                $message = "Schedule Insert SQL Error: " . $conn->error;
                $message_type = "danger";
            } else {

                if ($schedule_uses_coach_id) {
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

/*
|--------------------------------------------------------------------------
| FETCH RECENT SCHEDULES
|--------------------------------------------------------------------------
*/

$recent_schedules = [];

if ($schedule_uses_coach_id) {
    $recent_query = "SELECT
                        s.schedule_id,
                        s.coach_id,
                        s.assignment_date_time,
                        s.priority,
                        s.status,
                        u.user_name,
                        u.full_name,
                        c.coach_no,
                        t.train_no,
                        t.train_name
                     FROM fdss_coach_schedule s
                     LEFT JOIN fdss_train_coach c
                        ON c.coach_id = s.coach_id
                     LEFT JOIN fdss_users u
                        ON u.user_id = s.auditor_id
                     LEFT JOIN fdss_train_information t
                        ON t.train_info_id = COALESCE(s.train_info_id, c.train_info_id)
                     WHERE s.user_id = ?
                     AND DATE(s.assignment_date_time) BETWEEN ? AND ?
                     ORDER BY s.schedule_id DESC
                     LIMIT 10";
} else {
    $recent_query = "SELECT
                        s.schedule_id,
                        NULL AS coach_id,
                        s.assignment_date_time,
                        s.priority,
                        s.status,
                        u.user_name,
                        u.full_name,
                        s.coach_no,
                        t.train_no,
                        t.train_name
                     FROM fdss_coach_schedule s
                     LEFT JOIN fdss_users u
                        ON u.user_id = s.auditor_id
                     LEFT JOIN fdss_train_information t
                        ON t.train_info_id = s.train_info_id
                     WHERE s.user_id = ?
                     AND DATE(s.assignment_date_time) BETWEEN ? AND ?
                     ORDER BY s.schedule_id DESC
                     LIMIT 10";
}

$stmt = $conn->prepare($recent_query);

if ($stmt) {

    $stmt->bind_param("iss", $user_id, $today, $advance_cutoff_date);

    $stmt->execute();

    $recent_result = $stmt->get_result();

    while ($row = $recent_result->fetch_assoc()) {
        $recent_schedules[] = $row;
    }

    $stmt->close();

} else {
    $message = "Recent Schedule SQL Error: " . $conn->error;
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
                Auto Inspection Schedule
            </h1>

            <p class="page-header-subtitle">
                Create inspection schedules for overdue coaches and coaches due within next 3 days.
            </p>

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

        <div class="col-lg-7">

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
                                <th>Action</th>

                            </tr>

                            </thead>

                            <tbody>

                            <?php if (empty($coaches)): ?>

                                <tr>

                                    <td colspan="5"
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

                                                Create Schedule

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

        <div class="col-lg-5">

            <div class="content-card">

                <div class="card-header">

                    <h5>

                        <i class="bi bi-clock-history"></i>

                        Recent Schedules

                    </h5>

                </div>

                <div class="card-body">

                    <div class="table-wrapper">

                        <table class="table table-hover">

                            <thead>

                            <tr>

                                <th>Coach</th>
                                <th>Auditor</th>
                                <th>Date</th>
                                <th>Priority</th>

                            </tr>

                            </thead>

                            <tbody>

                            <?php if (empty($recent_schedules)): ?>

                                <tr>

                                    <td colspan="4"
                                        class="text-center text-muted py-4">

                                        No schedules created yet.

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($recent_schedules as $schedule): ?>

                                    <tr>

                                        <td>

                                            <strong>

                                                <?php echo e($schedule['coach_no']); ?>

                                            </strong>

                                            <br>

                                            <?php echo e($schedule['train_no']); ?>

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
                                            $priority_class =
                                                ($schedule['priority'] === 'High')
                                                ? 'bg-danger'
                                                : 'bg-info';
                                            ?>

                                            <span class="badge <?php echo $priority_class; ?>">

                                                <?php echo e($schedule['priority']); ?>

                                            </span>

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
                        Create Inspection Schedule
                    </h5>

                    <small class="opacity-75">
                        Assign auditor and create inspection task
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

                        <div class="col-md-4">

                            <label class="form-label fw-semibold">
                                Priority
                            </label>

                            <select class="form-select"
                                    name="priority">

                                <option value="Normal">
                                    Normal Priority
                                </option>

                                <option value="High">
                                    High Priority
                                </option>

                            </select>

                        </div>

                        <div class="col-md-8">

                            <label class="form-label fw-semibold">
                                Special Remarks
                            </label>

                            <textarea class="form-control"
                                      name="special_remarks"
                                      rows="3"
                                      placeholder="Write inspection instructions, emergency notes, special tasks etc..."></textarea>

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

                                    After creating this schedule,
                                    the assigned auditor will perform inspection
                                    for the selected coach before the due date.

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

                        Create Schedule

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
    const dueDate = new Date(nextDueDate + 'T10:00:00');
    const futureDate = new Date(dueDate);

    futureDate.setDate(futureDate.getDate() + 1);

    assignmentDateTime.value = nextDueDate + 'T10:00';
    nextDueDateInput.min = futureDate.toISOString().slice(0, 10);
    nextDueDateInput.value = '';
}

</script>

</body>
</html>

<?php
$conn->close();
?>
