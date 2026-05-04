<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

$error   = '';
$success = '';

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$ALLOWED_ROLES = ['admin', 'manager', 'voc', 'hr', 'finance', 'it', 'sales&marketing', 'staff'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- Empty & Format Checks ---
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = trim($_POST['role'] ?? '');

        if ($email === '') {
            throw new InvalidArgumentException("Email address is required.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Please enter a valid email address (e.g. user@example.com).");
        }
        if ($password === '') {
            throw new InvalidArgumentException("Password is required.");
        }
        if ($role === '' || !in_array($role, $ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException("Please select a valid Access Role.");
        }

        // --- Authenticate ---
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: index.php");
            exit;
        }

        // --- Granular error feedback ---
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch();

        if (!$exists) {
            throw new InvalidArgumentException("No account found with that email address.");
        } elseif ($exists['role'] !== $role) {
            throw new InvalidArgumentException("Incorrect role selected for this account. Please check your Access Role.");
        } else {
            throw new InvalidArgumentException("Incorrect password. Please try again.");
        }

    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "A system error occurred. Please try again later.";
        error_log("Login Error: " . $e->getMessage());
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
                    <div class="alert-error"
                        style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); color: #059669;">
                        <i class="fa-solid fa-circle-check"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="form-input-group">
                            <i class="fa-regular fa-envelope form-icon"></i>
                            <input type="email" name="email" class="form-input" placeholder="name@cotecna.com.ke"
                                required>
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
                                <option value="admin">Admin</option>
                                <option value="voc">VOC Officer</option>
                                <option value="manager">Manager</option>
                                <option value="it">IT Support</option>
                                <option value="hr">HR Manager</option>
                                <option value="finance">Finance</option>
                                <option value="sales&marketing">Sales & Marketing</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <label class="checkbox-wrap">
                            <input type="checkbox" name="remember" style="accent-color: #2563eb;">
                            Remember me
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-submit">Sign In <i class="fa-solid fa-arrow-right"
                            style="margin-left:8px;"></i></button>

                </form>

                <div class="form-footer">
                    Don't have an account? <a href="register.php">Sign Up</a>
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