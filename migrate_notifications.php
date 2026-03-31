<?php
// migrate_notifications.php
require_once 'config/database.php';

try {
    echo "Migrating Notifications Table...<br>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        context_type VARCHAR(50) DEFAULT NULL,
        context_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Notifications table created successfully.<br>";

    // Seeding sample notifications for testing
    echo "Seeding sample notifications...<br>";
    
    // Get the first user (usually admin)
    $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $user = $stmt->fetch();
    
    if ($user) {
        $userId = $user['id'];
        
        $notifications = [
            ['New Inspection Request', 'A new inspection request for Mombasa Port has been created.', 'inspections', 1],
            ['Invoice Overdue', 'Invoice #INV-003 is now overdue.', 'invoices', 3],
            ['Client Interaction', 'Call log added for Highland Tea Packers.', 'interactions', 1],
            ['Deal Closed', 'Deal "Govt Infrastructure Audit Phase 1" closed won!', 'deals', 1]
        ];

        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, context_type, context_id) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($notifications as $n) {
            $stmt->execute([$userId, $n[0], $n[1], $n[2], $n[3]]);
        }
        echo "Seeded " . count($notifications) . " notifications for User ID $userId.<br>";
    } else {
        echo "No users found to seed notifications.<br>";
    }

} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage();
}
?>
