<!-- templates/inspections.php -->
<?php
// Handle Form Submission (Add Inspection) - Simplified for brevity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_inspection') {
    $stmt = $pdo->prepare("INSERT INTO inspections (client_id, product_name, category, inspection_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['client_id'], $_POST['product_name'], $_POST['category'], $_POST['inspection_date'], 'scheduled']);
}

// Fetch Inspections with Client Name
try {
    $sql = "SELECT inspections.*, clients.company_name 
            FROM inspections 
            JOIN clients ON inspections.client_id = clients.id 
            ORDER BY inspection_date DESC";
    $stmt = $pdo->query($sql);
    $inspections = $stmt->fetchAll();
    
    // Fetch clients for dropdown
    $clients_stmt = $pdo->query("SELECT id, company_name FROM clients");
    $clients_list = $clients_stmt->fetchAll();
} catch (PDOException $e) {
    $inspections = [];
    $clients_list = [];
}
?>

<div class="glass-panel" style="padding:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2>Inspection Tracker</h2>
        <button onclick="document.getElementById('addInspectionModal').style.display='block'" class="glass-btn">
            <i class="fa-solid fa-plus"></i> Schedule Inspection
        </button>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Client</th>
                <th>Date</th>
                <th>Category</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($inspections)): ?>
                <tr><td colspan="7" style="text-align:center; color:rgba(255,255,255,0.3);">No inspections found.</td></tr>
            <?php else: ?>
                <?php foreach ($inspections as $insp): ?>
                <tr>
                    <td>#<?php echo $insp['id']; ?></td>
                    <td><?php echo htmlspecialchars($insp['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($insp['company_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($insp['inspection_date'])); ?></td>
                    <td><?php echo ucfirst($insp['category']); ?></td>
                    <td>
                        <span class="badge badge-info"><?php echo ucfirst($insp['status']); ?></span>
                    </td>
                    <td>
                        <a href="#" style="color:#3b82f6; text-decoration:none;">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Inspection Modal -->
<div id="addInspectionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:100; backdrop-filter:blur(5px);">
    <div class="glass-panel" style="width:500px; margin:100px auto; padding:30px; position:relative;">
        <h3 style="margin-top:0;">Schedule New Inspection</h3>
        <button onclick="document.getElementById('addInspectionModal').style.display='none'" style="position:absolute; top:20px; right:20px; background:none; border:none; color:white; font-size:1.2rem; cursor:pointer;">&times;</button>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_inspection">
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Client</label>
                <select name="client_id" class="glass-input" style="background:#1e293b;" required>
                    <option value="">Select Company...</option>
                    <?php foreach ($clients_list as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Product Name</label>
                <input type="text" name="product_name" class="glass-input" required>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Category</label>
                    <select name="category" class="glass-input" style="background:#1e293b;">
                        <option value="agriculture">Agriculture</option>
                        <option value="minerals">Minerals</option>
                        <option value="consumer_goods">Consumer Goods</option>
                        <option value="food">Food</option>
                    </select>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px;">Date</label>
                    <input type="date" name="inspection_date" class="glass-input" required>
                </div>
            </div>
            
            <button type="submit" class="glass-btn" style="width:100%;">Schedule</button>
        </form>
    </div>
</div>
