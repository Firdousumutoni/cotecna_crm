<?php
// Handle Add Deal
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deal'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO deals (client_id, deal_name, amount, stage, expected_close_date, description, probability) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['client_id'],
            $_POST['deal_name'],
            $_POST['amount'],
            $_POST['stage'],
            $_POST['expected_close_date'] ?: NULL,
            $_POST['description'] ?? '',
            $_POST['stage'] == 'Closed Won' ? 100 : ($_POST['stage'] == 'Closed Lost' ? 0 : 50) // Simplified prob logic
        ]);
        $success_msg = "Deal added to pipeline!";
    } catch (PDOException $e) {
        $error_msg = "Error adding deal: " . $e->getMessage();
    }
}

// Handle Update Deal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_deal'])) {
    try {
        $stmt = $pdo->prepare("UPDATE deals SET deal_name = ?, client_id = ?, amount = ?, stage = ?, probability = ?, description = ?, expected_close_date = ? WHERE id = ?");
        $stmt->execute([
             $_POST['deal_name'],
             $_POST['client_id'],
             $_POST['amount'],
             $_POST['stage'],
             $_POST['probability'],
             $_POST['description'],
             $_POST['expected_close_date'] ?: NULL,
             $_POST['deal_id']
        ]);

        // Auto-Create Inspection if Closed Won
        if ($_POST['stage'] === 'Closed Won') {
            // Check if inspection already exists for this deal
            $chk = $pdo->prepare("SELECT COUNT(*) FROM inspections WHERE deal_id = ?");
            $chk->execute([$_POST['deal_id']]);
            if ($chk->fetchColumn() == 0) {
                // Fetch Client Sector
                $stmtCl = $pdo->prepare("SELECT sector FROM clients WHERE id = ?");
                $stmtCl->execute([$_POST['client_id']]);
                $clSector = $stmtCl->fetchColumn() ?: 'Other';

                // Create Inspection
                $insDate = $_POST['expected_close_date'] ?: date('Y-m-d', strtotime('+7 days'));
                $stmtIns = $pdo->prepare("INSERT INTO inspections (client_id, deal_id, product_name, category, inspection_date, status, inspector_name, location, notes) VALUES (?, ?, ?, ?, ?, 'Scheduled', 'Unassigned', 'TBD', ?)");
                $stmtIns->execute([
                    $_POST['client_id'],
                    $_POST['deal_id'],
                    $_POST['deal_name'], // Product Name
                    $clSector,           // Category inherited from Sector
                    $insDate,
                    'Auto-generated from Deal: ' . $_POST['deal_name']
                ]);
                $success_msg = "Deal updated & Inspection scheduled automatically!";
            } else {
                $success_msg = "Deal updated successfully!";
            }
        } else {
            $success_msg = "Deal updated successfully!";
        }
    } catch (PDOException $e) {
        $error_msg = "Error updating deal: " . $e->getMessage();
    }
}

// Fetch Clients
$stmt = $pdo->query("SELECT id, name, company FROM clients ORDER BY company ASC");
$clients_list = $stmt->fetchAll();

// Fetch Deals
$stmt = $pdo->query("SELECT d.*, c.company as client_company FROM deals d JOIN clients c ON d.client_id = c.id ORDER BY d.created_at DESC");
$all_deals = $stmt->fetchAll();

// Group Deals by Stage
$pipeline = [
    'Lead' => [],
    'Negotiation' => [],
    'Proposal' => [],
    'Closed Won' => [],
    'Closed Lost' => []
];

foreach ($all_deals as $deal) {
    if (isset($pipeline[$deal['stage']])) {
        $pipeline[$deal['stage']][] = $deal;
    }
}

// Stage Utilities
$stage_colors = [
    'Lead' => '#475569',        // Slate
    'Negotiation' => '#ea580c', // Orange
    'Proposal' => '#005eb8',    // Brand Blue
    'Closed Won' => '#16a34a',  // Green
    'Closed Lost' => '#dc2626'  // Red
];
?>



