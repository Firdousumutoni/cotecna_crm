<?php
require_once 'config/database.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inspection_id INT NOT NULL,
            certificate_number VARCHAR(50) NOT NULL UNIQUE,
            issue_date DATE NOT NULL,
            expiry_date DATE,
            status ENUM('Active', 'Revoked', 'Expired') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Certificates table created successfully.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
