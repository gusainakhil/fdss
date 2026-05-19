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

function field_value($value)
{
    $value = trim((string) $value);
    return $value !== '' ? $value : 'NA';
}

$message = '';
$message_type = '';
$active_tab = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $active_tab = 'profile';
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($email === '') {
            $message = 'Email address is required.';
            $message_type = 'danger';
        } else {
            $update_query = "UPDATE fdss_users
                             SET email = ?,
                                 phone = ?,
                                 designation = ?,
                                 address = ?
                             WHERE user_id = ?";

            $stmt = $conn->prepare($update_query);

            if ($stmt) {
                $stmt->bind_param(
                    "ssssi",
                    $email,
                    $phone,
                    $designation,
                    $address,
                    $user_id
                );

                if ($stmt->execute()) {
                    $message = 'Profile updated successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating profile.';
                    $message_type = 'danger';
                }

                $stmt->close();
            } else {
                $message = 'Profile Update SQL Error: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'update_password') {
        $active_tab = 'password';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            $message = 'Please fill in all password fields.';
            $message_type = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New password and confirm password do not match.';
            $message_type = 'danger';
        } elseif (strlen($new_password) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $message_type = 'danger';
        } else {
            $password_stmt = $conn->prepare("SELECT password_hash FROM fdss_users WHERE user_id = ? LIMIT 1");

            if ($password_stmt) {
                $password_stmt->bind_param("i", $user_id);
                $password_stmt->execute();
                $password_result = $password_stmt->get_result();
                $password_row = $password_result->fetch_assoc();
                $password_stmt->close();

                if (!$password_row || !password_verify($current_password, $password_row['password_hash'])) {
                    $message = 'Current password is incorrect.';
                    $message_type = 'danger';
                } else {
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_password_stmt = $conn->prepare("UPDATE fdss_users SET password_hash = ? WHERE user_id = ?");

                    if ($update_password_stmt) {
                        $update_password_stmt->bind_param("si", $new_password_hash, $user_id);

                        if ($update_password_stmt->execute()) {
                            $message = 'Password updated successfully.';
                            $message_type = 'success';
                        } else {
                            $message = 'Error updating password.';
                            $message_type = 'danger';
                        }

                        $update_password_stmt->close();
                    } else {
                        $message = 'Password Update SQL Error: ' . $conn->error;
                        $message_type = 'danger';
                    }
                }
            } else {
                $message = 'Password Check SQL Error: ' . $conn->error;
                $message_type = 'danger';
            }
        }
    }
}

$user = [
    'username' => '',
    'user_name' => '',
    'email' => '',
    'phone' => '',
    'designation' => '',
    'address' => '',
    'role' => '',
    'status' => ''
];

$user_query = "SELECT
                  username,
                  user_name,
                  email,
                  phone,
                  designation,
                  address,
                  role,
                  status
               FROM fdss_users
               WHERE user_id = ?
               LIMIT 1";

