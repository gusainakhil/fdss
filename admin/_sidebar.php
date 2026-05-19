<div class="admin-sidebar">
    <div class="admin-brand">
        <div class="admin-brand-icon">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div>
            <strong>FDSS Admin</strong>
            <small>Control panel</small>
        </div>
    </div>

    <nav class="admin-nav">
        <a href="index.php" class="<?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a href="users.php" class="<?php echo ($active_page ?? '') === 'users' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i><span>User Management</span>
        </a>
        <a href="systems.php" class="<?php echo ($active_page ?? '') === 'systems' ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i><span>System Settings</span>
        </a>
        <a href="reports.php" class="<?php echo ($active_page ?? '') === 'reports' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i><span>Reports</span>
        </a>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="../index.php">
            <i class="bi bi-arrow-left"></i><span>Main Dashboard</span>
        </a>
        <a href="logout.php" class="text-danger">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </div>
</div>
