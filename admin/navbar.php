<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="index.php">
            <i class="bi bi-shield-lock"></i> FDSS Admin
        </a>

        <div class="d-flex align-items-center gap-2 ms-auto">
            <span class="badge text-bg-secondary d-none d-md-inline">
                <?php echo e($_SESSION['role'] ?? 'ADMIN'); ?>
            </span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <?php echo e($_SESSION['username'] ?? 'Admin'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people me-2"></i>Users</a></li>
                    <li><a class="dropdown-item" href="systems.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
