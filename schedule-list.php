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

        $priority = $_POST['priority'] ?? 'Normal';

        $special_remarks = trim($_POST['special_remarks'] ?? '');

        $status = $_POST['status'] ?? 'Assigned';

        if (
            $schedule_id <= 0 ||
            $auditor_id <= 0 ||
            $assignment_date_time === ''
        ) {

            $message = "Please fill all required fields.";
            $message_type = "danger";

        } else {

            $update_query = "UPDATE fdss_coach_schedule SET

                auditor_id = ?,
                assignment_date_time = ?,
                priority = ?,
                special_remarks = ?,
                status = ?

                WHERE schedule_id = ?
                AND user_id = ?";

            $stmt = $conn->prepare($update_query);

            if ($stmt) {

                $stmt->bind_param(
                    "issssii",
                    $auditor_id,
                    $assignment_date_time,
                    $priority,
                    $special_remarks,
                    $status,
                    $schedule_id,
                    $user_id
                );

                if ($stmt->execute()) {

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

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    elseif ($action === 'delete_schedule') {

        $schedule_id = (int) ($_POST['schedule_id'] ?? 0);

        $coach_no = trim($_POST['coach_no'] ?? '');

        $train_info_id = (int) ($_POST['train_info_id'] ?? 0);

        $delete_query = "DELETE FROM fdss_coach_schedule
                         WHERE schedule_id = ?
                         AND user_id = ?";

        $stmt = $conn->prepare($delete_query);

        if ($stmt) {

            $stmt->bind_param(
                "ii",
                $schedule_id,
                $user_id
            );

            if ($stmt->execute()) {

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

                $message = "Error deleting schedule.";
                $message_type = "danger";
            }

            $stmt->close();
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

$query = "SELECT

            s.schedule_id,
            s.coach_no,
            s.train_info_id,
            s.last_inspection_date,
            s.next_due_date,
            s.status,
            s.assignment_date_time,
            s.priority,
            s.special_remarks,

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

          ORDER BY s.schedule_id DESC";

$stmt = $conn->prepare($query);

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    $stmt->close();
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

</head>

<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">

<?php include('includes/sidebar.php'); ?>

<main class="main-content">

    <div class="page-header">

        <div>

            <h1>
                Inspection Schedule List
            </h1>

            <p class="page-header-subtitle">
                Manage and update all coach inspection schedules.
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

        <div class="card-header">

            <h5>

                <i class="bi bi-table"></i>

                Schedule List

            </h5>

        </div>

        <div class="card-body">

            <div class="table-wrapper">

                <table class="table table-hover">

                    <thead>

                    <tr>

                        <th>Coach</th>
                        <th>Train</th>
                        <th>Auditor</th>
                        <th>Inspection Date</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Actions</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($schedules)): ?>

                        <tr>

                            <td colspan="9"
                                class="text-center text-muted py-4">

                                No schedules found.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($schedules as $schedule): ?>

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
                                    echo $schedule['next_due_date']
                                        ? date('d M Y', strtotime($schedule['next_due_date']))
                                        : '-';
                                    ?>

                                </td>

                                <td>

                                    <?php
                                    $priority_class =
                                        ($schedule['priority'] === 'High')
                                        ? 'badge-danger'
                                        : 'badge-info';
                                    ?>

                                    <span class="badge <?php echo $priority_class; ?>">

                                        <?php echo e($schedule['priority']); ?>

                                    </span>

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

                                    <?php echo e($schedule['special_remarks']); ?>

                                </td>

                                <td>

                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editSchedule(
                                            '<?php echo e($schedule['schedule_id']); ?>',
                                            '<?php echo e($schedule['auditor_user_id']); ?>',
                                            '<?php echo e($schedule['assignment_date_time']); ?>',
                                            '<?php echo e($schedule['priority']); ?>',
                                            '<?php echo e(addslashes($schedule['special_remarks'])); ?>',
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
                                Priority
                            </label>

                            <select class="form-select"
                                    name="priority"
                                    id="editPriority">

                                <option value="Normal">
                                    Normal
                                </option>

                                <option value="High">
                                    High
                                </option>

                            </select>

                        </div>

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

                    <div class="mt-3">

                        <label class="form-label">
                            Special Remarks
                        </label>

                        <textarea class="form-control"
                                  name="special_remarks"
                                  rows="3"
                                  id="editRemarks"></textarea>

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

function editSchedule(
    scheduleId,
    auditorId,
    assignmentDateTime,
    priority,
    remarks,
    status
) {

    document.getElementById('editScheduleId').value = scheduleId;

    document.getElementById('editAuditor').value = auditorId;

    document.getElementById('editDateTime').value =
        assignmentDateTime.replace(' ', 'T');

    document.getElementById('editPriority').value = priority;

    document.getElementById('editRemarks').value = remarks;

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