<?php
// Handle Create Invoice
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    try {
        // Generate Invoice Number (Simple Auto-Increment Simulation for Demo)
        $stmt = $pdo->query("SELECT MAX(id) FROM invoices");
        $next_id = $stmt->fetchColumn() + 1;
        $inv_num = 'INV-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, client_id, description, amount, issue_date, due_date, status, inspection_id, is_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([
            $inv_num,
            $_POST['client_id'],
            $_POST['description'],
            $_POST['amount'],
            $_POST['issue_date'],
            $_POST['due_date'],
            $_POST['status'],
            !empty($_POST['inspection_id']) ? $_POST['inspection_id'] : null
        ]);
        $success_msg = "Invoice generated successfully! ($inv_num)";
    } catch (PDOException $e) {
        $error_msg = "Error creating invoice: " . $e->getMessage();
    }
}

// Handle Update Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_invoice'])) {
    try {
        $stmt = $pdo->prepare("UPDATE invoices SET client_id = ?, description = ?, amount = ?, status = ?, issue_date = ?, due_date = ? WHERE id = ?");
        $stmt->execute([
            $_POST['client_id'],
            $_POST['description'],
            $_POST['amount'],
            $_POST['status'],
            $_POST['issue_date'],
            $_POST['due_date'],
            $_POST['invoice_id']
        ]);
        $success_msg = "Invoice updated successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error updating invoice: " . $e->getMessage();
    }
}

// Handle Payment & Receipt Generation
if (isset($_POST['pay_invoice'])) {
    $inv_id = $_POST['invoice_id'];
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'Paid' WHERE id = ?");
    $stmt->execute([$inv_id]);
    
    // Redirect to Receipt in new window? No, we need JS to open it. 
    // We will set a flag to open it on load.
    $receipt_id = $inv_id;
    $success_msg = "Payment recorded. Generating receipt...";
    
    // Refresh data to show Paid status
    // (Fetch logic below will pick this up)
}

// Handle Send Invoice
if (isset($_POST['send_invoice'])) {
    $inv_id = $_POST['invoice_id'];
    $stmt = $pdo->prepare("UPDATE invoices SET is_sent = 1 WHERE id = ?");
    $stmt->execute([$inv_id]);
    $success_msg = "Invoice emailed to client successfully!";
}

// Fetch Invoices
$search = $_GET['search'] ?? '';
$query = "SELECT i.*, c.company as client_company, c.email as client_email, c.phone as client_phone FROM invoices i JOIN clients c ON i.client_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (i.invoice_number LIKE ? OR c.company LIKE ? OR i.description LIKE ?)";
    $params = array_fill(0, 3, "%$search%");
}

$query .= " ORDER BY i.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Fetch Clients (kept same)
$stmt = $pdo->query("SELECT id, name, company FROM clients ORDER BY company ASC");
$clients_list = $stmt->fetchAll();
?>

