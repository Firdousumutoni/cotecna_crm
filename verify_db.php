<?php
require_once 'config/database.php';

try {
    echo "<h1>Database Verification</h1>";
    
    // Check Users Table Structure
    echo "<h2>Users Table Columns:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']} <br>";
    }

    // Check User Count
    echo "<h2>User Count:</h2>";
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $row) {
        echo "{$row['role']}: {$row['count']} users<br>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
