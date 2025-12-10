<!-- templates/clients.php -->
<?php
// Handle Form Submission (Add Client)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_client') {
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (company_name, contact_person, email, phone, type, country) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['company_name'],
            $_POST['contact_person'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['type'],
            $_POST['country']
        ]);
        $success_msg = "Client added successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// Fetch Clients
try {
    $stmt = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC");
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    $clients = [];
    $error_msg = "Database Error: " . $e->getMessage();
}
?>

<div class="glass-panel" style="padding:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Client Management</h2>
        <button onclick="document.getElementById('addClientModal').style.display='block'" class="glass-btn">
            <i class="fa-solid fa-plus"></i> Add New Client
        </button>
    </div>

    <?php if (isset($success_msg)): ?>
        <div style="background:rgba(16, 185, 129, 0.2); color:#34d399; padding:10px; border-radius:8px; margin-bottom:20px;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Company</th>
                <th>Contact Person</th>
                <th>Type</th>
                <th>Country</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clients)): ?>
                <tr><td colspan="5" style="text-align:center; color:rgba(255,255,255,0.3);">No clients found. Add one!</td></tr>
            <?php else: ?>
                <?php foreach ($clients as $client): ?>
                <tr>
                    <td>
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($client['company_name']); ?></div>
                        <div style="font-size:0.8rem; opacity:0.7;"><?php echo htmlspecialchars($client['email']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($client['contact_person']); ?></td>
                    <td>
                        <span class="badge <?php echo $client['type'] == 'exporter' ? 'badge-info' : 'badge-warning'; ?>">
                            <?php echo ucfirst($client['type']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($client['country']); ?></td>
                    <td>
                        <button style="background:none; border:none; color:white; cursor:pointer;"><i class="fa-solid fa-pen"></i></button>
                        <button style="background:none; border:none; color:#ef4444; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Client Modal -->
<div id="addClientModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:100; backdrop-filter:blur(5px);">
    <div class="glass-panel" style="width:500px; margin:100px auto; padding:30px; position:relative;">
        <h3 style="margin-top:0;">Add New Client</h3>
        <button onclick="document.getElementById('addClientModal').style.display='none'" style="position:absolute; top:20px; right:20px; background:none; border:none; color:white; font-size:1.2rem; cursor:pointer;">&times;</button>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_client">
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Company Name</label>
                <input type="text" name="company_name" class="glass-input" required>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Contact Person</label>
                <input type="text" name="contact_person" class="glass-input" required>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Email</label>
                    <input type="email" name="email" class="glass-input">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Phone</label>
                    <input type="text" name="phone" class="glass-input">
                </div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Type</label>
                    <select name="type" class="glass-input" style="background:#1e293b;">
                        <option value="exporter">Exporter</option>
                        <option value="importer">Importer</option>
                    </select>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Country</label>
                    <input type="text" name="country" class="glass-input" value="Kenya">
                </div>
            </div>
            
            <button type="submit" class="glass-btn" style="width:100%;">Save Client</button>
        </form>
    </div>
</div>
