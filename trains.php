<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    

    if ($action === 'add_train') {
        $user_id = (int)$_SESSION['user_id'];
        $train_no = trim($_POST['train_no'] ?? '');
        $train_name = trim($_POST['train_name'] ?? '');
        $No_of_Coach = (int)($_POST['No_of_Coach'] ?? 0);
        $status = $_POST['status'] ?? 'Active';

        if ($train_no === '' || $train_name === '' || $No_of_Coach <= 0) {
            $message = "Please fill all required fields.";
            $message_type = "danger";
        } else {
            
           // Check duplicate train number for same user
$check_query = "SELECT train_info_id 
FROM fdss_train_information 
WHERE user_id = ? AND train_no = ?";

$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("is", $user_id, $train_no);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {

    $message = "Train number already exists!";
    $message_type = "danger";

} else {

    $insert_query = "INSERT INTO fdss_train_information 
    (user_id, train_no, train_name, No_of_Coach, status) 
    VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insert_query);

    $stmt->bind_param(
        "issis",
        $user_id,
        $train_no,
        $train_name,
        $No_of_Coach,
        $status
    );

    if ($stmt->execute()) {
        $message = "Train added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding train!";
        $message_type = "danger";
    }

    $stmt->close();
}

$check_stmt->close();
        }
    }

    elseif ($action === 'edit_train') {
        $train_info_id = (int)($_POST['train_info_id'] ?? 0);
        $user_id = (int)$_SESSION['user_id'];
        $train_no = trim($_POST['train_no'] ?? '');
        $train_name = trim($_POST['train_name'] ?? '');
        $No_of_Coach = (int)($_POST['No_of_Coach'] ?? 0);
        $status = $_POST['status'] ?? 'Active';

        $update_query = "UPDATE fdss_train_information 
            SET train_no = ?, train_name = ?, No_of_Coach = ?, status = ?
            WHERE train_info_id = ? AND user_id = ?";

        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssisii", $train_no, $train_name, $No_of_Coach, $status, $train_info_id, $user_id);

        if ($stmt->execute()) {
            $message = "Train updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating train: " . $conn->error;
            $message_type = "danger";
        }

        $stmt->close();
    }

    elseif ($action === 'delete_train') {
        $train_info_id = (int)($_POST['train_info_id'] ?? 0);
        $user_id = (int)$_SESSION['user_id'];

        $delete_query = "DELETE FROM fdss_train_information WHERE train_info_id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $train_info_id, $user_id);

        if ($stmt->execute()) {
            $message = "Train deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting train: " . $conn->error;
            $message_type = "danger";
        }

        $stmt->close();
    }
}

$trains = [];
$user_id = (int)$_SESSION['user_id'];

$query = "SELECT train_info_id, user_id, train_no, train_name, No_of_Coach, status, created_at, updated_at 
          FROM fdss_train_information 
          WHERE user_id = ? 
          ORDER BY train_info_id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $trains[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Train Management - FDSS Dashboard</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>

<?php include('includes/navbar.php'); ?>

<div class="sidebar-container">
    <?php include('includes/sidebar.php'); ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Train Management</h1>
                <p class="page-header-subtitle">Manage railway trains and assignments</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" id="addTrainBtn" data-bs-toggle="modal" data-bs-target="#trainModal">
                    <i class="bi bi-plus-circle"></i> Add Train
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-train-freight-front"></i>
                    Trains List (<?php echo count($trains); ?> Total)
                </h5>
            </div>

            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Train Number</th>
                                <th>Train Route / Name</th>
                                <th>Coaches</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($trains)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No trains found. Click "Add Train" to create one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($trains as $train): ?>
                                <tr data-id="<?php echo htmlspecialchars($train['train_info_id']); ?>">
                                    <td><?php echo htmlspecialchars($train['train_no']); ?></td>
                                    <td><?php echo htmlspecialchars($train['train_name']); ?></td>
                                    <td><?php echo htmlspecialchars($train['No_of_Coach']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = ($train['status'] === 'Active') ? 'badge-success' : 'badge-danger';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($train['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($train['created_at'])); ?></td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="editTrain(
                                                '<?php echo htmlspecialchars($train['train_info_id']); ?>',
                                                '<?php echo htmlspecialchars(addslashes($train['train_no'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($train['train_name'])); ?>',
                                                '<?php echo htmlspecialchars($train['No_of_Coach']); ?>',
                                                '<?php echo htmlspecialchars($train['status']); ?>'
                                            )"
                                            data-bs-toggle="modal"
                                            data-bs-target="#trainModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- <button 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteTrain('<?php echo htmlspecialchars($train['train_info_id']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button> -->
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

<div class="modal fade" id="trainModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Train</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add_train">
                <input type="hidden" name="train_info_id" id="editingTrainId" value="">

                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Train Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="trainNumber" name="train_no" placeholder="e.g., 2245" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Train Route / Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="trainRoute" name="train_name" placeholder="Enter route or name" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Number of Coaches <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="trainCoaches" name="No_of_Coach" min="1" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="trainStatus" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitTrainBtn">Add Train</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete_train">
    <input type="hidden" name="train_info_id" id="deleteTrainId">
</form>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>

<script>
const trainModalEl = document.getElementById('trainModal');
const trainModal = bootstrap.Modal.getOrCreateInstance(trainModalEl);

const addTrainBtn = document.getElementById('addTrainBtn');
const modalTitle = trainModalEl.querySelector('.modal-title');
const submitTrainBtn = document.getElementById('submitTrainBtn');
const formAction = document.getElementById('formAction');

function resetTrainForm() {
    document.getElementById('editingTrainId').value = '';
    document.getElementById('trainNumber').value = '';
    document.getElementById('trainRoute').value = '';
    document.getElementById('trainCoaches').value = '';
    document.getElementById('trainStatus').value = 'Active';

    formAction.value = 'add_train';
    modalTitle.textContent = 'Add New Train';
    submitTrainBtn.textContent = 'Add Train';
}

function editTrain(id, trainNo, trainName, coaches, status) {
    document.getElementById('editingTrainId').value = id;
    document.getElementById('trainNumber').value = trainNo;
    document.getElementById('trainRoute').value = trainName;
    document.getElementById('trainCoaches').value = coaches;
    document.getElementById('trainStatus').value = status;

    formAction.value = 'edit_train';
    modalTitle.textContent = 'Edit Train';
    submitTrainBtn.textContent = 'Update Train';
}

function deleteTrain(id) {
    if (confirm('Are you sure you want to delete this train?')) {
        document.getElementById('deleteTrainId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

addTrainBtn.addEventListener('click', function () {
    resetTrainForm();
});

trainModalEl.addEventListener('hidden.bs.modal', function () {
    resetTrainForm();
});
</script>

</body>
</html>

<?php
$conn->close();
?>