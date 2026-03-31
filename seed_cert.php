<?php
require_once 'config/database.php';

try {
    echo "Checking Certificate System...<br>";
    
    // 1. Check Table
    $pdo->query("SELECT 1 FROM certificates LIMIT 1");
    echo "Table 'certificates' exists.<br>";

    // 2. Find Certified Inspection (ID 4 is usually the one from setup_db)
    $stmt = $pdo->query("SELECT id, status FROM inspections WHERE status = 'certified' LIMIT 1");
    $insp = $stmt->fetch();

    if ($insp) {
        echo "Found certified inspection ID: " . $insp['id'] . "<br>";
        
        // 3. Check if certificate exists
        $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE inspection_id = ?");
        $stmtChk->execute([$insp['id']]);
        
        if ($stmtChk->fetchColumn() == 0) {
            echo "No certificate found. Creating one...<br>";
            $year = date('Y');
            $cert_num = "CER-$year-0001";
            
            $stmtIns = $pdo->prepare("INSERT INTO certificates (inspection_id, certificate_number, issue_date, expiry_date, status) VALUES (?, ?, ?, ?, 'Active')");
            $stmtIns->execute([
                $insp['id'],
                $cert_num,
                date('Y-m-d'),
                date('Y-m-d', strtotime('+1 year'))
            ]);
            echo "Certificate created: $cert_num<br>";
        } else {
            echo "Certificate already exists.<br>";
        }
    } else {
        echo "No certified inspections found to seed.<br>";
        // Force certify one for demo
        $pdo->exec("UPDATE inspections SET status='certified' WHERE id=4");
        echo "Force certified inspection 4. Re-run to seed certificate.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
