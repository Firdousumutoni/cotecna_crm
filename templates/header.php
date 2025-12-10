<!-- templates/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotecna Kenya | CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <div class="top-bar glass-panel">
                <div class="page-title">
                    <h2 style="margin:0; font-weight:600;"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h2>
                </div>
                <div class="user-profile" style="display:flex; align-items:center; gap:10px;">
                    <span>Admin User</span>
                    <div style="width:35px; height:35px; background:#3b82f6; border-radius:50%; display:flex; align-items:center; justify-content:center;">A</div>
                </div>
            </div>
