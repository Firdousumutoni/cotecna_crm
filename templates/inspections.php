<?php
// Handle UPDATE actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inspection'])) {
    try {
        $stmt = $pdo->prepare("UPDATE inspections SET location = ?, status = ?, notes = ?, inspector_name = ?, inspection_date = ? WHERE id = ?");
        $stmt->execute([
            $_POST['location'],
            $_POST['status'],
            $_POST['notes'],
            $_POST['inspector_name'],
            $_POST['inspection_date'],
            $_POST['inspection_id']
        ]);
        // Auto-Create Invoice if Completed/Certified
        if (in_array($_POST['status'], ['completed', 'certified'])) {
            // Check if invoice already exists
            $chk = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE inspection_id = ?");
            $chk->execute([$_POST['inspection_id']]);
            
            if ($chk->fetchColumn() == 0) {
                // Fetch Deal Amount if linked
                $amount = 0.00;
                $inspId = $_POST['inspection_id'];
                
                // Get Deal ID from Inspection
                $getDeal = $pdo->prepare("SELECT deal_id, product_name, client_id FROM inspections WHERE id = ?");
                $getDeal->execute([$inspId]);
                $inspData = $getDeal->fetch();
                
                if ($inspData && $inspData['deal_id']) {
                    $getAmt = $pdo->prepare("SELECT amount FROM deals WHERE id = ?");
                    $getAmt->execute([$inspData['deal_id']]);
                    $amount = $getAmt->fetchColumn() ?: 0.00;
                }

                // Generate Invoice Number
                $stmtNum = $pdo->query("SELECT MAX(id) FROM invoices");
                $next_id = $stmtNum->fetchColumn() + 1;
                $inv_num = 'INV-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

                // Create Invoice
                $stmtInv = $pdo->prepare("INSERT INTO invoices (invoice_number, client_id, description, amount, issue_date, due_date, status, inspection_id, is_sent) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, 0)");
                $stmtInv->execute([
                    $inv_num,
                    $inspData['client_id'],
                    'Invoice for Inspection: ' . $inspData['product_name'],
                    $amount,
                    date('Y-m-d'), // Issue Date
                    date('Y-m-d', strtotime('+30 days')), // Due Date
                    $inspId
                ]);
                echo "<script>alert('Inspection updated & Invoice generated automatically!'); window.location='index.php?page=inspections';</script>";
                exit; // Stop further execution
            }
        }

        echo "<script>alert('Inspection updated successfully!'); window.location='index.php?page=inspections';</script>";
    } catch (PDOException $e) {
        $error = "Error updating: " . $e->getMessage();
    }
}

// Handle CREATE actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_inspection'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO inspections (client_id, product_name, category, inspection_date, status, inspector_name, location, notes, deal_id, order_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['client_id'],
            $_POST['product_name'],
            $_POST['category'],
            $_POST['inspection_date'],
            $_POST['status'],
            $_POST['inspector_name'],
            $_POST['location'],
            $_POST['notes'],
            !empty($_POST['deal_id']) ? $_POST['deal_id'] : null,
            $_POST['order_number'] ?? null
        ]);
        echo "<script>alert('New Inspection created successfully!'); window.location='index.php?page=inspections';</script>";
    } catch (PDOException $e) {
        $error = "Error creating: " . $e->getMessage();
    }
}

// Fetch Clients for Dropdown
$clients = $pdo->query("SELECT id, company FROM clients ORDER BY company ASC")->fetchAll();

// Fetch Inspections
$filter_status = $_GET['status'] ?? '';
$sql = "SELECT i.*, c.company FROM inspections i JOIN clients c ON i.client_id = c.id";
if ($filter_status) {
    // Add filtering logic if needed
}
$sql .= " ORDER BY i.inspection_date DESC";
$stmt = $pdo->query($sql);
$inspections = $stmt->fetchAll();
?>

