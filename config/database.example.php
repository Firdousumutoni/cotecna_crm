<?php
// config/database.example.php
// Copy this file to config/database.php and update with your credentials

$host = '127.0.0.1';
$db   = 'cotecna_crm';
$user = 'root';
$pass = ''; // Enter your DB password here
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    $pdo->exec("USE `$db`");
    
} catch (\PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage() . "<br>Please make sure your MySQL server (XAMPP) is running.");
}
?>
