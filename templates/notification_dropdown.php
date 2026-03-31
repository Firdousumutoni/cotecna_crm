<?php
// templates/notification_dropdown.php

// Ensure functions are available
if (!function_exists('getUserNotifications')) return;

// Fetch latest 5 notifications
$dropdownNotifs = getUserNotifications($_SESSION['user_id'], 5);
$unreadCount = getUnreadNotificationCount($_SESSION['user_id']);
?>

<div class="notification-wrapper" style="position:relative;">
    <!-- Toggle Button -->
    <a href="javascript:void(0)" onclick="toggleNotificationDropdown(event)" class="notification-btn" style="text-decoration:none; color:inherit; position:relative; display:block;">
        <i class="fa-regular fa-bell" style="font-size:1.2rem;"></i>
        <?php if($unreadCount > 0): ?>
            <div class="notification-badge" id="notifBadge" 
                 style="width:10px; height:10px; background:#ef4444; border-radius:50%; position:absolute; top:2px; right:2px; border:2px solid white;"></div>
        <?php endif; ?>
    </a>

    <!-- Dropdown Menu -->
    <div id="notificationDropdown" 
         style="display:none; position:absolute; top:130%; right:-10px; width:360px; background:white; 
                border-radius:12px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); 
                z-index:9999; border:1px solid #e2e8f0; overflow:hidden;">
        
        <!-- Header -->
        <div style="padding:15px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div style="font-weight:700; color:#334155; font-size:0.95rem;">Notifications</div>
            <?php if($unreadCount > 0): ?>
            <form method="POST" action="index.php?page=notifications" style="margin:0;">
                <button type="submit" name="mark_all_read" style="background:none; border:none; color:#3b82f6; font-size:0.8rem; font-weight:600; cursor:pointer;">
                    Mark all read
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- List -->
        <div style="max-height:350px; overflow-y:auto;">
            <?php if(count($dropdownNotifs) > 0): ?>
                <?php foreach($dropdownNotifs as $notif): 
                    $isRead = $notif['is_read'];
                    
                    // Link Logic (Same as notifications.php)
                    $targetUrl = '#';
                    if ($notif['context_type'] && $notif['context_id']) {
                        $targetUrl = "index.php?page=" . urlencode($notif['context_type']) . "&view_id=" . urlencode($notif['context_id']);
                    }
                    
                    // If Unread, route via handler to mark read
                    $finalLink = $targetUrl;
                    if (!$isRead && $targetUrl !== '#') {
                        $finalLink = "index.php?page=notifications&mark_read=" . $notif['id'] . "&redirect=" . urlencode($targetUrl);
                    }
                ?>
                <a href="<?php echo $finalLink; ?>" 
                   style="display:block; padding:15px; border-bottom:1px solid #f1f5f9; text-decoration:none; transition:background 0.2s; background:<?php echo $isRead ? 'white' : '#f0f9ff'; ?>;"
                   onmouseover="this.style.background='<?php echo $isRead ? '#f8fafc' : '#e0f2fe'; ?>'"
                   onmouseout="this.style.background='<?php echo $isRead ? 'white' : '#f0f9ff'; ?>'">
                    
                    <div style="display:flex; gap:12px;">
                        <div style="color:<?php echo $isRead ? '#94a3b8' : '#3b82f6'; ?>; margin-top:2px;">
                            <?php if(!$isRead): ?><i class="fa-solid fa-circle" style="font-size:0.5rem;"></i><?php else: ?>
                            <i class="fa-regular fa-bell" style="font-size:0.9rem;"></i><?php endif; ?>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:0.9rem; font-weight:600; color:#1e293b; line-height:1.4; margin-bottom:4px;">
                                <?php echo htmlspecialchars($notif['title']); ?>
                            </div>
                            <div style="font-size:0.85rem; color:#64748b; line-height:1.4; margin-bottom:6px;">
                                <?php echo htmlspecialchars(substr($notif['message'], 0, 80)) . (strlen($notif['message'])>80 ? '...' : ''); ?>
                            </div>
                            <div style="font-size:0.75rem; color:#94a3b8;">
                                <?php echo date('M d, H:i', strtotime($notif['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:40px 20px; text-align:center; color:#94a3b8;">
                    <i class="fa-regular fa-bell-slash" style="font-size:2rem; margin-bottom:10px;"></i>
                    <div style="font-size:0.9rem;">No notifications</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div style="padding:10px; text-align:center; background:white; border-top:1px solid #e2e8f0;">
            <a href="index.php?page=notifications" style="text-decoration:none; color:#475569; font-size:0.85rem; font-weight:600;">
                View all notifications
            </a>
        </div>
    </div>
</div>

<script>
function toggleNotificationDropdown(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    const isHidden = dropdown.style.display === 'none';
    
    // Close any other open dropdowns if they exist (optional)
    
    dropdown.style.display = isHidden ? 'block' : 'none';
}

// Close when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificationDropdown');
    // If the click is NOT inside the dropdown AND NOT on the button (wrapper)
    if (dropdown && dropdown.style.display === 'block') {
         if (!e.target.closest('.notification-wrapper')) {
             dropdown.style.display = 'none';
         }
    }
});
</script>
