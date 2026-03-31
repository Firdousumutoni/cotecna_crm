<?php
// public/index.php
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$page = $_GET['page'] ?? 'dashboard';

// Simple Router
switch ($page) {
    case 'logout':
        session_destroy();
        header("Location: login.php");
        exit;
    case 'dashboard':
        $pageTitle = 'Dashboard';
        $content = '../templates/dashboard.php';
        break;
    case 'clients':
        $pageTitle = 'Clients & CRM';
        $content = '../templates/clients.php';
        break;
    case 'inspections':
        $pageTitle = 'Inspections';
        $content = '../templates/inspections.php';
        break;
    case 'interactions':
        $pageTitle = 'Interactions';
        $content = '../templates/interactions.php';
        break;
    case 'deals':
        $pageTitle = 'Deals Pipeline';
        $content = '../templates/deals.php';
        break;
    case 'invoices':
        $pageTitle = 'Invoicing';
        $content = '../templates/invoices.php';
        break;
    case 'receipt':
        // Direct render for receipt, no layout
        include '../templates/receipt.php';
        exit;
        break;
    case 'reports':
        $pageTitle = 'Reports';
        $content = '../templates/reports.php';
        break;
    case 'settings':
        $pageTitle = 'Settings';
        $content = '../templates/settings.php';
        break;
    case 'notifications':
        $pageTitle = 'Notifications';
        $content = '../templates/notifications.php';
        break;
    case 'certificates':
        $pageTitle = 'Certificates';
        $content = '../templates/certificates.php';
        break;
    default:
        $pageTitle = 'Dashboard';
        $content = '../templates/dashboard.php';
        break;
}

include '../templates/header.php';

if (file_exists($content)) {
    // Content is already included inside header.php (which acts as layout wrapper)
    // include $content; 
} else {
    echo "<div class='glass-panel' style='padding:20px;'><h2>Page not found</h2><p>The page <strong>$page</strong> is under construction.</p></div>";
}

include '../templates/footer.php';
?>
