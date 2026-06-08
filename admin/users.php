<?php
require_once __DIR__ . '/_auth.php';

$active_page = 'users';
$message = '';
$message_type = '';

function admin_station_exists($conn, $station_id)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM fdss_stations WHERE station_id = ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $station_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int) ($row['total'] ?? 0) > 0;
}

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($request_method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user' || $action === 'edit_user') {
        $user_name = trim($_POST['user_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $station_id = (int) ($_POST['station_id'] ?? 0);
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        $password = $_POST['password'] ?? '';
        $allowed_statuses = ['Active', 'Inactive'];

        if ($user_name === '' || $username === '' || $email === '' || $phone === '' || $station_id <= 0 || $start_date === '' || $end_date === '' || !in_array($status, $allowed_statuses, true)) {
            $message = 'Please fill all required fields, contact, dates and select station.';
            $message_type = 'danger';
        } elseif ($end_date < $start_date) {
            $message = 'End date cannot be before start date.';
            $message_type = 'danger';
        } elseif (!admin_station_exists($conn, $station_id)) {
            $message = 'Selected station is invalid.';
            $message_type = 'danger';
        } elseif ($action === 'add_user') {
            if ($password === '') {
                $message = 'Please enter password.';
                $message_type = 'danger';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $created_by = (int) $_SESSION['user_id'];
                $role = 'ORG_ADMIN';

                $stmt = $conn->prepare("INSERT INTO fdss_users (user_name, username, email, password_hash, phone, designation, role, station_id, start_date, end_date, status, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if ($stmt) {
                    $stmt->bind_param('sssssssisssi', $user_name, $username, $email, $password_hash, $phone, $designation, $role, $station_id, $start_date, $end_date, $status, $created_by);

                    if ($stmt->execute()) {
                        $message = 'ORG admin added successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Unable to add ORG admin. Username or email may already exist.';
                        $message_type = 'danger';
                    }

                    $stmt->close();
                }
            }
        } elseif ($action === 'edit_user') {
            $target_user_id = (int) ($_POST['user_id'] ?? 0);

            if ($target_user_id <= 0) {
                $message = 'Invalid user selected.';
                $message_type = 'danger';
            } elseif ($password !== '') {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE fdss_users SET user_name = ?, username = ?, email = ?, password_hash = ?, phone = ?, designation = ?, station_id = ?, start_date = ?, end_date = ?, status = ? WHERE user_id = ? AND role = 'ORG_ADMIN'");

                if ($stmt) {
                    $stmt->bind_param('ssssssisssi', $user_name, $username, $email, $password_hash, $phone, $designation, $station_id, $start_date, $end_date, $status, $target_user_id);

                    if ($stmt->execute()) {
                        $message = 'ORG admin updated successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Unable to update ORG admin. Username or email may already exist.';
                        $message_type = 'danger';
                    }

                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("UPDATE fdss_users SET user_name = ?, username = ?, email = ?, phone = ?, designation = ?, station_id = ?, start_date = ?, end_date = ?, status = ? WHERE user_id = ? AND role = 'ORG_ADMIN'");

                if ($stmt) {
                    $stmt->bind_param('sssssisssi', $user_name, $username, $email, $phone, $designation, $station_id, $start_date, $end_date, $status, $target_user_id);

                    if ($stmt->execute()) {
                        $message = 'ORG admin updated successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'Unable to update ORG admin. Username or email may already exist.';
                        $message_type = 'danger';
                    }

                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'toggle_status') {
        $target_user_id = (int) ($_POST['user_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';

        if ($target_user_id > 0 && in_array($new_status, ['Active', 'Inactive'], true)) {
            $stmt = $conn->prepare("UPDATE fdss_users SET status = ? WHERE user_id = ? AND role = 'ORG_ADMIN'");

            if ($stmt) {
                $stmt->bind_param('si', $new_status, $target_user_id);
                $stmt->execute();
                $stmt->close();
                $message = 'ORG admin status updated.';
                $message_type = 'success';
            }
        }
    }
}

$selected_status = $_GET['status'] ?? 'all';
$selected_station = $_GET['station_id'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$stations = [];
$station_sql = "SELECT
                    st.station_id,
                    st.station_name,
                    d.division_name,
                    z.zone_name
                FROM fdss_stations st
                INNER JOIN fdss_divisions d ON d.division_id = st.division_id
                INNER JOIN fdss_zones z ON z.zone_id = d.zone_id
                ORDER BY z.zone_name ASC, d.division_name ASC, st.station_name ASC";
$result = $conn->query($station_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stations[] = $row;
    }
}

$where = "WHERE u.role = 'ORG_ADMIN'";
$types = '';
$values = [];

if ($selected_status !== 'all') {
    $where .= ' AND u.status = ?';
    $types .= 's';
    $values[] = $selected_status;
}

if ($selected_station !== 'all') {
    $where .= ' AND u.station_id = ?';
    $types .= 'i';
    $values[] = (int) $selected_station;
}

if ($search !== '') {
    $where .= ' AND (u.username LIKE ? OR u.user_name LIKE ? OR u.email LIKE ?)';
    $types .= 'sss';
    $search_like = '%' . $search . '%';
    $values[] = $search_like;
    $values[] = $search_like;
    $values[] = $search_like;
}

$users = [];
$query = "SELECT
            u.user_id,
            u.user_name,
            u.username,
            u.email,
            u.phone,
            u.designation,
            u.station_id,
            u.start_date,
            u.end_date,
            u.role,
            u.status,
            u.created_at,
            st.station_name,
            d.division_name,
            z.zone_name
        FROM fdss_users u
        LEFT JOIN fdss_stations st ON st.station_id = u.station_id
        LEFT JOIN fdss_divisions d ON d.division_id = st.division_id
        LEFT JOIN fdss_zones z ON z.zone_id = d.zone_id
        $where
        ORDER BY u.created_at DESC";
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
    <title>ORG Admin Management - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body class="admin-page-body">
<?php include __DIR__ . '/navbar.php'; ?>

<div class="admin-shell">
    <?php include __DIR__ . '/_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>ORG Admin Management</h1>
                <p class="page-header-subtitle">Create, edit, assign station and control ORG admin access</p>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add ORG Admin
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
                        <label class="form-label">Station</label>
                        <select class="form-select" name="station_id">
                            <option value="all">All Stations</option>
                            <?php foreach ($stations as $station): ?>
                                <?php $station_label = $station['station_name'] . ' - ' . $station['division_name'] . ', ' . $station['zone_name']; ?>
                                <option value="<?php echo e($station['station_id']); ?>" <?php echo (string) $selected_station === (string) $station['station_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($station_label); ?>
                                </option>
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
                <h5><i class="bi bi-person-badge"></i> ORG Admins</h5>
                <span class="text-muted small"><?php echo count($users); ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Station</th>
                            <th>Designation</th>
                            <th>Access Dates</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No ORG admins found.</td></tr>
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
                                    <td>
                                        <strong><?php echo e($user['station_name'] ?: '-'); ?></strong><br>
                                        <span class="text-muted small">
                                            <?php echo e(trim(($user['division_name'] ?: '-') . ' / ' . ($user['zone_name'] ?: '-'))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($user['designation'] ?: '-'); ?></td>
                                    <td>
                                        <?php echo e($user['start_date'] ? date('d M Y', strtotime($user['start_date'])) : '-'); ?><br>
                                        <span class="text-muted small">to <?php echo e($user['end_date'] ? date('d M Y', strtotime($user['end_date'])) : '-'); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] === 'Active' ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                            <?php echo e($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e(date('d M Y', strtotime($user['created_at']))); ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a class="btn btn-sm btn-outline-info" href="user-view.php?user_id=<?php echo e($user['user_id']); ?>" target="_blank">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo e($user['user_id']); ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo e($user['user_id']); ?>">
                                                <input type="hidden" name="new_status" value="<?php echo e($next_status); ?>">
                                                <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                    <?php echo $next_status === 'Active' ? 'Activate' : 'Deactivate'; ?>
                                                </button>
                                            </form>
                                        </div>
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
                    <h5 class="modal-title">Add ORG Admin</h5>
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
                            <label class="form-label">Contact</label>
                            <input type="text" class="form-control" name="phone" required>
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
                            <label class="form-label">Station</label>
                            <select class="form-select" name="station_id" required>
                                <option value="">Select Station</option>
                                <?php foreach ($stations as $station): ?>
                                    <?php $station_label = $station['station_name'] . ' - ' . $station['division_name'] . ', ' . $station['zone_name']; ?>
                                    <option value="<?php echo e($station['station_id']); ?>"><?php echo e($station_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2"></i> Add ORG Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($users as $user): ?>
    <div class="modal fade" id="editUserModal<?php echo e($user['user_id']); ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" value="<?php echo e($user['user_id']); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit ORG Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="user_name" value="<?php echo e($user['user_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo e($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo e($user['phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" name="designation" value="<?php echo e($user['designation']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Station</label>
                                <select class="form-select" name="station_id" required>
                                    <option value="">Select Station</option>
                                    <?php foreach ($stations as $station): ?>
                                        <?php $station_label = $station['station_name'] . ' - ' . $station['division_name'] . ', ' . $station['zone_name']; ?>
                                        <option value="<?php echo e($station['station_id']); ?>" <?php echo (int) $user['station_id'] === (int) $station['station_id'] ? 'selected' : ''; ?>>
                                            <?php echo e($station_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo e($user['start_date']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo e($user['end_date']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo $user['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2"></i> Update ORG Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