<div class="main-content">
    <div class="top-header">
        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Search inspections..." style="border:none; outline:none; font-size:0.9rem; color:#475569; width:100%;">
        </div>

        <div class="user-profile">
            <?php include __DIR__ . '/notification_dropdown.php'; ?>
            <?php include __DIR__ . '/header_profile_link.php'; ?>
        </div>
    </div>

    <div class="content-scroll">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px;">
            <div>
                <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Field Inspections</h1>
                <p style="color:var(--text-muted);">Manage ongoing field operations and reports.</p>
            </div>
            <button onclick="document.getElementById('newInspectionModal').style.display='flex'" class="btn-primary" style="background:var(--brand-blue); color:white; border:none; padding:12px 25px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:10px; box-shadow:0 4px 6px -1px rgba(22, 163, 74, 0.2);"><i class="fa-solid fa-plus"></i> New Inspection</button>
        </div>

    <!-- CARDS GRID -->
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
        <?php foreach($inspections as $insp): 
            $status_config = [
                'scheduled' => ['label'=>'Scheduled', 'class'=>'badge-blue'],
                'in_progress' => ['label'=>'In Progress', 'class'=>'badge-blue-dark'],
                'completed' => ['label'=>'Completed', 'class'=>'badge-green'],
                'certified' => ['label'=>'Certified', 'class'=>'badge-green'],
                'rejected' => ['label'=>'Rejected', 'class'=>'badge-red'],
                'delayed' => ['label'=>'Delayed', 'class'=>'badge-orange']
            ];
            $s = $status_config[$insp['status']] ?? $status_config['scheduled'];
        ?>
        <div class="inspection-card">
            <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                <span class="status-pill <?php echo $s['class']; ?>">
                    <?php echo $s['label']; ?>
                </span>
                <i class="fa-regular fa-file-lines" style="color:var(--text-muted);"></i>
            </div>

            <div style="margin-bottom:15px;">
                <h3 style="font-size:1rem; font-weight:700; color:var(--text-main); margin-bottom:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?php echo htmlspecialchars($insp['company']); ?>
                </h3>
                <div style="color:var(--text-muted); font-size:0.85rem;"><?php echo htmlspecialchars($insp['product_name']); ?></div>
            </div>

            <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px; font-size:0.85rem; color:var(--text-muted);">
                <div style="display:flex; gap:10px; align-items:center;">
                    <i class="fa-solid fa-location-dot" style="color:var(--text-muted); width:15px;"></i> 
                    <?php echo htmlspecialchars($insp['location'] ?? 'Not set'); ?>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <i class="fa-regular fa-calendar" style="color:var(--text-muted); width:15px;"></i>
                    <?php echo date('Y-m-d', strtotime($insp['inspection_date'])); ?>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <i class="fa-regular fa-user" style="color:var(--text-muted); width:15px;"></i>
                    <?php echo htmlspecialchars($insp['inspector_name']); ?>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto;">
                <button onclick='openViewModal(<?php echo json_encode($insp); ?>)' style="background:none; border:none; color:var(--text-muted); font-weight:600; cursor:pointer; font-size:0.9rem;">Details</button>
                <button class="btn-primary" onclick='openUpdateModal(<?php echo json_encode($insp); ?>)' style="background-color:var(--primary-color); border-color:var(--primary-color); padding:8px 24px; border-radius:8px;">Update</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- UPDATE INSPECTION MODAL -->
<div id="updateInspectionModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:var(--bg-card); width:500px; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h2 style="font-size:1.2rem; font-weight:700; color:var(--text-main);">Inspection Details</h2>
            <button onclick="document.getElementById('updateInspectionModal').style.display='none'" style="border:none; background:none; font-size:1.2rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="update_inspection" value="1">
            <input type="hidden" name="inspection_id" id="modalId">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label class="form-label">Type</label>
                    <input type="text" id="modalType" disabled class="form-input" style="background:var(--bg-column);">
                </div>
                 <div>
                    <label class="form-label">Location</label>
                    <input type="text" name="location" id="modalLocation" class="form-input">
                </div>
            </div>

             <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="inspection_date" id="modalDate" class="form-input">
                </div>
                 <div>
                    <label class="form-label">Current Status</label>
                    <select name="status" id="modalStatus" class="form-input">
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="certified">Certified</option>
                        <option value="rejected">Rejected</option>
                        <option value="delayed">Delayed</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:15px;">
                <label class="form-label">Assigned Inspector</label>
                <input type="text" name="inspector_name" id="modalInspector" class="form-input">
            </div>

            <div style="margin-bottom:20px;">
                <label class="form-label">Notes / Description</label>
                <textarea name="notes" id="modalNotes" rows="3" class="form-input"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="document.getElementById('updateInspectionModal').style.display='none'" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Save Details</button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW DETAILS MODAL -->
