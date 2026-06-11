<?php $base_path = $base_path ?? ''; ?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="rail-logo">
            <i class="bi bi-train-front-fill"></i>
        </div>
        <div class="sidebar-logo-text">
            <strong>Indian Railways</strong>
            <small>FDSS Panel</small>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li><a href="<?php echo $base_path; ?>index.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
        <li><a href="<?php echo $base_path; ?>manufacturers.php"><i class="bi bi-building"></i><span> Add OEM / Manufacturers</span></a></li>
                        <li><a href="<?php echo $base_path; ?>coaches.php"><i class="bi bi-box-seam"></i><span> Coaches</span></a></li>
                       

       
        <li><a href="<?php echo $base_path; ?>trains.php"><i class="bi bi-train-freight-front"></i><span> Trains</span></a></li>
         <!-- <li><a href="<?php echo $base_path; ?>add-parameter.php"><i class="bi bi-gear"></i><span>Add FDSS/FSDS Parameters</span></a></li> -->
        <li><a href="<?php echo $base_path; ?>inventory.php"><i class="bi bi-boxes"></i><span> Inventory</span></a></li>
        <li><a href="<?php echo $base_path; ?>fdss_fsds_inventory.php"><i class="bi bi-clipboard-plus"></i><span>FDSS/FSDS</span></a></li>


         <li><a href="<?php echo $base_path; ?>auditors.php"><i class="bi bi-person-badge"></i><span> Auditors</span></a></li>
        <li><a href="<?php echo $base_path; ?>inspection-schedule.php"><i class="bi bi-calendar-check"></i><span>Create Schedule</span></a></li>
        <li><a href="<?php echo $base_path; ?>schedule-list.php"><i class="bi bi-calendar-range"></i><span>View Schedule</span></a></li>
        <li><a href="<?php echo $base_path; ?>attenttion-report.php"><i class="bi bi-sliders"></i><span>Attention Reports</span></a></li>
        <li><a href="<?php echo $base_path; ?>full-reports.php"><i class="bi bi-sliders"></i><span>Status of FDSS / FSDS</span></a></li>
        <!-- Reports Dropdown -->
        <!-- <li class="sidebar-dropdown">
            <a href="#" class="dropdown-toggle">
                <i class="bi bi-file-earmark-text"></i><span>Reports</span>
                <i class="bi bi-chevron-down dropdown-icon"></i>
            </a>
            <ul class="dropdown-menu">
                <li><a href="auditor-inspection.php"><i class="bi bi-clipboard-check"></i><span>Inspection Reports</span></a></li>
                <li><a href="reports.php"><i class="bi bi-graph-up"></i><span>System Reports</span></a></li>
            </ul>
        </li> -->
        <li><a href="<?php echo $base_path; ?>Warranty-claim-process.php"><i class="bi bi-sliders"></i><span>Warranty Claims</span></a></li>

        <li><a href="<?php echo $base_path; ?>settings.php"><i class="bi bi-sliders"></i><span>Settings</span></a></li>
    </ul>
</aside>
