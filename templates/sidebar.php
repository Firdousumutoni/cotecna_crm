<!-- templates/sidebar.php -->
<div class="sidebar glass-panel">
    <div class="brand" style="padding: 10px 10px 30px 10px; text-align: center;">
        <h1 style="color:white; margin:0; font-size:1.5rem;">
            <i class="fa-solid fa-cube" style="color:#3b82f6;"></i> COTECNA
        </h1>
        <small style="color:rgba(255,255,255,0.5);">Kenya Limited</small>
    </div>

    <nav>
        <a href="index.php?page=dashboard" class="nav-link <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line" style="width:25px;"></i> Dashboard
        </a>
        <a href="index.php?page=clients" class="nav-link <?php echo ($page == 'clients') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users" style="width:25px;"></i> Clients & CRM
        </a>
        <a href="index.php?page=inspections" class="nav-link <?php echo ($page == 'inspections') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clipboard-check" style="width:25px;"></i> Inspections
        </a>
        <a href="index.php?page=reports" class="nav-link <?php echo ($page == 'reports') ? 'active' : ''; ?>">
            <i class="fa-regular fa-file-pdf" style="width:25px;"></i> Reports
        </a>
        <a href="index.php?page=settings" class="nav-link <?php echo ($page == 'settings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gear" style="width:25px;"></i> Settings
        </a>
    </nav>

    <div style="position:absolute; bottom:20px; width:calc(100% - 40px);">
        <a href="index.php?page=logout" class="nav-link" style="color:#ef4444;">
            <i class="fa-solid fa-right-from-bracket" style="width:25px;"></i> Sign Out
        </a>
    </div>
</div>
