<?php
// includes/functions.php

/**
 * Create a new notification for a user
 */
function createNotification($userId, $title, $message, $contextType = null, $contextId = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, context_type, context_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $contextType, $contextId]);
        return true;
    } catch (PDOException $e) {
        // Log error or silently fail to not disrupt main flow
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications for a user
 */
function getUnreadNotifications($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get all notifications for a user (with pagination limit)
 */
function getUserNotifications($userId, $limit = 50) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $s = $limit; // bindParam expects variable
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->bindParam(2, $s, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId, $userId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead($userId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
}

/**
 * Get notification count
 */
function getUnreadNotificationCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}
?>
