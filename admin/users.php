<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['role'] !== 'SUPER_ADMIN' && $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="sidebar-container">
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h5><i class="bi bi-shield-lock"></i> Admin Panel</h5>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="users.php" class="nav-link active">
                <i class="bi bi-people"></i> User Management
            </a>
            <a href="systems.php" class="nav-link">
                <i class="bi bi-gear"></i> System Settings
            </a>
            <a href="reports.php" class="nav-link">
                <i class="bi bi-file-earmark-text"></i> Reports
            </a>
            <hr>
            <a href="logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>User Management</h1>
                <p class="page-header-subtitle">View and manage all system users</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add New User
                </button>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5>All Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT user_id, username, full_name, email, role, status, created_at FROM fdss_users ORDER BY created_at DESC";
                            $result = $conn->query($query);
                            
                            if ($result->num_rows > 0) {
                                while ($user = $result->fetch_assoc()) {
                                    $status_badge = $user['status'] === 'Active' ? 
                                        '<span class="badge bg-success">Active</span>' : 
                                        '<span class="badge bg-danger">Inactive</span>';
                                    
                                    $role_badge = '<span class="badge bg-info">' . htmlspecialchars($user['role']) . '</span>';
                                    
                                    echo "<tr>
                                        <td>" . htmlspecialchars($user['user_id']) . "</td>
                                        <td>" . htmlspecialchars($user['username']) . "</td>
                                        <td>" . htmlspecialchars($user['full_name'] ?? 'N/A') . "</td>
                                        <td>" . htmlspecialchars($user['email']) . "</td>
                                        <td>{$role_badge}</td>
                                        <td>{$status_badge}</td>
                                        <td>" . date('d M Y', strtotime($user['created_at'])) . "</td>
                                        <td>
                                            <button class='btn btn-sm btn-outline-primary'>
                                                <i class='bi bi-pencil'></i>
                                            </button>
                                            <button class='btn btn-sm btn-outline-danger'>
                                                <i class='bi bi-trash'></i>
                                            </button>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center text-muted py-4'>No users found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <option value="SUPER_ADMIN">Super Admin</option>
                            <option value="ADMIN">Admin</option>
                            <option value="ORG_ADMIN">Organization Admin</option>
                            <option value="ORG_USER">Organization User</option>
                            <option value="AUDITOR">Auditor</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
