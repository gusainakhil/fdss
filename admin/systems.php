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
    <title>System Settings - Admin</title>
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
            <a href="users.php" class="nav-link">
                <i class="bi bi-people"></i> User Management
            </a>
            <a href="systems.php" class="nav-link active">
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
                <h1>System Settings</h1>
                <p class="page-header-subtitle">Configure system-wide settings</p>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h5>General Settings</h5>
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">System Name</label>
                        <input type="text" class="form-control" value="FDSS - Fire Detection & Suppression System">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Administrator Email</label>
                        <input type="email" class="form-control" value="admin@fdss.gov.in">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Timezone</label>
                        <select class="form-select">
                            <option>India Standard Time (IST)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        <div class="content-card mt-4">
            <div class="card-header">
                <h5>Database Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Host:</strong> localhost:3306</p>
                        <p><strong>Database:</strong> fdssbeatleanalyt_Database</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Server Version:</strong> MariaDB 10.11.16</p>
                        <p><strong>PHP Version:</strong> 8.4.20</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/layout.js"></script>
</body>
</html>
<?php $conn->close(); ?>