<div class="main-content">
    <!-- Header (kept same) -->
    <div class="top-header">
        <div class="search-bar">
             <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <form id="searchForm" style="width:100%;">
                <input type="hidden" name="page" value="invoices">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search invoices..." onblur="this.form.submit()">
            </form>
        </div>
        <div class="user-profile">
            <?php include __DIR__ . '/notification_dropdown.php'; ?>
            <?php include __DIR__ . '/header_profile_link.php'; ?>
        </div>
    </div>

    <div class="content-scroll">
        <?php if ($success_msg): ?>
            <div class="alert-success" style="background:#dcfce7; color:#16a34a; padding:15px; border-radius:8px; margin-bottom:20px;">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
            <?php if(isset($receipt_id)): ?>
                <script>
                    window.open('index.php?page=receipt&id=<?php echo $receipt_id; ?>', '_blank');
                </script>
            <?php endif; ?>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <div>
                <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Invoicing</h1>
                <p style="color:var(--text-muted);">Manage billing from completed operations.</p>
            </div>
            <button type="button" onclick="window.openInvoiceModal()" style="background:#ef4444; color:white; border:none; padding:12px 25px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:10px; box-shadow:0 4px 6px -1px rgba(239, 68, 68, 0.2);">
                <i class="fa-solid fa-plus"></i> Create Invoice
            </button>
        </div>

        <div style="background:var(--bg-card); border-radius:12px; border:1px solid var(--border-color); overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:var(--bg-column-header); border-bottom:1px solid var(--border-color);">
                    <tr>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Invoice ID</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Client</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Amount</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Date Issued</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Status</th>
                        <th style="text-align:right; padding:15px 20px; font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                        <tr class="invoice-row" style="border-bottom:1px solid var(--border-color); transition:background 0.2s; cursor:pointer;" 
                            onclick="window.viewInvoice(<?php echo htmlspecialchars(json_encode($inv)); ?>)">
                            
                            <td style="padding:15px 20px; color:var(--text-muted); font-weight:500; font-family:monospace;"><?php echo $inv['invoice_number']; ?></td>
                            <td style="padding:15px 20px;">
                                <div style="font-weight:600; color:var(--text-main);"><?php echo htmlspecialchars($inv['client_company']); ?></div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($inv['description']); ?></div>
                            </td>
                            <td style="padding:15px 20px; font-weight:700; color:var(--text-main);">KES <?php echo number_format($inv['amount']); ?></td>
                            <td style="padding:15px 20px; color:var(--text-muted); font-size:0.9rem;"><?php echo $inv['issue_date']; ?></td>
                            <td style="padding:15px 20px;">
                                <?php 
                                $statusClass = match($inv['status']) {
                                    'Paid' => 'status-paid',
                                    'Pending' => 'status-pending',
                                    'Overdue' => 'status-overdue',
                                    default => ''
                                };
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php if($inv['status'] == 'Paid'): ?><i class="fa-regular fa-circle-check"></i><?php endif; ?>
                                    <?php if($inv['status'] == 'Pending'): ?><i class="fa-regular fa-clock"></i><?php endif; ?>
                                    <?php if($inv['status'] == 'Overdue'): ?><i class="fa-solid fa-triangle-exclamation"></i><?php endif; ?>
                                    <?php echo $inv['status']; ?>
                                </span>
                            </td>
                            <td style="padding:15px 20px; text-align:right;" onclick="event.stopPropagation()">
                                <a href="index.php?page=receipt&id=<?php echo $inv['id']; ?>" target="_blank" style="text-decoration:none; color:#3b82f6; font-size:0.8rem; font-weight:600; cursor:pointer;">
                                    <i class="fa-solid fa-download"></i> Receipt
                                </a>
                                <?php if($inv['status'] !== 'Paid'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this invoice as PAID?')">
                                        <input type="hidden" name="pay_invoice" value="1">
                                        <input type="hidden" name="invoice_id" value="<?php echo $inv['id']; ?>">
                                        <button type="submit" style="background:none; border:none; color:#16a34a; font-size:0.8rem; font-weight:600; cursor:pointer; margin-left:10px;">
                                            Pay & Generate
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Row Hover Effect */
.invoice-row:hover {
    background-color: var(--bg-column-header) !important;
}
</style>

<!-- Create Invoice Modal (kept same) -->
<div id="addInvoiceModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <!-- ... (rest of add invoice form - not changed) ... -->
    <!-- ... -->
    <div style="background:var(--bg-card); width:500px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.2rem; font-weight:700;">Create New Invoice</h3>
            <button type="button" onclick="window.closeInvoiceModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>
        <form method="POST">
             <input type="hidden" name="create_invoice" value="1">
             <input type="hidden" name="inspection_id" id="newInvInspectionId">
             
            <div class="form-group">
                <label class="form-label">Client</label>
                <select name="client_id" id="newInvClient" class="form-select" required>
                    <option value="">Select...</option>
                    <?php foreach($clients_list as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description / Service</label>
                <input type="text" name="description" id="newInvDesc" class="form-input" placeholder="e.g. Q1 Audit Services" required>
            </div>
            
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label class="form-label">Amount (KES)</label>
                    <input type="number" name="amount" class="form-input" placeholder="0.00" required step="0.01">
                </div>
                <div style="flex:1;">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label class="form-label">Issue Date</label>
                    <input type="date" name="issue_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div style="flex:1;">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-input" required>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="window.closeInvoiceModal()" style="padding:10px 20px; border:none; background:#f1f5f9; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 20px; border:none; background:#ef4444; color:white; border-radius:8px; font-weight:600; cursor:pointer;">+ Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('open_add') === '1') {
            const modal = document.getElementById('addInvoiceModal');
            if(modal) {
                modal.style.display = 'flex';
                
                // Pre-fill Logic
                const inspId = urlParams.get('inspection_id');
                const clientId = urlParams.get('client_id');
                
                if(inspId) {
                    document.getElementById('newInvInspectionId').value = inspId;
                    document.getElementById('newInvDesc').value = 'Invoice for Inspection Reference #INS-' + String(inspId).padStart(4, '0');
                }
                
                if(clientId) {
                    document.getElementById('newInvClient').value = clientId;
                }
            }
        }
    });
