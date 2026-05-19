<?php
require_once __DIR__ . '/_auth.php';

$active_page = 'users';
$message = '';
$message_type = '';

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($request_method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $user_name = trim($_POST['user_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        $password = $_POST['password'] ?? '';

        $allowed_roles = ['SUPER_ADMIN', 'ADMIN', 'ORG_ADMIN', 'ORG_USER', 'AUDITOR'];
        $allowed_statuses = ['Active', 'Inactive'];

        if ($user_name === '' || $username === '' || $email === '' || $password === '' || !in_array($role, $allowed_roles, true) || !in_array($status, $allowed_statuses, true)) {
            $message = 'Please fill all required fields.';
            $message_type = 'danger';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $created_by = (int) $_SESSION['user_id'];

            $stmt = $conn->prepare("INSERT INTO fdss_users (user_name, username, email, password_hash, phone, designation, role, status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param('ssssssssi', $user_name, $username, $email, $password_hash, $phone, $designation, $role, $status, $created_by);

                if ($stmt->execute()) {
                    $message = 'User added successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Unable to add user. Username or email may already exist.';
                    $message_type = 'danger';
                }

                $stmt->close();
            }
        }
    } elseif ($action === 'toggle_status') {
        $target_user_id = (int) ($_POST['user_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';

        if ($target_user_id > 0 && in_array($new_status, ['Active', 'Inactive'], true)) {
            $stmt = $conn->prepare("UPDATE fdss_users SET status = ? WHERE user_id = ?");

            if ($stmt) {
                $stmt->bind_param('si', $new_status, $target_user_id);
                $stmt->execute();
                $stmt->close();
                $message = 'User status updated.';
                $message_type = 'success';
            }
        }
    }
}

$selected_role = $_GET['role'] ?? 'all';
$selected_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = 'WHERE 1=1';
$types = '';
$values = [];

if ($selected_role !== 'all') {
    $where .= ' AND role = ?';
    $types .= 's';
    $values[] = $selected_role;
}

if ($selected_status !== 'all') {
    $where .= ' AND status = ?';
    $types .= 's';
    $values[] = $selected_status;
}

if ($search !== '') {
    $where .= ' AND (username LIKE ? OR user_name LIKE ? OR email LIKE ?)';
    $types .= 'sss';
    $search_like = '%' . $search . '%';
    $values[] = $search_like;
    $values[] = $search_like;
    $values[] = $search_like;
}

$users = [];
$query = "SELECT user_id, user_name, username, email, phone, designation, role, status, created_at FROM fdss_users $where ORDER BY created_at DESC";
$stmt = $conn->prepare($query);

if ($stmt) {
    if ($types !== '') {
        $refs = [];
        foreach ($values as $key => $value) {
            $refs[$key] = &$values[$key];
        }
        $stmt->bind_param($types, ...$refs);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $stmt->close();
}
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
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>

<div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>User Management</h1>
                <p class="page-header-subtitle">Create users, filter accounts, and control access status</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add User
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
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Username, name or email">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="all">All Roles</option>
                            <?php foreach (['SUPER_ADMIN', 'ADMIN', 'ORG_ADMIN', 'ORG_USER', 'AUDITOR'] as $role): ?>
                                <option value="<?php echo e($role); ?>" <?php echo $selected_role === $role ? 'selected' : ''; ?>><?php echo e($role); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="all">All Status</option>
                            <option value="Active" <?php echo $selected_status === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $selected_status === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-people"></i> Users</h5>
                <span class="text-muted small"><?php echo count($users); ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Designation</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php $next_status = $user['status'] === 'Active' ? 'Inactive' : 'Active'; ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($user['user_name']); ?></strong><br>
                                        <span class="text-muted small">@<?php echo e($user['username']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo e($user['email']); ?><br>
                                        <span class="text-muted small"><?php echo e($user['phone'] ?: '-'); ?></span>
                                    </td>
                                    <td><?php echo e($user['designation'] ?: '-'); ?></td>
                                    <td><span class="badge text-bg-info"><?php echo e($user['role']); ?></span></td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                            <?php echo e($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e(date('d M Y', strtotime($user['created_at']))); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo e($user['user_id']); ?>">
                                            <input type="hidden" name="new_status" value="<?php echo e($next_status); ?>">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                <?php echo $next_status === 'Active' ? 'Activate' : 'Deactivate'; ?>
                                            </button>
                                        </form>
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

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="user_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" name="designation">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="SUPER_ADMIN">SUPER_ADMIN</option>
                                <option value="ADMIN">ADMIN</option>
                                <option value="ORG_ADMIN">ORG_ADMIN</option>
                                <option value="ORG_USER">ORG_USER</option>
                                <option value="AUDITOR">AUDITOR</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
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