<div id="viewInspectionModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:var(--bg-card); width:500px; border-radius:12px; padding:30px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:center;">
             <div>
                <h2 style="font-size:1.2rem; font-weight:700; color:var(--text-main); margin:0;">Inspection Details</h2>
                <div id="viewRef" style="color:var(--text-muted); font-size:0.85rem; font-weight:600; margin-top:3px;">#INS-0000</div>
            </div>
            <button onclick="document.getElementById('viewInspectionModal').style.display='none'" style="border:none; background:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
            <div>
                <div class="detail-label">Client</div>
                <div id="viewClient" class="detail-value">Client Name</div>
            </div>
             <div>
                <div class="detail-label">Product</div>
                <div id="viewProduct" class="detail-value">Product Name</div>
            </div>
             <div>
                <div class="detail-label">Category</div>
                <div id="viewCategory" class="detail-value">Category</div>
            </div>
             <div>
                <div class="detail-label">Date</div>
                <div id="viewDate" class="detail-value">2025-01-01</div>
            </div>
             <div>
                <div class="detail-label">Status</div>
                <div id="viewStatus" class="detail-value">Scheduled</div>
            </div>
             <div>
                <div class="detail-label">Revenue</div>
                <div id="viewRevenue" class="detail-value">KES 0.00</div>
            </div>
        </div>
        
        <div style="margin-bottom:20px; border-top:1px solid var(--border-color); padding-top:15px;">
             <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <div class="detail-label">Assigned Inspector</div>
                    <div id="viewInspector" class="detail-value">Inspector Name</div>
                </div>
                <div>
                    <div class="detail-label">Location</div>
                    <div id="viewLocation" class="detail-value">Location Name</div>
                </div>
             </div>
        </div>

        <div style="margin-bottom:25px;">
            <div class="detail-label">Notes</div>
            <div id="viewNotes" class="detail-value" style="background:var(--bg-body); padding:10px; border-radius:8px; font-size:0.85rem; line-height:1.5;">Notes content...</div>
        </div>

        <div style="text-align:right; display:flex; justify-content:flex-end; gap:10px;">
            <a id="btnGenInvoice" href="#" style="display:none; padding:10px 20px; text-decoration:none; background:#0f172a; color:white; border-radius:8px; font-weight:600; align-items:center; gap:8px;">
                 <i class="fa-solid fa-file-invoice-dollar"></i> Generate Invoice
            </a>
            <button onclick="document.getElementById('viewInspectionModal').style.display='none'" class="btn-primary">Close</button>
        </div>
    </div>
    </div>
</div>

<!-- NEW INSPECTION MODAL -->
<div id="newInspectionModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:var(--bg-card); width:500px; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h2 style="font-size:1.2rem; font-weight:700; color:var(--text-main);">New Inspection Request</h2>
            <button onclick="document.getElementById('newInspectionModal').style.display='none'" style="border:none; background:none; font-size:1.2rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="create_inspection" value="1">
            <input type="hidden" name="deal_id" id="newInspDealId">

            <!-- Process Flow: Order Number -->
            <div style="margin-bottom:15px;" id="orderNumContainer">
                 <label class="form-label" style="font-weight:700; color:var(--brand-blue);">Order / Job Number</label>
                 <input type="text" name="order_number" id="newInspOrderNum" class="form-input" placeholder="e.g. ORD-2025-001">
            </div>

            <div style="margin-bottom:15px;">
                <label class="form-label">Client</label>
                <select name="client_id" id="newInspClient" class="form-input" required>
                    <?php foreach($clients as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                     <label class="form-label">Product Name</label>
                     <input type="text" name="product_name" class="form-input" required placeholder="e.g. Copper Cathodes">
                </div>
                 <div>
                    <label class="form-label">Category</label>
                    <select name="category" class="form-input">
                        <option value="Agriculture">Agriculture</option>
                        <option value="Minerals & Metals">Minerals & Metals</option>
                        <option value="Consumer Goods">Consumer Goods</option>
                        <option value="Logistics">Logistics</option>
                        <option value="Government">Government</option>
                        <option value="Food Safety">Food Safety</option>
                        <option value="Oil & Gas">Oil & Gas</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

             <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label class="form-label">Inspection Date</label>
                    <input type="date" name="inspection_date" class="form-input" required>
                </div>
                 <div>
                    <label class="form-label">Initial Status</label>
                    <select name="status" class="form-input">
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label class="form-label">Assigned Inspector</label>
                    <input type="text" name="inspector_name" class="form-input" required placeholder="Name">
                </div>
                 <div>
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-input" required placeholder="e.g. Mombasa Port">
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-input" placeholder="Additional details..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="document.getElementById('newInspectionModal').style.display='none'" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Schedule Inspection</button>
            </div>
        </form>
    </div>
</div>

<style>
.detail-label { font-size:0.75rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px; }
.detail-value { font-size:0.95rem; font-weight:600; color:var(--text-main); }

.inspection-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, box-shadow 0.2s;
}
.inspection-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px var(--shadow-subtle);
    border-color: var(--primary-color);
}
.form-label { display:block; font-size:0.85rem; font-weight:600; color:var(--text-muted); margin-bottom:5px; }
.form-input { width:100%; padding:10px; border:1px solid var(--border-color); border-radius:8px; font-size:0.9rem; outline:none; background:var(--bg-body); color:var(--text-main); }
.form-input:focus { border-color:var(--primary-color); box-shadow:0 0 0 2px rgba(59,130,246,0.1); }

