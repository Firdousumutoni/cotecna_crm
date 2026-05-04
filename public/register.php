<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

$error = '';

$ALLOWED_ROLES = ['admin', 'manager', 'voc', 'hr', 'finance', 'it', 'sales&marketing', 'staff'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- Empty & Type Checks ---
        $name     = validate_text($_POST['name'] ?? '', 'Full Name', 100);
        $email    = validate_email($_POST['email'] ?? '', 'Email Address');
        $role     = validate_whitelist(trim($_POST['role'] ?? ''), $ALLOWED_ROLES, 'Role');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');

        // --- Name: letters, spaces, hyphens, apostrophes only ---
        if (!preg_match("/^[a-zA-Z\s'\-]{2,100}$/", $name)) {
            throw new InvalidArgumentException("Full Name must contain letters only (2–100 characters). No special characters or numbers.");
        }

        // --- Password strength ---
        validate_password($password);
        if ($password !== $confirm) {
            throw new InvalidArgumentException("Passwords do not match. Please re-enter.");
        }

        // --- Check if email already registered ---
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new InvalidArgumentException("This email address is already registered. Please use a different one.");
        }

        // --- All checks passed — create account ---
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $role]);

        $_SESSION['success_msg'] = "Account created successfully! Please login.";
        header("Location: login.php");
        exit;

    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "A database error occurred. Please try again.";
        error_log("Register DB Error: " . $e->getMessage());
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
                                <input type="password" name="confirm_password" class="form-input" placeholder="••••••"
                                    required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Create Account <i class="fa-solid fa-arrow-right"
                            style="margin-left:8px;"></i></button>

                </form>

                <div class="form-footer">
                    Already have an account? <a href="login.php">Sign In</a>
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