<div class="main-content" style="overflow-x:hidden;">
    <!-- ... Header ... -->
    <div class="top-header">
        <div class="search-bar">
             <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Search pipeline..." disabled style="cursor:not-allowed;">
        </div>

        <div class="user-profile">
            <?php include __DIR__ . '/notification_dropdown.php'; ?>
            <?php include __DIR__ . '/header_profile_link.php'; ?>
        </div>
    </div>

    <div class="content-scroll" style="display:flex; flex-direction:column; background:var(--bg-body); overflow-x:auto;">
        <?php if ($success_msg): ?>
            <!-- ... Success Alert ... -->
             <div class="alert-success" style="background:#dcfce7; color:#16a34a; padding:15px; border-radius:8px; margin-bottom:20px;">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-shrink:0;">
            <div>
                <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Deals Pipeline</h1>
                <p style="color:var(--text-muted);">Track opportunities from lead to close.</p>
            </div>
            <button type="button" onclick="window.openDealModal()" style="background:#16a34a; color:white; border:none; padding:12px 25px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:10px; box-shadow:0 4px 6px -1px rgba(22, 163, 74, 0.2);">
                <i class="fa-solid fa-plus"></i> New Deal
            </button>
        </div>

        <!-- Kanban Board Container -->
        <div style="display:flex; gap:20px; align-items:flex-start; height:100%; padding-bottom:20px; min-width:1400px;"> 
            
            <?php foreach ($pipeline as $stage => $deals): ?>
                <div style="flex:1; background:var(--bg-column); border-radius:12px; display:flex; flex-direction:column; min-width:280px; max-width:320px;">
                    <!-- Column Header -->
                    <div style="padding:15px; border-bottom:2px solid <?php echo $stage_colors[$stage]; ?>; display:flex; justify-content:space-between; align-items:center; background:var(--bg-column-header); border-radius:12px 12px 0 0;">
                        <span style="font-weight:700; color:var(--text-main); font-size:0.95rem; text-transform:uppercase;"><?php echo $stage; ?></span>
                        <span style="background:white; padding:2px 8px; border-radius:12px; font-size:0.8rem; font-weight:600; color:<?php echo $stage_colors[$stage]; ?>"><?php echo count($deals); ?></span>
                    </div>

                    <!-- Cards Area -->
                    <div style="padding:15px; display:flex; flex-direction:column; gap:15px; overflow-y:auto; max-height:calc(100vh - 250px);">
                        <?php foreach($deals as $deal): ?>
                            <div onclick="window.viewDeal(this)" 
                                 data-id="<?php echo $deal['id']; ?>"
                                 data-title="<?php echo htmlspecialchars($deal['deal_name']); ?>"
                                 data-client="<?php echo htmlspecialchars($deal['client_company']); ?>"
                                 data-client-id="<?php echo $deal['client_id']; ?>"
                                 data-amount="<?php echo number_format($deal['amount']); ?>"
                                 data-stage="<?php echo $deal['stage']; ?>"
                                 data-prob="<?php echo $deal['probability'] ?? 0; ?>"
                                 data-desc="<?php echo htmlspecialchars($deal['description'] ?? ''); ?>"
                                 data-date-raw="<?php echo $deal['expected_close_date']; ?>"
                                 data-date="<?php echo $deal['expected_close_date'] ? date('M d, Y', strtotime($deal['expected_close_date'])) : 'TBD'; ?>"
                                 style="background:var(--bg-card); padding:15px; border-radius:8px; box-shadow:0 2px 4px var(--shadow-subtle); border:1px solid var(--border-color); cursor:pointer; transition:all 0.2s;" 
                                 onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)'" 
                                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px var(--shadow-subtle)'">
                                
                                <div style="font-size:0.95rem; font-weight:600; color:var(--text-main); margin-bottom:5px; line-height:1.3;">
                                    <?php echo htmlspecialchars($deal['deal_name']); ?>
                                </div>
                                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:10px;">
                                    <i class="fa-regular fa-building"></i> <?php echo htmlspecialchars($deal['client_company']); ?>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div style="font-weight:700; color:var(--brand-blue);">
                                        KES <?php echo number_format($deal['amount']); ?>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">
                                        <?php echo $deal['probability'] ?? 0; ?>% Prob.
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Empty State -->
                        <?php if(empty($deals)): ?>
                            <div style="text-align:center; padding:20px; color:#cbd5e1; font-style:italic; font-size:0.9rem;">
                                No deals
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Total Sum Footer -->
                    <div style="padding:10px 15px; text-align:right; font-size:0.85rem; color:#64748b; border-top:1px solid #e2e8f0;">
                        Total: <strong>KES <?php echo number_format(array_sum(array_column($deals, 'amount'))); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<!-- View Deal Modal -->
