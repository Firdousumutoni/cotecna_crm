<?php
// setup_db.php
require_once 'config/database.php';

try {
    echo "Setting up Database...<br>";

    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'staff') DEFAULT 'staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Users table created.<br>";

    // Create Default Admin User (password: password)
    $password = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['admin@cotecna.com']);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin User', 'admin@cotecna.com', $password, 'admin']);
        echo "Default Admin user created (admin@cotecna.com / password).<br>";
    }

    // 2. Clients Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(150) NOT NULL,
        contact_person VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        type ENUM('exporter', 'importer') NOT NULL,
        country VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Clients table created.<br>";

    // 3. Inspections Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS inspections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        product_name VARCHAR(150) NOT NULL,
        category ENUM('agriculture', 'minerals', 'consumer_goods', 'food') NOT NULL,
        inspection_date DATE NOT NULL,
        status ENUM('scheduled', 'in_progress', 'completed', 'certified', 'rejected') DEFAULT 'scheduled',
        inspector_name VARCHAR(100),
        report_url VARCHAR(255),
        revenue DECIMAL(10, 2) DEFAULT 0.00,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Inspections table created.<br>";

    echo "Database Setup Complete! <a href='public/index.php'>Go to Home</a>";

} catch (PDOException $e) {
    echo "Setup Failed: " . $e->getMessage();
}
?>
