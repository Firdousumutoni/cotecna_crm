<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Email is already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $role]);
                
                $_SESSION['success_msg'] = "Account created successfully! Please login.";
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Cotecna CRM</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                    Join the enterprise standard for inspection, testing, and certification management.
                </p>

                <ul class="feature-list">
                    <li class="feature-item">
                        <i class="fa-solid fa-user-plus feature-icon"></i>
                        Role-Based Access Control
                    </li>
                    <li class="feature-item">
                        <i class="fa-solid fa-shield-halved feature-icon"></i>
                        Secure Account Setup
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Panel: Register Form -->
        <div class="right-panel">
            <div class="login-wrapper" style="max-width:500px;">
                <div class="login-header">
                    <h2 class="login-title">Create Account</h2>
                    <div class="login-subtitle">Join the Cotecna team ecosystem</div>
                </div>

                <?php if ($error): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="form-input-group">
                            <i class="fa-regular fa-user form-icon"></i>
                            <input type="text" name="name" class="form-input" placeholder="John Doe" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="form-input-group">
                            <i class="fa-regular fa-envelope form-icon"></i>
                            <input type="email" name="email" class="form-input" placeholder="name@cotecna.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <div class="form-input-group">
                            <i class="fa-solid fa-id-badge form-icon"></i>
                            <select name="role" class="form-select" required>
                                <option value="" disabled selected>Select Your Role</option>
                                <option value="voc">VOC Officer</option>
                                <option value="manager">Manager</option>
                                <option value="it">IT Support</option>
                                <option value="hr">HR Manager</option>
                                <option value="finance">Finance</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label class="form-label">Password</label>
                            <div class="form-input-group">
                                <i class="fa-solid fa-lock form-icon"></i>
                                <input type="password" name="password" class="form-input" placeholder="••••••" required>
                            </div>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label class="form-label">Confirm</label>
                            <div class="form-input-group">
                                <i class="fa-solid fa-lock form-icon"></i>
                                <input type="password" name="confirm_password" class="form-input" placeholder="••••••" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Create Account <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i></button>

                </form>

                <div class="form-footer">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