<div id="viewDealModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-card); width:600px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
        <button type="button" onclick="document.getElementById('viewDealModal').style.display='none'" style="position:absolute; top:20px; right:20px; background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        
        <div style="margin-bottom:20px;">
            <div id="viewDealStage" style="display:inline-block; padding:4px 12px; border-radius:12px; font-size:0.8rem; font-weight:700; text-transform:uppercase; margin-bottom:10px; background:var(--bg-body); color:var(--text-main);">STAGE</div>
            <h2 id="viewDealTitle" style="font-size:1.5rem; font-weight:700; color:var(--text-main); margin-bottom:5px;">Deal Title</h2>
            <div style="font-size:1.1rem; color:var(--text-muted);"><i class="fa-regular fa-building"></i> <span id="viewDealClient">Client Name</span></div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:25px; background:var(--bg-body); padding:20px; border-radius:12px;">
            <div>
                <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Value</div>
                <div style="font-size:1.4rem; font-weight:700; color:var(--brand-blue);">KES <span id="viewDealAmount">0</span></div>
            </div>
             <div>
                <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Probability</div>
                <div style="font-size:1.4rem; font-weight:700; color:var(--text-main);"><span id="viewDealProb">0</span>%</div>
            </div>
             <div>
                <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Expected Close</div>
                <div style="font-size:1.1rem; font-weight:600; color:var(--text-main);" id="viewDealDate">TBD</div>
            </div>
        </div>

        <div style="margin-bottom:25px;">
             <div style="font-size:0.9rem; font-weight:700; color:var(--text-main); margin-bottom:8px;">Description / Notes</div>
             <div id="viewDealDesc" style="font-size:0.95rem; line-height:1.6; color:var(--text-main); background:var(--bg-body); padding:15px; border:1px solid var(--border-color); border-radius:8px; min-height:80px;">
                 No description provided.
             </div>
        </div>

        <div style="text-align:right; display:flex; justify-content:flex-end; gap:10px;">
             <!-- Process Flow Action -->
             <a id="btnCreateOrder" href="#" style="display:none; padding:10px 20px; text-decoration:none; background:#0f172a; color:white; border-radius:8px; font-weight:600; align-items:center; gap:8px;">
                 <i class="fa-solid fa-file-circle-plus"></i> <span id="btnCreateOrderText">Create Order</span>
             </a>
             <button type="button" onclick="window.openEditDealModal()" style="padding:10px 25px; border:none; background:var(--bg-body); color:var(--text-main); border: 1px solid var(--border-color); border-radius:8px; cursor:pointer; font-weight:600;">Edit</button>
             <button type="button" onclick="document.getElementById('viewDealModal').style.display='none'" style="padding:10px 25px; border:none; background:var(--bg-column); border-radius:8px; cursor:pointer; font-weight:600; color:var(--text-muted);">Close</button>
        </div>
    </div>
</div>