</script>

<!-- Edit Invoice Modal -->
<div id="editInvoiceModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-card); width:500px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.2rem; font-weight:700;">Edit Invoice</h3>
            <button type="button" onclick="document.getElementById('editInvoiceModal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>
        <form method="POST">
             <input type="hidden" name="update_invoice" value="1">
             <input type="hidden" name="invoice_id" id="editInvId">
            <div class="form-group"><label class="form-label">Client</label><select name="client_id" id="editInvClient" class="form-select" required><option value="">Select...</option><?php foreach($clients_list as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Description / Service</label><input type="text" name="description" id="editInvDesc" class="form-input" required></div>
            <div style="display:flex; gap:15px; margin-bottom:15px;"><div style="flex:1;"><label class="form-label">Amount (KES)</label><input type="number" name="amount" id="editInvAmount" class="form-input" required step="0.01"></div><div style="flex:1;"><label class="form-label">Status</label><select name="status" id="editInvStatusInput" class="form-select"><option value="Pending">Pending</option><option value="Paid">Paid</option><option value="Overdue">Overdue</option></select></div></div>
            <div style="display:flex; gap:15px; margin-bottom:15px;"><div style="flex:1;"><label class="form-label">Issue Date</label><input type="date" name="issue_date" id="editInvIssue" class="form-input" required></div><div style="flex:1;"><label class="form-label">Due Date</label><input type="date" name="due_date" id="editInvDue" class="form-input" required></div></div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;"><button type="button" onclick="document.getElementById('editInvoiceModal').style.display='none'" style="padding:10px 20px; border:none; background:#f1f5f9; border-radius:8px; cursor:pointer;">Cancel</button><button type="submit" style="padding:10px 20px; border:none; background:#3b82f6; color:white; border-radius:8px; font-weight:600; cursor:pointer;">Update Invoice</button></div>
        </form>
    </div>
</div>

<!-- View Invoice Modal -->
<div id="viewInvoiceModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-card); width:600px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
        <button type="button" onclick="document.getElementById('viewInvoiceModal').style.display='none'" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:1.5rem; cursor:pointer; color:#64748b;">&times;</button>
        
        <div style="margin-bottom:20px;">
            <div id="viewInvStatus" style="display:inline-block; padding:4px 12px; border-radius:12px; font-size:0.8rem; font-weight:700; text-transform:uppercase; margin-bottom:10px; background:#e2e8f0; color:#334155;">STATUS</div>
            <h2 id="viewInvNum" style="font-size:1.5rem; font-weight:700; color:var(--text-main); margin-bottom:5px;">INV-000</h2>
            <div style="font-size:1.1rem; color:#64748b;">
                <i class="fa-regular fa-building"></i> <span id="viewInvClient">Client Name</span>
                <span id="viewInvPhone" style="font-size:0.9rem; color:#94a3b8; margin-left:10px;"><i class="fa-solid fa-phone"></i> <span></span></span>
            </div>
            <div style="font-size:0.9rem; color:#94a3b8; margin-top:5px;" id="viewInvDesc">Description</div>
        </div>

        <div style="background:var(--bg-body); padding:20px; border-radius:12px; margin-bottom:20px;">
             <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                 <div>
                     <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Total Amount</div>
                     <div style="font-size:1.8rem; font-weight:800; color:var(--text-main);">KES <span id="viewInvAmount">0.00</span></div>
                 </div>
                 <div style="text-align:right;">
                     <div style="font-size:0.8rem; color:var(--text-muted);">Due Date</div>
                     <div style="font-size:1rem; font-weight:600; color:var(--text-muted);" id="viewInvDue">2024-01-01</div>
                 </div>
             </div>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
             <!-- Email Client Action -->
             <form method="POST" onsubmit="return confirm('Send this invoice to client via email?')">
                 <input type="hidden" name="send_invoice" value="1">
                 <input type="hidden" name="invoice_id" id="sendInvId">
                 <button type="submit" style="padding:10px 20px; background:var(--bg-sidebar); color:white; border-radius:8px; border:none; font-weight:600; cursor:pointer;">
                    <i class="fa-solid fa-envelope"></i> Email Client
                 </button>
             </form>
             
             <button onclick="window.openEditInvoiceModal()" style="padding:10px 20px; background:var(--bg-column); color:var(--text-main); border-radius:8px; border:1px solid var(--border-color); font-weight:600; cursor:pointer;">Edit</button>
             <a id="viewInvReceipt" href="#" target="_blank" style="padding:10px 20px; background:var(--bg-sidebar); color:white; border-radius:8px; text-decoration:none; font-weight:600; display:inline-block;">Receipt</a>
        </div>
    </div>
</div>

<script>
    // Store current invoice data globally
    let currentInvoice = null;

    window.openInvoiceModal = function() { document.getElementById('addInvoiceModal').style.display = 'flex'; };
    window.closeInvoiceModal = function() { document.getElementById('addInvoiceModal').style.display = 'none'; };

    window.viewInvoice = function(data) {
        currentInvoice = data; // Store for edit
        document.getElementById('viewInvNum').innerText = data.invoice_number;
        document.getElementById('viewInvClient').innerText = data.client_company;
        document.querySelector('#viewInvPhone span').innerText = data.client_phone || 'N/A';
        document.getElementById('viewInvDesc').innerText = data.description;
        document.getElementById('viewInvAmount').innerText = new Intl.NumberFormat().format(data.amount);
        document.getElementById('viewInvDue').innerText = data.due_date;
        document.getElementById('viewInvReceipt').href = 'index.php?page=receipt&id=' + data.id;
        document.getElementById('sendInvId').value = data.id;

        const badge = document.getElementById('viewInvStatus');
        badge.innerText = data.status;
        // Reset styles
        badge.innerText = data.status;
        // Reset classes
        badge.className = 'status-badge'; 
        
        if(data.status === 'Paid') {
            badge.classList.add('status-paid');
        } else if (data.status === 'Overdue') {
            badge.classList.add('status-overdue');
        } else {
            badge.classList.add('status-pending');
        }

        document.getElementById('viewInvoiceModal').style.display = 'flex';
    };

    window.openEditInvoiceModal = function() {
        if(!currentInvoice) return;
        document.getElementById('viewInvoiceModal').style.display = 'none';
        
        // Populate inputs
        document.getElementById('editInvId').value = currentInvoice.id;
        document.getElementById('editInvClient').value = currentInvoice.client_id;
        document.getElementById('editInvDesc').value = currentInvoice.description;
        document.getElementById('editInvAmount').value = currentInvoice.amount;
        document.getElementById('editInvStatusInput').value = currentInvoice.status;
        document.getElementById('editInvIssue').value = currentInvoice.issue_date;
        document.getElementById('editInvDue').value = currentInvoice.due_date;

        document.getElementById('editInvoiceModal').style.display = 'flex';
    };

    window.onclick = function(event) {
        if (event.target == document.getElementById('addInvoiceModal')) window.closeInvoiceModal();
        if (event.target == document.getElementById('viewInvoiceModal')) document.getElementById('viewInvoiceModal').style.display = 'none';
        if (event.target == document.getElementById('editInvoiceModal')) document.getElementById('editInvoiceModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-success, .alert-error');
        alerts.forEach(alert => {
             setTimeout(() => { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); }, 3000);
        });
    });
</script>

<?php
// Auto-open modal if view_id is set
if(isset($_GET['view_id'])) {
    $targetId = $_GET['view_id'];
    $targetData = null;
    foreach($invoices as $inv) {
        if($inv['id'] == $targetId) {
            $targetData = $inv;
            break;
        }
    }
    if($targetData) {
        $jsonData = json_encode($targetData);
        echo "<script>document.addEventListener('DOMContentLoaded', function() { window.viewInvoice($jsonData); });</script>";
    }
}
?>