/* Status Badges */
.status-pill { padding:4px 12px; border-radius:12px; font-size:0.75rem; font-weight:700; display:inline-block; }
.badge-blue { background:#eff6ff; color:#3b82f6; }
.badge-blue-dark { background:#dbeafe; color:#2563eb; }
.badge-green { background:#dcfce7; color:#16a34a; }
.badge-red { background:#fee2e2; color:#ef4444; }
.badge-orange { background:#ffedd5; color:#ea580c; }

body.dark-mode .badge-blue { background:rgba(59, 130, 246, 0.2); color:#60a5fa; }
body.dark-mode .badge-blue-dark { background:rgba(37, 99, 235, 0.2); color:#60a5fa; }
body.dark-mode .badge-green { background:rgba(22, 163, 74, 0.2); color:#4ade80; }
body.dark-mode .badge-red { background:rgba(239, 68, 68, 0.2); color:#f87171; }
body.dark-mode .badge-orange { background:rgba(234, 88, 12, 0.2); color:#fb923c; }
</style>

<script>
    function openViewModal(data) {
        document.getElementById('viewRef').innerText = '#INS-' + String(data.id).padStart(4, '0');
        document.getElementById('viewClient').innerText = data.company;
        document.getElementById('viewProduct').innerText = data.product_name;
        document.getElementById('viewCategory').innerText = data.category.charAt(0).toUpperCase() + data.category.slice(1);
        document.getElementById('viewDate').innerText = data.inspection_date;
        document.getElementById('viewStatus').innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('_', ' ');
        document.getElementById('viewInspector').innerText = data.inspector_name;
        document.getElementById('viewLocation').innerText = data.location || 'Not set';
        document.getElementById('viewRevenue').innerText = data.revenue ? 'KES ' + Number(data.revenue).toLocaleString() : 'N/A';
        document.getElementById('viewNotes').innerText = data.notes || 'No notes available.';
        
        // Process Flow: Show Invoice Button if Completed or Certified
        const btnInv = document.getElementById('btnGenInvoice');
        if (data.status === 'completed' || data.status === 'certified') {
            btnInv.style.display = 'flex';
            // Link to Invoices Page
            btnInv.href = `index.php?page=invoices&open_add=1&inspection_id=${data.id}&client_id=${data.client_id}`;
        } else {
            btnInv.style.display = 'none';
        }

        document.getElementById('viewInspectionModal').style.display = 'flex';
    }

    function openUpdateModal(data) {
        document.getElementById('modalId').value = data.id;
        document.getElementById('modalType').value = data.category; // Or product name
        document.getElementById('modalLocation').value = data.location || '';
        document.getElementById('modalDate').value = data.inspection_date;
        document.getElementById('modalStatus').value = data.status;
        document.getElementById('modalInspector').value = data.inspector_name;
        document.getElementById('modalNotes').value = data.notes || '';
        
        document.getElementById('updateInspectionModal').style.display = 'flex';
    }

    // Auto-Open Logic for Process Flow
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('open_add') === '1') {
            const addModal = document.getElementById('newInspectionModal');
            if(addModal) {
                addModal.style.display = 'flex';
                
                // Pre-fill Deal ID if present
                const dealId = urlParams.get('deal_id');
                if(dealId) {
                    const el = document.getElementById('newInspDealId');
                    if(el) el.value = dealId;
                }

                // Pre-select Client if present
                const clientId = urlParams.get('client_id');
                if(clientId) {
                    const clientSelect = document.getElementById('newInspClient');
                    if(clientSelect) clientSelect.value = clientId;
                }

                // Highlight Order Number field
                const orderInput = document.getElementById('newInspOrderNum');
                if(orderInput) orderInput.focus();
            }
        }
    });
</script>

<?php
// Auto-open modal if view_id is set
if(isset($_GET['view_id'])) {
    $targetId = $_GET['view_id'];
    $targetData = null;
    foreach($inspections as $insp) {
        if($insp['id'] == $targetId) {
            $targetData = $insp;
            break;
        }
    }
    if($targetData) {
        // Ensure data is safe for JS
        $jsonData = json_encode($targetData);
        echo "<script>document.addEventListener('DOMContentLoaded', function() { openViewModal($jsonData); });</script>";
    }
}
?>
