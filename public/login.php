<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // For simplicity in this beginner demo, checking hardcoded admin or DB
    // Ideally check DB. Let's check DB.
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid credentials. Try admin@cotecna.com / password";
        }
    } catch (PDOException $e) {
        $error = "Database error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cotecna CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="display:flex; align-items:center; justify-content:center; background-image: radial-gradient(at 50% 50%, hsla(253,16%,7%,1) 0, transparent 50%);">

    <div class="glass-panel" style="width:400px; padding:40px; text-align:center;">
        <div style="margin-bottom:30px;">
            <i class="fa-solid fa-cube text-primary" style="font-size:3rem; color:#3b82f6; margin-bottom:10px;"></i>
            <h2>Cotecna CRM</h2>
            <div style="opacity:0.6;">Please sign in to continue</div>
        </div>

        <?php if ($error): ?>
            <div style="background:rgba(239,68,68,0.2); color:#fca5a5; padding:10px; border-radius:8px; margin-bottom:20px; font-size:0.9rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:20px; text-align:left;">
                <label style="display:block; margin-bottom:8px; font-size:0.9rem;">Email Address</label>
                <input type="email" name="email" class="glass-input" placeholder="admin@cotecna.com" required>
            </div>
            
            <div style="margin-bottom:30px; text-align:left;">
                <label style="display:block; margin-bottom:8px; font-size:0.9rem;">Password</label>
                <input type="password" name="password" class="glass-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="glass-btn" style="width:100%;">Sign In</button>
        </form>

        <div style="margin-top:20px; font-size:0.9rem; opacity:0.6;">
            Demo: admin@cotecna.com / password
        </div>
    </div>

</body>
</html>