$stmt = $conn->prepare($user_query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user = $row;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - FDSS Dashboard</title>
    
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
                    <h1>Settings</h1>
                    <p class="page-header-subtitle">
                        <a href="index.php" class="text-primary text-decoration-none">Dashboard</a>
                        <span class="text-muted"> / </span>Settings
                    </p>
                </div>
            </div>

            <?php if ($message): ?>

                <div class="alert alert-<?php echo e($message_type); ?> alert-dismissible fade show">
                    <?php echo e($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

            <?php endif; ?>

            <!-- TABS -->
            <div class="content-card">
                <div class="card-header" style="border-bottom: none; padding-bottom: 0;">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#profileTab">
                                <i class="bi bi-person-circle"></i> User Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab === 'password' ? 'active' : ''; ?>" data-bs-toggle="tab" href="#passwordTab">
                                <i class="bi bi-key"></i> Change Password
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- TAB CONTENT -->
                <div class="tab-content">
                    <!-- USER PROFILE TAB -->
                    <div id="profileTab" class="tab-pane fade <?php echo $active_tab === 'profile' ? 'show active' : ''; ?>">
                        <div class="card-body">
                            <form id="profileForm" method="POST">
                                <input type="hidden" name="action" value="update_profile">

                                <h6 class="mb-4" style="font-weight: 600; text-transform: uppercase; color: #2c3e50;">
                                    <i class="bi bi-person-circle me-2"></i>My Profile Information
                                </h6>

                                <!-- Personal Information -->
                                <h6 class="mb-3 mt-4" style="font-weight: 600; text-transform: uppercase; color: #2c3e50;">Personal Information</h6>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Username</label>
                                            <input type="text" class="form-control" value="<?php echo e(field_value($user['username'])); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">User Name</label>
                                            <input type="text" class="form-control" value="<?php echo e(field_value($user['user_name'])); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Email Address</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" value="<?php echo e($user['phone']); ?>" placeholder="+91-XXXXXXXXXX">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Designation</label>
                                            <input type="text" class="form-control" name="designation" value="<?php echo e(field_value($user['designation'])); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Department</label>
                                            <input type="text" class="form-control" value="NA" disabled>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Address</label>
                                            <textarea class="form-control" name="address" rows="3"><?php echo e($user['address']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Role</label>
                                            <input type="text" class="form-control" value="<?php echo e(field_value($user['role'])); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Status</label>
                                            <input type="text" class="form-control" value="<?php echo e(field_value($user['status'])); ?>" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save Changes
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- CHANGE PASSWORD TAB -->
                    <div id="passwordTab" class="tab-pane fade <?php echo $active_tab === 'password' ? 'show active' : ''; ?>">
                        <div class="card-body">
                            <form id="passwordForm" method="POST">
                                <input type="hidden" name="action" value="update_password">

                                <h6 class="mb-4" style="font-weight: 600; text-transform: uppercase; color: #2c3e50;">
                                    <i class="bi bi-key me-2"></i>Change Your Password
                                </h6>

                                <div class="alert alert-info" role="alert">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Password must be at least 8 characters long and contain uppercase, lowercase, numbers, and special characters.
                                </div>

                                <div class="row g-3 mb-4" style="max-width: 500px;">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Current Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="currentPassword" name="current_password" placeholder="Enter your current password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">New Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="Enter new password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                            <div id="passwordStrength" style="margin-top: 8px; font-size: 0.85rem;">
                                                <div style="height: 4px; background: #e9ecef; border-radius: 2px; margin-bottom: 4px;">
                                                    <div id="strengthBar" style="height: 100%; width: 0%; background: #dc3545; border-radius: 2px; transition: all 0.3s ease;"></div>
                                                </div>
                                                <small id="strengthText" class="text-muted">Strength: Weak</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold">Confirm New Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Confirm new password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <div id="passwordMatch" class="d-none">
                                            <div class="alert alert-danger" id="mismatchAlert">
                                                <i class="bi bi-exclamation-circle me-2"></i>Passwords do not match
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Change Password
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

           <?php include('includes/footer.php'); ?>

    <!-- <div id="footer-placeholder"></div> -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/layout.js"></script>

    <script>
        /* ──────────────────────────────────────────
           Toggle password visibility
        ────────────────────────────────────────── */
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
            } else {
                field.type = 'password';
            }
        }

        /* ──────────────────────────────────────────
           Check password strength
        ────────────────────────────────────────── */
        document.getElementById('newPassword')?.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let strengthText = 'Weak';
            let color = '#dc3545';

            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;

            // Character type checks
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            // Calculate percentage and color
            const percentage = (strength / 6) * 100;
            if (percentage < 40) {
                strengthText = 'Weak';
                color = '#dc3545';
            } else if (percentage < 70) {
                strengthText = 'Fair';
                color = '#ffc107';
            } else if (percentage < 90) {
                strengthText = 'Good';
                color = '#20c997';
            } else {
                strengthText = 'Strong';
                color = '#198754';
            }

            document.getElementById('strengthBar').style.width = percentage + '%';
            document.getElementById('strengthBar').style.background = color;
            document.getElementById('strengthText').textContent = `Strength: ${strengthText}`;
        });

        /* ──────────────────────────────────────────
           Check password match
        ────────────────────────────────────────── */
        document.getElementById('confirmPassword')?.addEventListener('input', function() {
            const newPass = document.getElementById('newPassword').value;
            const confirmPass = this.value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirmPass && newPass !== confirmPass) {
                matchDiv.classList.remove('d-none');
            } else {
                matchDiv.classList.add('d-none');
            }
        });

    </script>
</body>
</html>
