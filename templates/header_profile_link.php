<?php
// templates/header_profile_link.php

// Fetch fresh user data for the header (Avatar/Job Title)
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    global $pdo; // Ensure $pdo is available
    if (!$pdo) require_once __DIR__ . '/../config/database.php';
    
    $stmtH = $pdo->prepare("SELECT name, job_title, role, avatar_url FROM users WHERE id = ?");
    $stmtH->execute([$_SESSION['user_id']]);
    $currentUser = $stmtH->fetch();
}
?>

<a href="index.php?page=settings" style="display:flex; align-items:center; gap:12px; text-decoration:none; color:inherit; margin-left:10px; padding:5px; border-radius:8px; transition:background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
    <div class="profile-info" style="text-align:right;">
        <div class="profile-name" style="font-weight:600; font-size:0.9rem; color:var(--text-main);">
            <?php echo htmlspecialchars($currentUser['name'] ?? $_SESSION['user_name'] ?? 'User'); ?>
        </div>
        <div class="profile-role" style="font-size:0.75rem; color:var(--text-muted);">
            <?php echo htmlspecialchars(!empty($currentUser['job_title']) ? $currentUser['job_title'] : ucfirst($currentUser['role'] ?? 'Staff')); ?>
        </div>
    </div>
    <div class="profile-avatar" style="width:38px; height:38px; border-radius:50%; background:var(--brand-blue); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; overflow:hidden; border:2px solid #e2e8f0;">
        <?php if (!empty($currentUser['avatar_url'])): ?>
            <img src="<?php echo htmlspecialchars($currentUser['avatar_url']); ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
        <?php else: ?>
            <?php echo strtoupper(substr($currentUser['name'] ?? $_SESSION['user_name'] ?? 'U', 0, 1)); ?>
        <?php endif; ?>
    </div>
</a>
