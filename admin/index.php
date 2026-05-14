<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if user has admin role
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
    <title>Admin Dashboard - FDSS</title>
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
            <a href="index.php" class="nav-link active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="users.php" class="nav-link">
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
                <h1>Admin Dashboard</h1>
                <p class="page-header-subtitle">System administration and user management</p>
            </div>
            <div class="page-header-actions">
                <span class="badge bg-success">Role: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total Users</h6>
                                <h3 class="mb-0">
                                    <?php
                                    $result = $conn->query("SELECT COUNT(*) as count FROM fdss_users");
                                    $row = $result->fetch_assoc();
                                    echo $row['count'];
                                    ?>
                                </h3>
                            </div>
                            <div style="font-size: 2rem; color: #0d6efd;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Active Stations</h6>
                                <h3 class="mb-0">
                                    <?php
                                    $result = $conn->query("SELECT COUNT(*) as count FROM fdss_stations WHERE status = 'Active'");
                                    $row = $result->fetch_assoc();
                                    echo $row['count'];
                                    ?>
                                </h3>
                            </div>
                            <div style="font-size: 2rem; color: #198754;">
                                <i class="bi bi-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Trains Registered</h6>
                                <h3 class="mb-0">
                                    <?php
                                    $result = $conn->query("SELECT COUNT(*) as count FROM fdss_train_information WHERE status = 'Active'");
                                    $row = $result->fetch_assoc();
                                    echo $row['count'];
                                    ?>
                                </h3>
                            </div>
                            <div style="font-size: 2rem; color: #ffc107;">
                                <i class="bi bi-train-front-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="content-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Pending Inspections</h6>
                                <h3 class="mb-0">
                                    <?php
                                    $result = $conn->query("SELECT COUNT(*) as count FROM fdss_coach_schedule WHERE status = 'Pending'");
                                    $row = $result->fetch_assoc();
                                    echo $row['count'];
                                    ?>
                                </h3>
                            </div>
                            <div style="font-size: 2rem; color: #dc3545;">
                                <i class="bi bi-exclamation-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card mt-4">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <a href="users.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-person-plus"></i> Manage Users
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="systems.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-sliders"></i> System Settings
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="../index.php" class="btn btn-outline-success w-100">
                            <i class="bi bi-arrow-left"></i> Back to Main
                        </a>
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
