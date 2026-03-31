<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
             // Basic error handling
             $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
             $stmt->execute([$email]);
             $exists = $stmt->fetch();
             
             if ($exists) {
                 if ($exists['role'] !== $role) {
                     $error = "Incorrect Role selected.";
                 } else {
                     $error = "Invalid password.";
                 }
             } else {
                 $error = "No account found.";
             }
        }
    } catch (PDOException $e) {
        $error = "System error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cotecna CRM</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

    <div class="split-container">
        
        <!-- Left Panel: Branding -->
        <div class="left-panel">
            <div class="brand-wrapper">
                <div class="logo-large">
                    <div class="logo-box">C</div>
                    <div class="logo-text">Cotecna<span style="color:#005eb8;">CRM</span></div>
                </div>
                
                <p class="brand-desc">
                    The enterprise standard for inspection, testing, and certification management.
                </p>

                <ul class="feature-list">
                    <li class="feature-item">
                        <i class="fa-solid fa-circle-check feature-icon"></i>
                        Real-time Operational Briefings
                    </li>
                    <li class="feature-item">
                        <i class="fa-solid fa-circle-check feature-icon"></i>
                        Automated Inspection Workflows
                    </li>
                    <li class="feature-item">
                        <i class="fa-solid fa-circle-check feature-icon"></i>
                        Secure Client Data Vault
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Panel: Login Form -->
        <div class="right-panel">
            <div class="login-wrapper">
                <div class="login-header">
                    <h2 class="login-title">Welcome Back</h2>
                    <div class="login-subtitle">Sign in to access your command center</div>
                </div>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert-error" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); color: #059669;">
                        <i class="fa-solid fa-circle-check"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="form-input-group">
                            <i class="fa-regular fa-envelope form-icon"></i>
                            <input type="email" name="email" class="form-input" placeholder="name@cotecna.com.ke" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="form-input-group">
                            <i class="fa-solid fa-lock form-icon"></i>
                            <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Access Role</label>
                        <div class="form-input-group">
                            <i class="fa-solid fa-id-badge form-icon"></i>
                            <select name="role" class="form-select" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="admin">Administrator</option>
                                <option value="voc">VOC Officer</option>
                                <option value="manager">Manager</option>
                                <option value="it">IT Support</option>
                                <option value="hr">HR Manager</option>
                                <option value="finance">Finance</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <label class="checkbox-wrap">
                            <input type="checkbox" name="remember" style="accent-color: #2563eb;">
                            Remember me
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-submit">Sign In <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i></button>

                </form>

                <div class="form-footer">
                    Don't have an account? <a href="register.php">Sign Up</a>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
