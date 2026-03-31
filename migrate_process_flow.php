<?php
// migrate_process_flow.php
require_once 'config/database.php';

try {
    echo "Migrating Database for Process Flow...<br>";

    // 1. Add deal_id and order_number to inspections
    try {
        $pdo->exec("ALTER TABLE inspections ADD COLUMN deal_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE inspections ADD CONSTRAINT fk_inspection_deal FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE SET NULL");
        echo "Added 'deal_id' to inspections.<br>";
    } catch (PDOException $e) { /* Ignore if exists */ }

    try {
        $pdo->exec("ALTER TABLE inspections ADD COLUMN order_number VARCHAR(50) DEFAULT NULL");
        echo "Added 'order_number' to inspections.<br>";
    } catch (PDOException $e) { /* Ignore if exists */ }

    // 2. Add inspection_id to invoices
    try {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN inspection_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE invoices ADD CONSTRAINT fk_invoice_inspection FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE SET NULL");
        echo "Added 'inspection_id' to invoices.<br>";
    } catch (PDOException $e) { /* Ignore if exists */ }

    // 3. Add 'sent_to_client' status/flag to invoices if needed? 
    // Existing status is ENUM('Paid', 'Pending', 'Overdue'). 'Sent' could be implied by 'Pending', or we add a specific flag.
    // Let's add a `is_sent` boolean.
    try {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN is_sent TINYINT(1) DEFAULT 0");
        echo "Added 'is_sent' flag to invoices.<br>";
    } catch (PDOException $e) { /* Ignore if exists */ }

    echo "Migration Complete.<br>";

} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage();
}
?>
