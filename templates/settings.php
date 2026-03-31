<?php
// Handle POST Updates
$success_msg = '';
$error_msg = '';

// Fetch Current User Data FIRST
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$user_role = $user['role'] ?? 'staff'; // Default to staff if missing

// Update Profile (All Users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        // 1. Handle File Uploads
        $avatar_sql = ""; 
        $cover_sql = "";
        $params = [];
        
        // Define paths clearly
        // Absolute path for file system operations
        $baseUploadDir = __DIR__ . '/../public/uploads/';
        
        if (!function_exists('uploadProfileFile')) {
            function uploadProfileFile($file, $subDir, $baseDir) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    return false;
                }
                
                // Ensure directory exists
                $targetDir = $baseDir . $subDir;
                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0777, true)) {
                        throw new Exception("Failed to create directory: " . $targetDir);
                    }
                }
                
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    throw new Exception("Invalid file type: " . $ext);
                }
                
                $filename = uniqid() . '.' . $ext;
                $targetFile = $targetDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    // Return the web-accessible relative path
                    return 'uploads/' . $subDir . $filename; 
                }
                return false;
            }
        }

        // Check Avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $newAvatar = uploadProfileFile($_FILES['avatar'], 'avatars/', $baseUploadDir);
            if ($newAvatar) {
                $avatar_sql = ", avatar_url = ?";
                $params[] = $newAvatar;
            }
        } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
             // Handle actual errors (size, partial, etc)
             if ($_FILES['avatar']['error'] == UPLOAD_ERR_INI_SIZE) throw new Exception("Avatar file too large.");
        }
        
        // Check Cover
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $newCover = uploadProfileFile($_FILES['cover_image'], 'covers/', $baseUploadDir);
            if ($newCover) {
                $cover_sql = ", cover_image = ?";
                $params[] = $newCover;
            }
        }

        // 2. Build Base SQL
        // Check if email changed
        $new_email = $_POST['email'];
        if ($new_email !== $user['email']) {
             $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
             $stmt->execute([$new_email, $_SESSION['user_id']]);
             if ($stmt->fetch()) {
                 throw new Exception("Email already in use by another account.");
             }
        }

        $sql = "UPDATE users SET name = ?, job_title = ?, phone = ?, email = ?" . $avatar_sql . $cover_sql . " WHERE id = ?";
        
        // 3. Prepare Params
        $final_params = [$_POST['name'], $_POST['job_title'], $_POST['phone'], $new_email];
        if (!empty($params)) {
            $final_params = array_merge($final_params, $params);
        }
        $final_params[] = $_SESSION['user_id'];

        // 4. Execute
        $stmt = $pdo->prepare($sql);
        $stmt->execute($final_params);

        // 5. Update Session/Local
        $_SESSION['user_name'] = $_POST['name'];
        
        // Refresh User Data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        $success_msg = "Profile updated successfully!";
        
    } catch (Exception $e) { 
        $error_msg = "Error updating profile: " . $e->getMessage();
    }
}

// Update Password (All Users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if ($_POST['new_password'] === $_POST['confirm_password']) {
        $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $_SESSION['user_id']]);
        $success_msg = "Password changed successfully!";
    } else {
        $error_msg = "New passwords do not match.";
    }
}

// Update Company Settings (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company']) && $user_role === 'admin') {
    $settings_to_update = ['company_name', 'company_address', 'support_email', 'currency', 'tax_name', 'tax_rate', 'tax_pin'];
    foreach ($settings_to_update as $key) {
        if (isset($_POST[$key])) {
            $stmt = $pdo->prepare("INSERT INTO company_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $_POST[$key], $_POST[$key]]);
        }
    }
    $success_msg = "Company settings saved!";
}

// Update User Roles (Admin Only) - Simpler version for now
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user_role']) && $user_role === 'admin') {
     try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$_POST['role'], $_POST['user_id']]);
        $success_msg = "User role updated!";
    } catch (PDOException $e) {
        $error_msg = "Error updating role.";
    }
}

