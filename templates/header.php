<!-- templates/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotecna Kenya | CRM</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Apply Dark Mode immediately if saved
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
            document.body.classList.add('dark-mode');
        }
    </script>
</head>
<body>
    <div class="app-container" style="display:flex; height:100vh; overflow:hidden;">
        <?php include '../templates/sidebar.php'; ?>
        <?php 
           if (file_exists($content)) {
               include $content;
           } else {
               echo "<div class='main-content' style='padding:20px;'><h2>Page not found</h2></div>";
           }
        ?>
    </div>
