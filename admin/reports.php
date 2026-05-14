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
    <title>Reports - Admin</title>
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
            <a href="systems.php" class="nav-link">
                <i class="bi bi-gear"></i> System Settings
            </a>
            <a href="reports.php" class="nav-link active">
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
                <h1>System Reports</h1>
                <p class="page-header-subtitle">View and generate system reports</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-header">
                        <h5>User Activity Report</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Generate reports on user login activities and system usage.</p>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Generate Report
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-header">
                        <h5>Inspection Reports</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">View all inspection reports and audit logs.</p>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Generate Report
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-header">
                        <h5>Train & Coach Report</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Generate reports on registered trains and coaches.</p>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Generate Report
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-header">
                        <h5>Inventory Report</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">View inventory status and equipment logs.</p>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Generate Report
                        </button>
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
