<?php 
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if user role is ORG_ADMIN
if ($_SESSION['role'] !== 'ORG_ADMIN') {
    header('Location: login.php?access=denied');
    exit;
}

$message = '';
$message_type = '';

// Handle Add/Edit Auditor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_auditor') {
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);
       
        $designation = $conn->real_escape_string($_POST['designation']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $password = $_POST['password'];
        $status = $_POST['status'];
        
        // Auto-generate username from name + 2 random digits
        $name_parts = explode(' ', trim($full_name));
        $first_name = strtolower(str_replace(' ', '', $name_parts[0]));
        $last_name = strtolower(str_replace(' ', '', $name_parts[1] ?? ''));
        $random_numbers = rand(10, 99);
        $username = $first_name . '_' . $last_name . '_' . $random_numbers;
        
        // Check if username already exists
        $check_query = "SELECT user_id FROM fdss_users WHERE username = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "Username already exists. Please try again.";
            $message_type = "danger";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert into fdss_users table with AUDITOR role
   $station_id = null;

            $insert_query = "INSERT INTO fdss_users (user_name, username, email, password_hash, phone, designation, role, station_id, status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, 'AUDITOR', ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
           $insert_stmt->bind_param(
    "ssssssisi",
    $full_name,
    $username,
    $email,
    $password_hash,
    $phone,
    $designation,
    $station_id,
    $status,
    $_SESSION['user_id']
);
            
            if ($insert_stmt->execute()) {
                $message = "Auditor added successfully! Generated Username: <strong>$username</strong>";
                $message_type = "success";
            } else {
                $message = "Error adding auditor: " . $conn->error;
                $message_type = "danger";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
    
    elseif ($action === 'edit_auditor') {
        $user_id = intval($_POST['user_id']);
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $email = $conn->real_escape_string($_POST['email']);

        $designation = $conn->real_escape_string($_POST['designation']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $status = $_POST['status'];
        
        // Verify that this auditor was created by the current user
        $verify_query = "SELECT user_id FROM fdss_users WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $user_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        
        if ($verify_stmt->get_result()->num_rows === 0) {
            $message = "You don't have permission to edit this auditor.";
            $message_type = "danger";
        } else {
            $update_query = "UPDATE fdss_users SET user_name = ?, email = ?, phone = ?, designation = ?, status = ? WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
            $update_stmt = $conn->prepare($update_query);
           $update_stmt->bind_param(
    "sssssii",
    $full_name,
    $email,
    $phone,
    $designation,
    $status,
    $user_id,
    $_SESSION['user_id']
);    
            if ($update_stmt->execute()) {
                $message = "Auditor updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating auditor: " . $conn->error;
                $message_type = "danger";
            }
            $update_stmt->close();
        }
        $verify_stmt->close();
    }
    
    elseif ($action === 'delete_auditor') {
        $user_id = intval($_POST['user_id']);
        
        // Verify that this auditor was created by the current user
        $verify_query = "SELECT user_id FROM fdss_users WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $user_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        
        if ($verify_stmt->get_result()->num_rows === 0) {
            $message = "You don't have permission to delete this auditor.";
            $message_type = "danger";
        } else {
            $delete_query = "DELETE FROM fdss_users WHERE user_id = ? AND role = 'AUDITOR' AND created_by_user_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("ii", $user_id, $_SESSION['user_id']);
            
            if ($delete_stmt->execute()) {
                $message = "Auditor deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting auditor: " . $conn->error;
                $message_type = "danger";
            }
            $delete_stmt->close();
        }
        $verify_stmt->close();
    }
}

// Fetch all auditors created by the logged-in user from database
$auditors = [];
$query = "SELECT user_id, user_name, email, designation, username, status, phone, created_by_user_id FROM fdss_users WHERE role = 'AUDITOR' AND created_by_user_id = ? ORDER BY user_id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $auditors[] = $row;
    }
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditor Management - FDSS Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
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
            <div class="page-header">
                <div>
                    <h1>Auditor Management</h1>
                    <p class="page-header-subtitle">Manage railway auditors and assignments</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" id="addAuditorBtn" data-bs-toggle="modal" data-bs-target="#auditorModal">
                        <i class="bi bi-plus-circle"></i> Add Auditor
                    </button>
                </div>
            </div>

            <!-- Display Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo ($message_type === 'success') ? 'check-circle' : 'exclamation-circle'; ?>-fill"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- AUDITORS TABLE -->
            <div class="content-card">
                <div class="card-header">
                    <h5><i class="bi bi-person-badge"></i> Auditors List (<?php echo count($auditors); ?> Total)</h5>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Designation</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($auditors)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No auditors found. Click "Add Auditor" to create one.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($auditors as $auditor): ?>
                                        <tr data-id="<?php echo htmlspecialchars($auditor['user_id']); ?>">
                                            <td><?php echo htmlspecialchars($auditor['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($auditor['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($auditor['email']); ?></td>
                                            <td><code><?php echo htmlspecialchars($auditor['username']); ?></code></td>
                                            <td><?php echo htmlspecialchars($auditor['designation'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($auditor['phone'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php 
                                              $status_class = ($auditor['status'] === 'Active') ? 'badge-success' : 'badge-danger';
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($auditor['status']); ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editAuditor(<?php echo htmlspecialchars($auditor['user_id']); ?>)" data-bs-toggle="modal" data-bs-target="#auditorModal">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <!-- <button class="btn btn-sm btn-outline-danger" onclick="deleteAuditor(<?php echo htmlspecialchars($auditor['user_id']); ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button> -->
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- AUDITOR MODAL -->
    <div class="modal fade" id="auditorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Auditor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" id="formAction" value="add_auditor">
                    <input type="hidden" name="user_id" id="editingAuditorId" value="">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="auditorName" name="full_name" placeholder="Enter full name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="auditorEmail" name="email" placeholder="Enter email address" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="auditorPhone" name="phone" placeholder="Enter phone number">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" id="auditorDesignation" name="designation" placeholder="Enter designation">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="auditorStatus" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group mb-3" id="passwordGroup">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="auditorPassword" name="password" placeholder="Enter password" required>
                            <small class="text-muted d-block mt-2">Username will be auto-generated from name + 2 random digits (e.g., akhil_gusain_23)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitAuditorBtn">Add Auditor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

           <?php include('includes/footer.php'); ?>

    <!-- <div id="footer-placeholder"></div> -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/layout.js"></script>
    
    <script>
        const auditorModalEl = document.getElementById('auditorModal');
        const auditorModal = bootstrap.Modal.getOrCreateInstance(auditorModalEl);
        const auditorForm = auditorModalEl.querySelector('form');
        const addAuditorBtn = document.getElementById('addAuditorBtn');
        const modalTitle = auditorModalEl.querySelector('.modal-title');
        const submitAuditorBtn = document.getElementById('submitAuditorBtn');
        const formAction = document.getElementById('formAction');
        const passwordGroup = document.getElementById('passwordGroup');

        function resetForm() {
            auditorForm.reset();
            document.getElementById('editingAuditorId').value = '';
            formAction.value = 'add_auditor';
            modalTitle.textContent = 'Add New Auditor';
            submitAuditorBtn.textContent = 'Add Auditor';
            if (passwordGroup) passwordGroup.style.display = 'block';
            document.getElementById('auditorPassword').required = true;
        }

        function editAuditor(userId) {
            // Fetch auditor data from the table row
            const row = document.querySelector('tr[data-id="' + userId + '"]');
            if (!row) return;

            const cells = row.querySelectorAll('td');
            document.getElementById('auditorName').value = cells[1].textContent.trim();
            document.getElementById('auditorEmail').value = cells[2].textContent.trim();
            document.getElementById('auditorPhone').value = cells[5].textContent.trim();
            document.getElementById('auditorDesignation').value = cells[4].textContent.trim();
            document.getElementById('auditorStatus').value = cells[6].querySelector('.badge').textContent.trim();
            
            document.getElementById('editingAuditorId').value = userId;
            formAction.value = 'edit_auditor';
            modalTitle.textContent = 'Edit Auditor';
            submitAuditorBtn.textContent = 'Update Auditor';
            if (passwordGroup) passwordGroup.style.display = 'none';
            document.getElementById('auditorPassword').required = false;
        }

        function deleteAuditor(userId) {
            if (!confirm('Are you sure you want to delete this auditor?')) {
                return;
            }

            // Create a hidden form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_auditor">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        addAuditorBtn.addEventListener('click', function() {
            resetForm();
            auditorModal.show();
        });

        // Close modal event - reset form
        auditorModalEl.addEventListener('hidden.bs.modal', function() {
            resetForm();
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