<!-- Add Deal Modal (kept as is) -->
<div id="addDealModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
  <!-- ... (rest of add form) ... -->
    <div style="background:var(--bg-card); width:500px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.2rem; font-weight:700;">New Deal</h3>
            <button type="button" onclick="window.closeDealModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="add_deal" value="1">
            
            <div class="form-group">
                <label class="form-label">Deal Name / Title</label>
                <input type="text" name="deal_name" class="form-input" placeholder="e.g. Annual Audit Contract" required>
            </div>

            <div class="form-group">
                <label class="form-label">Client</label>
                <select name="client_id" class="form-select" required>
                    <option value="">Select...</option>
                    <?php foreach($clients_list as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                 <div style="flex:1;">
                    <label class="form-label">Amount (KES)</label>
                    <input type="number" name="amount" class="form-input" placeholder="0.00" required step="0.01">
                </div>
                <div style="flex:1;">
                     <label class="form-label">Stage</label>
                     <select name="stage" class="form-select">
                        <option value="Lead">Lead</option>
                        <option value="Negotiation">Negotiation</option>
                        <option value="Proposal">Proposal</option>
                        <option value="Closed Won">Closed Won</option>
                        <option value="Closed Lost">Closed Lost</option>
                     </select>
                </div>
            </div>

             <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Expected Close Date</label>
                <input type="date" name="expected_close_date" class="form-input">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="window.closeDealModal()" style="padding:10px 20px; border:none; background:#f1f5f9; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 20px; border:none; background:#16a34a; color:white; border-radius:8px; font-weight:600; cursor:pointer;">Create Deal</button>
            </div>
        </form>
    </div>
        </form>
    </div>
</div>

<!-- Edit Deal Modal -->
<div id="editDealModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-card); width:500px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.2rem; font-weight:700;">Edit Deal</h3>
            <button type="button" onclick="document.getElementById('editDealModal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="update_deal" value="1">
            <input type="hidden" name="deal_id" id="editDealId">
            
            <div class="form-group">
                <label class="form-label">Deal Name / Title</label>
                <input type="text" name="deal_name" id="editDealName" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Client</label>
                <select name="client_id" id="editDealClient" class="form-select" required>
                    <?php foreach($clients_list as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                 <div style="flex:1;">
                    <label class="form-label">Amount (KES)</label>
                    <input type="number" name="amount" id="editDealAmount" class="form-input" required step="0.01">
                </div>
                <div style="flex:1;">
                     <label class="form-label">Stage</label>
                     <select name="stage" id="editDealStage" class="form-select">
                        <option value="Lead">Lead</option>
                        <option value="Negotiation">Negotiation</option>
                        <option value="Proposal">Proposal</option>
                        <option value="Closed Won">Closed Won</option>
                        <option value="Closed Lost">Closed Lost</option>
                     </select>
                </div>
            </div>

             <div class="form-group">
                <label class="form-label">Probability (%)</label>
                <input type="number" name="probability" id="editDealProb" class="form-input" min="0" max="100">
            </div>

             <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="editDealDesc" class="form-input" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Expected Close Date</label>
                <input type="date" name="expected_close_date" id="editDealDate" class="form-input">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="document.getElementById('editDealModal').style.display='none'" style="padding:10px 20px; border:none; background:#f1f5f9; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 20px; border:none; background:#3b82f6; color:white; border-radius:8px; font-weight:600; cursor:pointer;">Update Deal</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Store current deal data
    let currentDeal = null;

    window.openDealModal = function() {
        const modal = document.getElementById('addDealModal');
        if(modal) modal.style.display = 'flex';
    };

    window.closeDealModal = function() {
        const modal = document.getElementById('addDealModal');
        if(modal) modal.style.display = 'none';
    };

    window.viewDeal = function(element) {
        // Store Data
        currentDeal = {
            id: element.getAttribute('data-id'),
            title: element.getAttribute('data-title'),
            clientId: element.getAttribute('data-client-id'),
            amount: element.getAttribute('data-amount').replace(/,/g, ''), // Remove commas
            stage: element.getAttribute('data-stage'),
            prob: element.getAttribute('data-prob'),
            desc: element.getAttribute('data-desc'),
            dateRaw: element.getAttribute('data-date-raw') // Need raw date for input
        };

        // Populate Data
        document.getElementById('viewDealTitle').innerText = element.getAttribute('data-title');
        document.getElementById('viewDealClient').innerText = element.getAttribute('data-client');
        document.getElementById('viewDealAmount').innerText = element.getAttribute('data-amount');
        document.getElementById('viewDealStage').innerText = element.getAttribute('data-stage');
        document.getElementById('viewDealDesc').innerText = element.getAttribute('data-desc') || "No description provided.";
        document.getElementById('viewDealDate').innerText = element.getAttribute('data-date');
        document.getElementById('viewDealProb').innerText = element.getAttribute('data-prob');

        // Stage Colors
        const stage = element.getAttribute('data-stage');
        const stageBadge = document.getElementById('viewDealStage');
        let color = '#cbd5e1'; 
        if(stage === 'Closed Won') color = '#22c55e';
        else if(stage === 'Closed Lost') color = '#ef4444';
        else if(stage === 'Negotiation') color = '#f59e0b';
        else if(stage === 'Proposal') color = '#3b82f6';
        
        stageBadge.style.backgroundColor = color + '20'; // 20% opacity
        stageBadge.style.color = color;

        // Process Flow Button Logic
        const btnOrder = document.getElementById('btnCreateOrder');
        if(stage === 'Closed Won') {
            btnOrder.style.display = 'flex';
            // Link to Inspections Page with Pre-filled Data
            // We need client_id (not just name) and deal_id
            const dealId = element.getAttribute('data-id');
            const clientId = element.getAttribute('data-client-id'); 
            btnOrder.href = `index.php?page=inspections&open_add=1&deal_id=${dealId}&client_id=${clientId}`;
        } else {
            btnOrder.style.display = 'none';
        }

        // Show Modal
        document.getElementById('viewDealModal').style.display = 'flex';
    };

    window.openEditDealModal = function() {
        if(!currentDeal) return;
        document.getElementById('viewDealModal').style.display = 'none';

        document.getElementById('editDealId').value = currentDeal.id;
        document.getElementById('editDealName').value = currentDeal.title;
        document.getElementById('editDealClient').value = currentDeal.clientId; // Make sure client ID is available
        document.getElementById('editDealAmount').value = currentDeal.amount;
        document.getElementById('editDealStage').value = currentDeal.stage;
        document.getElementById('editDealProb').value = currentDeal.prob;
        document.getElementById('editDealDesc').value = currentDeal.desc;
        // Date parsing if needed, but assuming data-date-raw is YYYY-MM-DD
        document.getElementById('editDealDate').value = currentDeal.dateRaw || '';

        document.getElementById('editDealModal').style.display = 'flex';
    }

    window.onclick = function(event) {
        var modal = document.getElementById('addDealModal');
        var viewModal = document.getElementById('viewDealModal');
        var editModal = document.getElementById('editDealModal');
        if (event.target == modal) window.closeDealModal();
        if (event.target == viewModal) viewModal.style.display = 'none';
        if (event.target == editModal) editModal.style.display = 'none';
    }

    // Auto-fade alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-success, .alert-error');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500); 
            }, 2000);
        });
    });
