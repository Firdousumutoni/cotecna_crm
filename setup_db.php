<?php
// setup_db.php
require_once 'config/database.php';

try {
    echo "Setting up Database...<br>";

    // Disable foreign key checks to allow dropping tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    echo "Dropping all existing tables...<br>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "Dropped table: $table<br>";
        } catch (PDOException $e) {
            echo "Failed to drop $table: " . $e->getMessage() . "<br>";
        }
    }

    // 1. Users Table
    // Drop table to ensure enum is updated for this setup (Development mode) 
    
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            job_title VARCHAR(100) DEFAULT 'Staff',
            phone VARCHAR(20) DEFAULT '',
            avatar_url VARCHAR(255) DEFAULT '',
            role ENUM('admin', 'voc', 'manager', 'it', 'hr', 'finance', 'staff') DEFAULT 'staff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Users table created (with extended profile fields).<br>";
    } catch (PDOException $e) {
        echo "CREATE USERS FAILED: " . $e->getMessage() . "<br>";
    }

    // Create Default Users
    $password = password_hash('password', PASSWORD_DEFAULT);
    
    $users = [
        ['Admin User', 'admin@cotecna.com', 'admin', 'System Administrator', '+254 700 000001'],
        ['VOC Officer', 'voc@cotecna.com', 'voc', 'Verification Officer', '+254 700 000002'],
        ['Manager', 'manager@cotecna.com', 'manager', 'Operations Manager', '+254 700 000003'],
        ['IT Support', 'it@cotecna.com', 'it', 'IT Specialist', '+254 700 000004'],
        ['HR Manager', 'hr@cotecna.com', 'hr', 'Human Resources', '+254 700 000005'],
        ['Finance', 'finance@cotecna.com', 'finance', 'Finance Officer', '+254 700 000006']
    ];

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, job_title, phone) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($users as $user) {
        $stmt->execute([$user[0], $user[1], $password, $user[2], $user[3], $user[4]]);
        echo "Created user: {$user[1]} ({$user[2]})<br>";
    }

    // 1.5 Company Settings Table (NEW)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS company_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        echo "Company Settings table created.<br>";

        // Seed Default Settings
        $defaults = [
            'company_name' => 'Cotecna Inspection SA',
            'company_address' => 'Mombasa, Kenya',
            'support_email' => 'support@cotecna.com',
            'currency' => 'KES',
            'tax_name' => 'VAT',
            'tax_rate' => '16',
            'tax_pin' => 'P051234567Y',
            'logo_url' => 'assets/logo.png'
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO company_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }
        echo "Default Company Settings seeded.<br>";

    } catch (PDOException $e) {
        echo "CREATE COMPANY SETTINGS FAILED: " . $e->getMessage() . "<br>";
    }

    // 2. Clients Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        company VARCHAR(150) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        sector ENUM('Agriculture', 'Logistics', 'Consumer Products', 'Metals & Minerals', 'Government', 'Food Safety', 'Pharmaceuticals', 'Other') DEFAULT 'Other',
        status ENUM('Active', 'Pending', 'Inactive') DEFAULT 'Pending',
        revenue DECIMAL(15, 2) DEFAULT 0.00,
        last_contact DATE DEFAULT CURRENT_DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Clients table created.<br>";

    // Seed Clients
    $clients = [
        ['David Kimani', 'Highland Tea Packers', 'david.k@highlandtea.co.ke', '+254 722 123 456', 'Agriculture', 'Active', 210000, '2025-01-05'],
        ['Grace Wanjiku', 'Savannah Textiles', 'grace.w@savannahtex.com', '+254 733 987 654', 'Consumer Products', 'Pending', 75000, '2024-12-10'],
        ['Raj Patel', 'Construction Supplies Ltd', 'raj@cslkenya.com', '+254 721 555 111', 'Metals & Minerals', 'Active', 450000, '2025-01-06'],
        ['Esther Mutua', 'City Importers', 'esther@cityimp.com', '+254 710 222 333', 'Logistics', 'Inactive', 0, '2024-10-10'],
        ['Dr. Hassan Ali', 'MedPharm Kenya', 'hassan@medpharm.co.ke', '+254 755 123 789', 'Pharmaceuticals', 'Active', 650000, '2025-01-12'],
        ['Fresh Foods Ltd', 'Fresh Foods Exports', 'ops@freshfoods.com', '+254 788 444 555', 'Food Safety', 'Pending', 120000, '2025-01-14'],
        ['John Doe', 'Logistics Kenya Ltd', 'jdoe@lkl.co.ke', '+254 720 000 000', 'Logistics', 'Active', 150000, '2024-12-20'],
        ['Jane Smith', 'Nairobi Exports', 'jsmith@nairobiexports.com', '+254 730 111 222', 'Consumer Products', 'Pending', 50000, '2024-12-22'],
        ['Michael Kamau', 'Mombasa Port Services', 'mkamau@mps.co.ke', '+254 700 999 888', 'Government', 'Active', 320000, '2024-12-23'],
        ['Sarah Ochieng', 'Rift Valley Grains', 'sarah@rvgrains.com', '+254 711 333 444', 'Agriculture', 'Inactive', 0, '2024-11-15'],
        ['Peter Njoroge', 'Titanium Base Ltd', 'peter@titanium.com', '+254 722 777 666', 'Metals & Minerals', 'Active', 180000, '2024-12-24'],
        ['Amina Abdi', 'Coastal Fisheries', 'amina@coastalfish.com', '+254 799 555 666', 'Food Safety', 'Active', 95000, '2025-01-08']
    ];

    $stmt = $pdo->prepare("INSERT INTO clients (name, company, email, phone, sector, status, revenue, last_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($clients as $client) {
        // Handle "Pharmaceuticals" not in Enum by mapping to Other or updating Enum. 
        // For now, let's map Dr. Hassan to 'Other' or update Enum? 
        // Screenshot showed 'Pharmaceuticals' so let's update Enum in the Create Table above.
        // Actually, let's just make Sector VARCHAR to be flexible for now or update ENUM details above.
        // I'll update the ENUM above to include 'Pharmaceuticals' to be safe.
        
        $stmt->execute($client);
    }
    echo "Seeded " . count($clients) . " clients.<br>";

    // 3. Interactions Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        type ENUM('Call', 'Email', 'Meeting', 'Note') NOT NULL,
        subject VARCHAR(255) NOT NULL,
        notes TEXT,
        outcome ENUM('Satisfied', 'Neutral', 'Dissatisfied', 'Pending') DEFAULT 'Pending',
        interaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )");
    echo "Interactions table created.<br>";

    // Seed Interactions
    // We need to adhere to the client IDs created above. Assuming auto increment starts at 1, we can map to the 12 clients.
    // 1: David Kimani (Agri), 2: Grace (Consumer), 3: Raj (Metals), 4: Esther (Logistics), 5: Dr Hassan (Pharma)
    // 6: Fresh Foods, 7: John Doe (Logistics), 8: Jane Smith (Consumer), 9: Michael (Gov), 10: Sarah (Agri)
    // 11: Peter (Metals), 12: Amina (Food)

    $interactions = [
        [1, 'Call', 'Contract Renewal Discussion', 'Called to discuss renewal of annual contract.', 'Satisfied', '2025-01-10 10:30:00'],
        [11, 'Call', 'Lab Testing Results Delay', 'Follow up on lab testing results delay. Apologized, expedited processing.', 'Neutral', '2025-01-05 11:00:00'],
        [9, 'Meeting', 'Quarterly Review', 'Quarterly review meeting at client HQ. Client satisfied with current turnaround times.', 'Satisfied', '2025-01-09 14:00:00'],
        [2, 'Email', 'Invoice Discrepancy', 'Client emailed about mismatch in Invoice #4402. Investigating.', 'Pending', '2025-01-08 09:15:00'],
        [5, 'Meeting', 'New Product Certification', 'Presentation on new pharma certification standards. Client interested.', 'Satisfied', '2025-01-07 15:30:00'],
        [7, 'Note', 'Internal Handover', 'Handover of account management to new VOC officer.', 'Neutral', '2025-01-06 16:45:00'],
        [12, 'Call', 'Complaint on Inspector', 'Complaint regarding inspector behavior at Mombasa port. Needs HR follow up.', 'Dissatisfied', '2025-01-04 13:20:00'],
        [3, 'Email', 'Quote Request', 'Requested quote for 500 tons of steel inspection.', 'Pending', '2025-01-11 08:00:00']
    ];

    $stmt = $pdo->prepare("INSERT INTO interactions (client_id, type, subject, notes, outcome, interaction_date) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($interactions as $log) {
        $stmt->execute($log);
    }
    echo "Seeded " . count($interactions) . " interactions.<br>";

    // 4. Deals Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS deals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        deal_name VARCHAR(255) NOT NULL,
        description TEXT,
        amount DECIMAL(15, 2) NOT NULL,
        stage ENUM('Lead', 'Negotiation', 'Proposal', 'Closed Won', 'Closed Lost') NOT NULL,
        probability INT DEFAULT 0,
        expected_close_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )");
    echo "Deals table created.<br>";

    // Seed Deals (Expanded)
    // Seed Deals (Specific Targets: Rev=100.9M, Pipe=9.5M, Stalled=12)
    $deals = [
        // REVENUE (Closed Won) -> Sum: 100.9M
        [9, 'Govt Infrastructure Audit Phase 1', 'Structural integrity audit.', 50000000.00, 'Closed Won', 100, '2025-02-05', '2025-01-10'],
        [12, 'Port Operations Audit', 'Quarterly audit.', 30000000.00, 'Closed Won', 100, '2025-04-15', '2025-03-01'],
        [11, 'Titanium Export Batch', 'Pre-shipment inspection.', 20000000.00, 'Closed Won', 100, '2025-06-12', '2025-05-20'],
        [1, 'Specialty Tea Lab Analysis', 'Advanced chemical analysis.', 900000.00, 'Closed Won', 100, '2025-08-10', '2025-07-15'],

        // PIPELINE (Open & Stalled) -> Sum: 9.5M (12 Deals)
        // Stalled means created > 14 days ago. Current simulated date is Dec 2025.
        // We set created_at to '2025-10-01' so they are definitely stalled.
        [4, 'Fleet Inspection A', 'Vehicle inspection fleet A.', 1000000.00, 'Proposal', 60, '2026-01-15', '2025-10-01'],
        [4, 'Fleet Inspection B', 'Vehicle inspection fleet B.', 1000000.00, 'Proposal', 60, '2026-01-15', '2025-10-01'],
        [2, 'Warehouse Safety A', 'Safety audit A.', 1000000.00, 'Negotiation', 50, '2026-02-01', '2025-10-01'],
        [2, 'Warehouse Safety B', 'Safety audit B.', 1000000.00, 'Negotiation', 50, '2026-02-01', '2025-10-01'],
        [6, 'Market Cert A', 'Export certification A.', 1000000.00, 'Lead', 30, '2026-02-10', '2025-10-01'],
        [6, 'Market Cert B', 'Export certification B.', 1000000.00, 'Lead', 30, '2026-02-10', '2025-10-01'],
        [7, 'Grain Audit A', 'Bulk grain audit A.', 1000000.00, 'Proposal', 40, '2026-03-05', '2025-10-01'],
        
        [7, 'Grain Audit B', 'Bulk grain audit B.', 500000.00, 'Negotiation', 40, '2026-03-05', '2025-10-01'],
        [10, 'Agri Onboarding A', 'Client onboarding.', 500000.00, 'Lead', 20, '2026-01-20', '2025-10-01'],
        [10, 'Agri Onboarding B', 'Client onboarding.', 500000.00, 'Lead', 20, '2026-01-20', '2025-10-01'],
        [3, 'Steel Check A', 'Quality check.', 500000.00, 'Proposal', 50, '2026-02-15', '2025-10-01'],
        [3, 'Steel Check B', 'Quality check.', 500000.00, 'Proposal', 50, '2026-02-15', '2025-10-01']
    ];

    $stmt = $pdo->prepare("INSERT INTO deals (client_id, deal_name, description, amount, stage, probability, expected_close_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($deals as $deal) {
        $stmt->execute($deal);
    }
    echo "Seeded " . count($deals) . " deals.<br>";

    // 5. Invoices Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number VARCHAR(50) NOT NULL UNIQUE,
        client_id INT NOT NULL,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        issue_date DATE NOT NULL,
        due_date DATE NOT NULL,
        status ENUM('Paid', 'Pending', 'Overdue') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
    )");
    echo "Invoices table created.<br>";

    // Seed Invoices
    $invoices = [
        ['INV-001', 9, 'Invoice for Draft Survey Retainer', 2500000.00, '2024-12-21', '2025-01-21', 'Paid'], // Mombasa Port (ID 9 based on seed order)
        ['INV-002', 11, 'Lab Testing Services', 45000.00, '2024-12-16', '2025-01-16', 'Pending'], // Titanium Base (ID 11)
        ['INV-003', 1, 'Q4 2024 Certification Fee', 550000.00, '2024-12-01', '2024-12-31', 'Overdue'],
        ['INV-004', 3, 'Steel Inspection Batch #22', 120000.00, '2025-01-05', '2025-02-05', 'Pending'],
        ['INV-005', 5, 'Pharma Compliance Audit', 850000.00, '2024-12-28', '2025-01-28', 'Paid'],
        ['INV-006', 7, 'Logistics Safety Review', 320000.00, '2025-01-02', '2025-02-02', 'Pending'],
        ['INV-007', 12, 'Food Safety Lab Analysis', 65000.00, '2024-11-20', '2024-12-20', 'Overdue'],
        ['INV-008', 2, 'Warehouse Inspection', 450000.00, '2025-01-08', '2025-02-08', 'Pending']
    ];

    $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, client_id, description, amount, issue_date, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($invoices as $inv) {
        $stmt->execute($inv);
    }
    echo "Seeded " . count($invoices) . " invoices.<br>";

    // 6. Inspections Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS inspections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        product_name VARCHAR(150) NOT NULL,
        category ENUM('agriculture', 'minerals', 'consumer_goods', 'food', 'logistics') NOT NULL,
        inspection_date DATE NOT NULL,
        status ENUM('scheduled', 'in_progress', 'completed', 'certified', 'rejected', 'delayed') DEFAULT 'scheduled',
        inspector_name VARCHAR(100),
        location VARCHAR(100) DEFAULT 'Mombasa Port',
        notes TEXT,
        report_url VARCHAR(255),
        revenue DECIMAL(10, 2) DEFAULT 0.00,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Inspections table created.<br>";

    // Seed Inspections
    $inspections = [
        [9, 'Govt Housing Block A', 'agriculture', '2025-01-15', 'in_progress', 'John Doe', 'Nairobi West', 'Structural analysis pending phase 2.'],
        [12, 'Mombasa Port Terminal 4', 'logistics', '2025-02-10', 'completed', 'Jane Smith', 'Mombasa Port', 'Terminal clearance verified.'],
        [11, 'Scrap Metal Batch #404', 'minerals', '2025-03-05', 'scheduled', 'Mike Ross', 'Athi River', 'Awaiting customs approval.'],
        [1, 'Tea Shipment #22-B', 'agriculture', '2025-01-20', 'certified', 'Sarah Connors', 'Limuru', 'Quality grade A1 confirmed.'],
        [3, 'Steel Beam Quality Check', 'minerals', '2025-02-28', 'rejected', 'Terminator T800', 'Industrial Area', 'Structural flaws detected in batch 3.'],
        [6, 'Avocado Container #88', 'food', '2025-03-12', 'delayed', 'Ellen Ripley', 'Eldoret', 'Transport logistics delay.'],
    ];

    $stmt = $pdo->prepare("INSERT INTO inspections (client_id, product_name, category, inspection_date, status, inspector_name, location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($inspections as $ins) {
        $stmt->execute($ins);
    }
    echo "Seeded " . count($inspections) . " inspections.<br>";

    echo "Database Setup Complete! <a href='public/index.php'>Go to Home</a>";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

} catch (PDOException $e) {
    echo "Setup Failed: " . $e->getMessage();
}
?>
