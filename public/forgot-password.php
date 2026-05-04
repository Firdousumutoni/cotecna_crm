<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['new_password'] ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');

        if ($email === '') {
            throw new InvalidArgumentException("Email address is required.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Please enter a valid email address.");
        }
        validate_password($password);
        if ($password !== $confirm) {
            throw new InvalidArgumentException("Passwords do not match.");
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new InvalidArgumentException("No account found with that email address.");
        }

        // Reset password (in a real production app, this would send an email with a reset token instead of allowing direct reset)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);

        $_SESSION['success_msg'] = "Password reset successfully! You can now log in.";
        header("Location: login.php");
        exit;

    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "A system error occurred. Please try again later.";
        error_log("Password Reset Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Cotecna CRM</title>
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
                    Secure Account Recovery
                </p>
            </div>
        </div>

        <!-- Right Panel: Reset Form -->
        <div class="right-panel">
            <div class="login-wrapper">
                <div class="login-header">
                    <h2 class="login-title">Reset Password</h2>
                    <div class="login-subtitle">Enter your email and a new password to recover access</div>
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
                        <label class="form-label">New Password</label>
                        <div class="form-input-group">
                            <i class="fa-solid fa-lock form-icon"></i>
                            <input type="password" name="new_password" class="form-input" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="form-input-group">
                            <i class="fa-solid fa-lock form-icon"></i>
                            <input type="password" name="confirm_password" class="form-input" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Reset Password <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i></button>
                </form>

                <div class="form-footer">
                    Remembered your password? <a href="login.php">Sign In</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-error');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 3000);
            });
        });
    </script>
</body>
</html>
