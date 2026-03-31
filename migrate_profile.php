<?php
// migrate_profile.php
require_once 'config/database.php';

try {
    echo "Migrating Users Table for Profile Features...<br>";

    // Add cover_image column if not exists
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN cover_image VARCHAR(255) DEFAULT ''");
        echo "Added 'cover_image' column.<br>";
    } catch (PDOException $e) {
        // Column likely exists or other error (ignore if exists)
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
             echo "'cover_image' column already exists.<br>";
        } else {
             // Try standard SQL for checking/adding if needed, but ALTER IGNORE is simpler in dev
             echo "Note: " . $e->getMessage() . "<br>";
        }
    }

    echo "Migration Complete.<br>";

} catch (PDOException $e) {
    die("Migration Failed: " . $e->getMessage());
}
?>