</script>

<?php
// Auto-open modal if view_id is set
if(isset($_GET['view_id'])) {
    $targetId = $_GET['view_id'];
    $targetData = null;
    foreach($all_deals as $deal) {
        if($deal['id'] == $targetId) {
            $targetData = $deal;
            break;
        }
    }
    if($targetData) {
        $dealName = htmlspecialchars($targetData['deal_name']);
        $clientCompany = htmlspecialchars($targetData['client_company']);
        $amount = number_format($targetData['amount']);
        $prob = $targetData['probability'] ?? 0;
        $desc = htmlspecialchars($targetData['description'] ?? '');
        $date = $targetData['expected_close_date'] ? date('M d, Y', strtotime($targetData['expected_close_date'])) : 'TBD';
        $stage = $targetData['stage'];

        // Construct a safe dummy object for JS
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Build temporary element to pass to viewDeal
            const dummyEl = document.createElement('div');
            dummyEl.setAttribute('data-id', '$targetId');
            dummyEl.setAttribute('data-title', '$dealName');
            dummyEl.setAttribute('data-client', '$clientCompany');
            dummyEl.setAttribute('data-amount', '$amount');
            dummyEl.setAttribute('data-stage', '$stage');
            dummyEl.setAttribute('data-prob', '$prob');
            // Description might have newlines, so we need careful handling or just use JS object
            dummyEl.setAttribute('data-desc', `" . json_encode($desc) . "`.slice(1, -1)); 
            dummyEl.setAttribute('data-date', '$date');
            
            window.viewDeal(dummyEl);
        });
        </script>";
    }
}
?>