// Fetch Company Settings (Helper Function or Direct Query)
$settings_raw = $pdo->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
// Default fallback
$settings = array_merge([
    'company_name' => '', 'company_address' => '', 'support_email' => '', 
    'currency' => 'KES', 'tax_name' => 'VAT', 'tax_rate' => '16', 'tax_pin' => ''
], $settings_raw);

// Fetch All Users (For Admin)
$all_users = [];
if ($user_role === 'admin') {
    $all_users = $pdo->query("SELECT * FROM users ORDER BY name ASC")->fetchAll();
}
?>

<div class="main-content">
    <div class="top-header">
        <div style="flex:1;">
            <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Settings</h1>
            <p style="color:var(--text-muted);">Manage your profile and system preferences.</p>
        </div>
        <div class="user-profile">
            <?php include __DIR__ . '/notification_dropdown.php'; ?>
            <?php include __DIR__ . '/header_profile_link.php'; ?>
        </div>
    </div>

    <div class="content-scroll">
    
        <?php if ($success_msg): ?>
            <div id="successAlert" style="background:#dcfce7; color:#16a34a; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600; transition: opacity 1s ease-out;">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
            <script>
                setTimeout(function() {
                    const alert = document.getElementById('successAlert');
                    if (alert) {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.style.display = 'none', 1000);
                    }
                }, 2000);
            </script>
        <?php endif; ?>
        <?php if ($error_msg): ?>
             <div style="background:#fee2e2; color:#ef4444; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:600;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- TABS -->
        <div style="border-bottom:1px solid #e2e8f0; margin-bottom:30px; display:flex; gap:30px;">
            <button class="tab-btn active" onclick="switchTab(event, 'general')">General</button>
            <button class="tab-btn" onclick="switchTab(event, 'security')">Security</button>
            <?php if ($user_role === 'admin'): ?>
                <button class="tab-btn" onclick="switchTab(event, 'company')">Company (Admin)</button>
                <button class="tab-btn" onclick="switchTab(event, 'users')">Users (Admin)</button>
            <?php endif; ?>
        </div>

        <!-- GENERAL SETTINGS -->
        <div id="general" class="tab-content">
            <div class="settings-card">
                <h3 class="card-title">My Profile</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <!-- Cover Image & Avatar Section -->
                    <div style="position:relative; margin-bottom:60px;">
                        <!-- Cover Image -->
                        <div class="profile-cover" style="height:150px; background:<?php echo !empty($user['cover_image']) ? "url('".$user['cover_image']."')" : '#cbd5e1'; ?> center/cover no-repeat; border-radius:12px; position:relative; overflow:hidden;">
                            <label for="coverInput" style="position:absolute; bottom:10px; right:10px; background:rgba(0,0,0,0.6); color:white; padding:5px 10px; border-radius:6px; font-size:0.8rem; cursor:pointer;">
                                <i class="fa-solid fa-camera"></i> Change Cover
                            </label>
                            <input type="file" name="cover_image" id="coverInput" style="display:none;" accept="image/*" onchange="previewImage(this, '.profile-cover')">
                        </div>
                        
                        <!-- Avatar -->
                        <div style="position:absolute; bottom:-40px; left:20px; width:100px; height:100px; border-radius:50%; border:4px solid white; background:white;">
                             <?php if (!empty($user['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" id="avatarPreview" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                             <?php else: ?>
                                <div id="avatarPreview" style="width:100%; height:100%; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:2rem; color:#64748b; font-weight:700;">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                             <?php endif; ?>
                             
                             <label for="avatarInput" style="position:absolute; bottom:0; right:0; background:#3b82f6; color:white; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; border:2px solid white;">
                                <i class="fa-solid fa-pencil" style="font-size:0.8rem;"></i>
                             </label>
                             <input type="file" name="avatar" id="avatarInput" style="display:none;" accept="image/*" onchange="previewAvatar(this)">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div>
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Job Title</label>
                            <input type="text" name="job_title" value="<?php echo htmlspecialchars($user['job_title']); ?>" class="form-input">
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div>
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-input" required>
                        </div>
                         <div>
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="form-input">
                        </div>
                    </div>
                    
                    <div style="margin-bottom:20px;">
                        <label class="form-label">Time Zone</label>
                        <select class="form-input">
                            <option>(GMT+03:00) Nairobi</option>
                            <option>(GMT+00:00) UTC</option>
                        </select>
                    </div>

                    <div style="text-align:right;">
                        <button type="submit" class="btn-primary">Save Profile</button>
                    </div>
                </form>
                
                <script>
                function previewAvatar(input) {
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var preview = document.getElementById('avatarPreview');
                            if (preview.tagName === 'IMG') {
                                preview.src = e.target.result;
                            } else {
                                // If it was a div, replace with img or set background
                                preview.innerHTML = '';
                                preview.style.background = 'url(' + e.target.result + ') center/cover no-repeat';
                            }
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }
                function previewImage(input, selector) {
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            document.querySelector(selector).style.background = 'url(' + e.target.result + ') center/cover no-repeat';
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }
                </script>
            </div>
            
             <div class="settings-card" style="margin-top:30px;">
                <h3 class="card-title">Preferences</h3>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:15px 0; border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div style="font-weight:600; color:#1e293b;">Dark Mode</div>
                        <div style="font-size:0.85rem; color:#64748b;">Switch between light and dark themes.</div>
                    </div>
                    <div>
                        <label class="switch">
                          <input type="checkbox" id="darkModeToggle">
                          <span class="slider round"></span>
                        </label>
                    </div>
                </div>
                 <div style="display:flex; justify-content:space-between; align-items:center; padding:15px 0;">
                    <div>
                        <div style="font-weight:600; color:#1e293b;">Email Notifications</div>
                        <div style="font-size:0.85rem; color:#64748b;">Receive emails for task assignments.</div>
                    </div>
                    <div>
                         <label class="switch">
                          <input type="checkbox" checked>
                          <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Dark Mode Logic
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;

    // Load initial state
    if (localStorage.getItem('theme') === 'dark') {
        body.classList.add('dark-mode');
        if(darkModeToggle) darkModeToggle.checked = true;
    }

    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.classList.add('dark-mode');
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark-mode');
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });
    }

    // 2. Email Notifications Logic (Persistence Validation)
    const emailToggle = document.getElementById('emailToggle');
    
    // Load initial state (Default to true if not set)
    if (localStorage.getItem('email_notifs') === 'false') {
        if(emailToggle) emailToggle.checked = false;
    } else {
        if(emailToggle) emailToggle.checked = true;
    }

    if (emailToggle) {
        emailToggle.addEventListener('change', function() {
            localStorage.setItem('email_notifs', this.checked);
            // Simulate saving feedback
            console.log("Email preferences saved: " + this.checked);
        });
    }
    
    // 3. 2FA Toggle Logic (Persistence Validation)
    const twoFaToggle = document.getElementById('twoFaToggle');
    if (localStorage.getItem('2fa_enabled') === 'true') {
        if(twoFaToggle) twoFaToggle.checked = true;
    }

    if (twoFaToggle) {
        twoFaToggle.addEventListener('change', function() {
             localStorage.setItem('2fa_enabled', this.checked);
        });
    }
});
</script>

        <!-- SECURITY SETTINGS -->
        <div id="security" class="tab-content" style="display:none;">
            <div class="settings-card">
                <h3 class="card-title">Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="update_password" value="1">
                    <div style="margin-bottom:15px;">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div style="margin-bottom:15px;">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>
                    <div style="margin-bottom:20px;">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                     <div style="text-align:right;">
                        <button type="submit" class="btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
             <div class="settings-card" style="margin-top:30px;">
                <h3 class="card-title">Two-Factor Authentication</h3>
                 <div style="display:flex; justify-content:space-between; align-items:center; padding:15px 0;">
                    <div>
                        <div style="font-weight:600; color:#1e293b;">Enable 2FA</div>
                        <div style="font-size:0.85rem; color:#64748b;">Protect your account with an extra layer of security.</div>
                    </div>
                    <div>
                        <label class="switch">
                          <input type="checkbox">
                          <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($user_role === 'admin'): ?>
        <!-- COMPANY SETTINGS (ADMIN) -->
        <div id="company" class="tab-content" style="display:none;">
            <div class="settings-card">
                <h3 class="card-title">Company Profile</h3>
                <form method="POST">
                    <input type="hidden" name="update_company" value="1">
                    <div style="margin-bottom:15px;">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>" class="form-input">
                    </div>
                     <div style="margin-bottom:15px;">
                        <label class="form-label">Physical Address</label>
                        <textarea name="company_address" rows="2" class="form-input"><?php echo htmlspecialchars($settings['company_address']); ?></textarea>
                    </div>
                     <div style="margin-bottom:20px;">
                        <label class="form-label">Support Email</label>
                        <input type="email" name="support_email" value="<?php echo htmlspecialchars($settings['support_email']); ?>" class="form-input">
                    </div>
                    
                    <h3 class="card-title" style="margin-top:30px; border-top:1px solid #f1f5f9; padding-top:20px;">Financial & Tax</h3>
                     <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:15px;">
                        <div>
                            <label class="form-label">Base Currency</label>
                            <select name="currency" class="form-input">
                                <?php 
                                $currencies = [
                                    "USD" => "USD (US Dollar)",
                                    "EUR" => "EUR (Euro)",
                                    "KES" => "KES (Kenya Shilling)",
                                    "GBP" => "GBP (British Pound)",
                                    "JPY" => "JPY (Japanese Yen)",
                                    "AUD" => "AUD (Australian Dollar)",
                                    "CAD" => "CAD (Canadian Dollar)",
                                    "CHF" => "CHF (Swiss Franc)",
                                    "CNY" => "CNY (Chinese Yuan)",
                                    "INR" => "INR (Indian Rupee)",
                                    "ZAR" => "ZAR (South African Rand)",
                                    "TZS" => "TZS (Tanzanian Shilling)",
                                    "UGX" => "UGX (Ugandan Shilling)",
                                    "RWF" => "RWF (Rwandan Franc)",
                                    "NGN" => "NGN (Nigerian Naira)",
                                    "GHS" => "GHS (Ghanaian Cedi)",
                                    "EGP" => "EGP (Egyptian Pound)",
                                    "AED" => "AED (UAE Dirham)",
                                    "SAR" => "SAR (Saudi Riyal)",
                                    "SGD" => "SGD (Singapore Dollar)",
                                    "NZD" => "NZD (New Zealand Dollar)",
                                ];
                                foreach($currencies as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php if(($settings['currency'] ?? 'KES') == $code) echo 'selected'; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                             <label class="form-label">Tax ID / PIN</label>
                             <input type="text" name="tax_pin" value="<?php echo htmlspecialchars($settings['tax_pin']); ?>" class="form-input">
                        </div>
                    </div>
                     <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div>
                            <label class="form-label">Tax Name (e.g. VAT)</label>
                            <input type="text" name="tax_name" value="<?php echo htmlspecialchars($settings['tax_name']); ?>" class="form-input">
                        </div>
                         <div>
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" name="tax_rate" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" class="form-input">
                        </div>
                    </div>

                    <div style="text-align:right;">
                        <button type="submit" class="btn-primary">Save Company Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- USERS MANAGEMENT (ADMIN) -->
        <div id="users" class="tab-content" style="display:none;">
            <div class="settings-card">
                 <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <h3 class="card-title" style="margin:0;">User Management</h3>
                    <button class="btn-primary" style="padding:5px 15px; font-size:0.85rem;">+ Invite User</button>
                </div>

                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left; border-bottom:1px solid var(--border-color);">
                            <th style="padding:10px; font-size:0.85rem;">User</th>
                            <th style="padding:10px; font-size:0.85rem;">Email</th>
                            <th style="padding:10px; font-size:0.85rem;">Role</th>
                            <th style="padding:10px; font-size:0.85rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_users as $u): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:15px 10px;">
                                <div style="font-weight:600; color:#1e293b;"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div style="font-size:0.8rem; color:#64748b;"><?php echo htmlspecialchars($u['job_title']); ?></div>
                            </td>
                             <td style="padding:15px 10px; color:#475569; font-size:0.9rem;"><?php echo htmlspecialchars($u['email']); ?></td>
                             <td style="padding:15px 10px;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="update_user_role" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role" onchange="this.form.submit()" style="padding:5px; border-radius:4px; border:1px solid #cbd5e1; font-size:0.85rem;">
                                        <option value="admin" <?php echo $u['role']=='admin'?'selected':''; ?>>Admin</option>
                                        <option value="manager" <?php echo $u['role']=='manager'?'selected':''; ?>>Manager</option>
                                        <option value="voc" <?php echo $u['role']=='voc'?'selected':''; ?>>VOC</option>
                                        <option value="staff" <?php echo $u['role']=='staff'?'selected':''; ?>>Staff</option>
                                    </select>
                                </form>
                             </td>
                             <td style="padding:15px 10px;">
                                 <button style="color:#ef4444; background:none; border:none; cursor:pointer;"><i class="fa-regular fa-trash-can"></i></button>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
.settings-card { 
    background: var(--bg-card); 
    padding: 25px; 
    border-radius: 12px; 
    border: 1px solid var(--border-color); 
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
}
.card-title { 
    font-size: 1.1rem; 
    font-weight: 700; 
    color: var(--text-main); 
    margin-bottom: 20px; 
}
.tab-btn { 
    background: none; 
    border: none; 
    padding-bottom: 10px; 
    font-weight: 600; 
    color: var(--text-muted); 
    cursor: pointer; 
    font-size: 1rem; 
    border-bottom: 2px solid transparent; 
    transition: all 0.2s; 
}
.tab-btn.active { 
    color: var(--primary-color); 
    border-bottom-color: var(--primary-color); 
}
.tab-btn:hover { 
    color: var(--text-main); 
}

/* Switch Toggle */
.switch { 
    position: relative; 
    display: inline-block; 
    width: 50px; 
    height: 26px; 
    vertical-align: middle;
}
.switch input { 
    opacity: 0; 
    width: 0; 
    height: 0; 
}
.slider { 
    position: absolute; 
    cursor: pointer; 
    top: 0; 
    left: 0; 
    right: 0; 
    bottom: 0; 
    background-color: #cbd5e1; 
    transition: .4s; 
    z-index: 1;
}
.slider:before { 
    position: absolute; 
    content: ""; 
    height: 18px; 
    width: 18px; 
    left: 4px; 
    bottom: 4px; 
    background-color: white; 
    transition: .4s; 
    z-index: 2;
}
input:checked + .slider { 
    background-color: var(--primary-color, #1e40af); 
}
input:focus + .slider { 
    box-shadow: 0 0 1px var(--primary-color, #1e40af); 
}
input:checked + .slider:before { 
    transform: translateX(24px); 
}
.slider.round { 
    border-radius: 34px; 
}
.slider.round:before { 
    border-radius: 50%; 
}

/* Table Text Colors */
table th { color: var(--text-muted) !important; }
table td { color: var(--text-main) !important; }
table tr { border-bottom: 1px solid var(--border-color); }
</style>

<script>
function switchTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}
</script>
