<?php
// templates/notifications.php

// Handle Mark Read & Redirect (Context Click)
if (isset($_GET['mark_read']) && isset($_GET['redirect'])) {
    markNotificationAsRead($_GET['mark_read'], $_SESSION['user_id']);
    header("Location: " . urldecode($_GET['redirect']));
    exit;
}

// Mark single as read (existing functionality, just for button)
if (isset($_GET['mark_read']) && !isset($_GET['redirect'])) {
    markNotificationAsRead($_GET['mark_read'], $_SESSION['user_id']);
    header("Location: index.php?page=notifications");
    exit;
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
    markAllNotificationsAsRead($_SESSION['user_id']);
    header("Location: index.php?page=notifications");
    exit;
}

$notifications = getUserNotifications($_SESSION['user_id']);
?>

<div class="main-content">
    <div class="top-header">
        <div class="search-bar">
            <!-- Search not critical for notifications but keeping layout consistent -->
        </div>

        <div class="user-profile">
            <div class="notification-btn">
                <i class="fa-regular fa-bell" style="font-size:1.2rem;"></i>
                <!-- Badge logic handled in header/JS, but we can show it here too -->
                <?php $unreadCount = getUnreadNotificationCount($_SESSION['user_id']); ?>
                <?php if($unreadCount > 0): ?>
                    <div class="notification-badge" style="width:8px; height:8px; background:#ef4444; border-radius:50%; position:absolute; top:0; right:0;"></div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?php echo $_SESSION['user_name'] ?? 'User'; ?></div>
                <div class="profile-role"><?php echo ucfirst($_SESSION['user_role'] ?? 'Staff'); ?></div>
            </div>
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
            </div>
        </div>
    </div>

    <div class="content-scroll">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <div>
                <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Notifications</h1>
                <p style="color:var(--text-muted);">Stay updated with your latest alerts and tasks.</p>
            </div>
            <div>
                <?php if($unreadCount > 0): ?>
                <form method="POST">
                    <button type="submit" name="mark_all_read" class="btn-secondary" style="background:#f1f5f9; color:#64748b; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">
                        <i class="fa-solid fa-check-double"></i> Mark all as read
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="notification-list" style="max-width: 800px;">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <?php 
                        $isRead = $notif['is_read'];
                        $bgColor = $isRead ? 'white' : '#f0f9ff';
                        $borderColor = $isRead ? '#e2e8f0' : '#bae6fd';
                        
                        // Context Link Logic
                        $link = '#';
                        $btnText = 'View Context';
                        if ($notif['context_type'] && $notif['context_id']) {
                             // Target URL
                             $targetUrl = "index.php?page=" . urlencode($notif['context_type']) . "&view_id=" . urlencode($notif['context_id']);
                             
                             // If unread, route through the marker handler (self) with redirect
                             // Since we are IN templates/notifications.php included by index, we route to index.php?page=notifications
                             if (!$isRead) {
                                 $link = "index.php?page=notifications&mark_read=" . $notif['id'] . "&redirect=" . urlencode($targetUrl);
                             } else {
                                 // If already read, just go there
                                 $link = $targetUrl;
                             }
                        }
                    ?>
                    <div style="background:<?php echo $bgColor; ?>; border:1px solid <?php echo $borderColor; ?>; padding:20px; border-radius:12px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:start; box-shadow:0 2px 4px rgba(0,0,0,0.02); transition:transform 0.2s;">
                        <div style="display:flex; gap:15px;">
                            <div style="background:<?php echo $isRead ? '#f1f5f9' : '#e0f2fe'; ?>; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:<?php echo $isRead ? '#64748b' : '#0284c7'; ?>;">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <div>
                                <h3 style="font-size:1rem; font-weight:600; color:#1e293b; margin-bottom:5px;">
                                    <?php echo htmlspecialchars($notif['title']); ?>
                                    <?php if(!$isRead): ?>
                                        <span style="background:#ef4444; color:white; font-size:0.6rem; padding:2px 6px; border-radius:10px; margin-left:8px; vertical-align:middle;">NEW</span>
                                    <?php endif; ?>
                                </h3>
                                <p style="color:#475569; font-size:0.9rem; margin-bottom:10px; line-height:1.5;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <div style="font-size:0.8rem; color:#94a3b8;">
                                    <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:10px; align-items:flex-end;">
                            <?php if ($notif['context_type'] && $notif['context_id']): ?>
                                <a href="<?php echo $link; ?>" class="btn-primary" style="text-decoration:none; padding:8px 16px; font-size:0.85rem; display:inline-block; text-align:center;">
                                    View Context <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
                                </a>
                            <?php endif; ?>
                            <?php if(!$isRead): ?>
                                <a href="index.php?page=notifications&mark_read=<?php echo $notif['id']; ?>" style="color:#64748b; font-size:0.8rem; text-decoration:none; border-bottom:1px dashed #cbd5e1;">Mark as read</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#64748b;">
                    <i class="fa-regular fa-bell-slash" style="font-size:3rem; margin-bottom:20px; color:#cbd5e1;"></i>
                    <h3>No notifications yet</h3>
                    <p>You're all caught up!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
