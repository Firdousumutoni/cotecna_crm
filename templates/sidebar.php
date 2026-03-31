<!-- templates/sidebar.php -->
<div class="sidebar">
    <div class="sidebar-header">
        <div style="background:var(--brand-blue); width:32px; height:32px; border-radius:6px; display:flex; align-items:center; justify-content:center; color:white; font-weight:700;">C</div>
        <div class="logo-text" style="font-size:1.2rem; font-weight:700; color:white;">
            Cotecna<span style="color:var(--brand-blue);">CRM</span>
        </div>
    </div>

    <nav class="nav-menu">
        <div style="padding: 0 24px; margin-bottom: 10px; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Main Menu</div>
        
        <a href="index.php?page=dashboard" class="nav-item <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        
        <a href="index.php?page=clients" class="nav-item <?php echo ($page == 'clients') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Clients
        </a>
        
        <a href="index.php?page=interactions" class="nav-item <?php echo ($page == 'interactions') ? 'active' : ''; ?>">
            <i class="fa-regular fa-comments"></i> Interactions
        </a>
        
        <a href="index.php?page=deals" class="nav-item <?php echo ($page == 'deals') ? 'active' : ''; ?>">
            <i class="fa-solid fa-briefcase"></i> Deals
        </a>
        
        <a href="index.php?page=invoices" class="nav-item <?php echo ($page == 'invoices') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i> Invoices
        </a>

        <div style="padding: 0 24px; margin: 20px 0 10px 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Management</div>

        <a href="index.php?page=reports" class="nav-item <?php echo ($page == 'reports') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie"></i> Reports
        </a>
        
        <a href="index.php?page=inspections" class="nav-item <?php echo ($page == 'inspections') ? 'active' : ''; ?>">
            <i class="fa-solid fa-clipboard-check"></i> Inspections
        </a>
        
        <a href="index.php?page=settings" class="nav-item <?php echo ($page == 'settings') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gear"></i> Settings
        </a>
        
        <a href="index.php?page=certificates" class="nav-item <?php echo ($page == 'certificates') ? 'active' : ''; ?>">
            <i class="fa-solid fa-certificate"></i> Certificates
        </a>
    </nav>

    <div style="padding:20px; border-top:1px solid rgba(255,255,255,0.1);">
        <a href="index.php?page=logout" style="color:#ef4444; text-decoration:none; display:flex; align-items:center; gap:10px; font-size:0.9rem;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
        </a>
    </div>
</div>
