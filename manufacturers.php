<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$message_type = '';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $company_name = trim($_POST['company_name'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $email_id = trim($_POST['email_id'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if ($action === 'add_manufacturer') {
        if ($company_name === '') {
            $message = "Company name is required.";
            $message_type = "danger";
        } else {
            $query = "INSERT INTO fdss_manufacturers 
                (company_name, name, mobile_number, email_id, address, status, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssi", $company_name, $name, $mobile_number, $email_id, $address, $status, $user_id);

            if ($stmt->execute()) {
                $message = "Manufacturer added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding manufacturer.";
                $message_type = "danger";
            }

            $stmt->close();
        }
    }

    if ($action === 'edit_manufacturer') {
        $manufacturer_id = (int) ($_POST['manufacturer_id'] ?? 0);

        $query = "UPDATE fdss_manufacturers 
                  SET company_name = ?, name = ?, mobile_number = ?, email_id = ?, address = ?, status = ?
                  WHERE manufacturer_id = ? AND user_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssii", $company_name, $name, $mobile_number, $email_id, $address, $status, $manufacturer_id, $user_id);

        if ($stmt->execute()) {
            $message = "Manufacturer updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating manufacturer.";
            $message_type = "danger";
        }

        $stmt->close();
    }

    if ($action === 'delete_manufacturer') {
        $manufacturer_id = (int) ($_POST['manufacturer_id'] ?? 0);

        $query = "DELETE FROM fdss_manufacturers WHERE manufacturer_id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $manufacturer_id, $user_id);

        if ($stmt->execute()) {
            $message = "Manufacturer deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting manufacturer.";
            $message_type = "danger";
        }

        $stmt->close();
    }
}

$manufacturers = [];

$query = "SELECT * FROM fdss_manufacturers WHERE user_id = ? ORDER BY manufacturer_id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $manufacturers[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manufacturer Management - FDSS Dashboard</title>

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
                <h1>Manufacturer Management</h1>
                <p class="page-header-subtitle">Add and manage OEM / manufacturer company details</p>
            </div>

            <div class="page-header-actions">
                <button class="btn btn-primary" id="addManufacturerBtn" data-bs-toggle="modal" data-bs-target="#manufacturerModal">
                    <i class="bi bi-plus-circle"></i> Add Manufacturer
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show">
                <?php echo e($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="card-header">
                <h5><i class="bi bi-building-gear"></i> Manufacturer List (<?php echo count($manufacturers); ?> Total)</h5>
            </div>

            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Contact personally Name</th>
                                <th>Mobile Number</th>
                                <th>Email ID</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($manufacturers)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No manufacturers found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($manufacturers as $manufacturer): ?>
                                <tr>
                                    <td><strong><?php echo e($manufacturer['company_name']); ?></strong></td>
                                    <td><?php echo e($manufacturer['name'] ?: '-'); ?></td>
                                    <td><?php echo e($manufacturer['mobile_number'] ?: '-'); ?></td>
                                    <td><?php echo e($manufacturer['email_id'] ?: '-'); ?></td>
                                    <td><?php echo e($manufacturer['address'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($manufacturer['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo e($manufacturer['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button 
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="editManufacturer(
                                                '<?php echo e($manufacturer['manufacturer_id']); ?>',
                                                '<?php echo e(addslashes($manufacturer['company_name'])); ?>',
                                                '<?php echo e(addslashes($manufacturer['name'])); ?>',
                                                '<?php echo e(addslashes($manufacturer['mobile_number'])); ?>',
                                                '<?php echo e(addslashes($manufacturer['email_id'])); ?>',
                                                '<?php echo e(addslashes($manufacturer['address'])); ?>',
                                                '<?php echo e($manufacturer['status']); ?>'
                                            )"
                                            data-bs-toggle="modal"
                                            data-bs-target="#manufacturerModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <button 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteManufacturer('<?php echo e($manufacturer['manufacturer_id']); ?>')">
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

<div class="modal fade" id="manufacturerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color:#3c8dbc;">
                <h5 class="modal-title" id="manufacturerModalTitle">
                    <i class="bi bi-building-add"></i> Add Manufacturer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add_manufacturer">
                <input type="hidden" name="manufacturer_id" id="manufacturerId">

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="companyName" name="company_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact personally Name</label>
                        <input type="text" class="form-control" id="name" name="name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobileNumber" name="mobile_number">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email ID</label>
                        <input type="email" class="form-control" id="emailId" name="email_id">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        Save Manufacturer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete_manufacturer">
    <input type="hidden" name="manufacturer_id" id="deleteManufacturerId">
</form>

<?php include('includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/layout.js"></script>

<script>
const manufacturerModalTitle = document.getElementById('manufacturerModalTitle');
const submitBtn = document.getElementById('submitBtn');

function resetManufacturerForm() {
    document.getElementById('formAction').value = 'add_manufacturer';
    document.getElementById('manufacturerId').value = '';
    document.getElementById('companyName').value = '';
    document.getElementById('name').value = '';
    document.getElementById('mobileNumber').value = '';
    document.getElementById('emailId').value = '';
    document.getElementById('address').value = '';
    document.getElementById('status').value = 'Active';

    manufacturerModalTitle.innerHTML = '<i class="bi bi-building-add"></i> Add Manufacturer';
    submitBtn.textContent = 'Save Manufacturer';
}

function editManufacturer(id, companyName, name, mobileNumber, emailId, address, status) {
    document.getElementById('formAction').value = 'edit_manufacturer';
    document.getElementById('manufacturerId').value = id;
    document.getElementById('companyName').value = companyName;
    document.getElementById('name').value = name;
    document.getElementById('mobileNumber').value = mobileNumber;
    document.getElementById('emailId').value = emailId;
    document.getElementById('address').value = address;
    document.getElementById('status').value = status;

    manufacturerModalTitle.innerHTML = '<i class="bi bi-pencil-square"></i> Edit Manufacturer';
    submitBtn.textContent = 'Update Manufacturer';
}

function deleteManufacturer(id) {
    if (confirm('Are you sure you want to delete this manufacturer?')) {
        document.getElementById('deleteManufacturerId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.getElementById('addManufacturerBtn').addEventListener('click', resetManufacturerForm);

document.getElementById('manufacturerModal').addEventListener('hidden.bs.modal', resetManufacturerForm);
</script>

</body>
</html>

<?php
$conn->close();
?>