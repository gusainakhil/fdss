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
        <a href="index.php" class="<?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>" title="Dashboard">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a href="stations.php" class="<?php echo ($active_page ?? '') === 'stations' ? 'active' : ''; ?>" title="Stations">
            <i class="bi bi-buildings"></i><span>Stations</span>
        </a>
        <a href="users.php" class="<?php echo ($active_page ?? '') === 'users' ? 'active' : ''; ?>" title="ORG Admins">
            <i class="bi bi-person-badge"></i><span>users</span>
        </a>
       
    </nav>

    <div class="admin-sidebar-footer">
        <a href="../index.php" title="Main Dashboard">
            <i class="bi bi-arrow-left"></i><span>Main Dashboard</span>
        </a>
        <a href="logout.php" class="text-danger" title="Logout">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </div>
</div>
