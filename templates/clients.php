<?php
// Handle Actions (Create, Update, Delete)
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // DELETE
        if (isset($_POST['delete_client'])) {
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$_POST['client_id']]);
            $success_msg = "Client deleted successfully.";
        }
        // UPDATE
        elseif (isset($_POST['update_client'])) {
            $stmt = $pdo->prepare("UPDATE clients SET name=?, company=?, email=?, sector=?, revenue=?, status=? WHERE id=?");
            $stmt->execute([
                $_POST['name'],
                $_POST['company'],
                $_POST['email'],
                $_POST['sector'],
                $_POST['revenue'],
                $_POST['status'] ?? 'Pending',
                $_POST['client_id']
            ]);
            $success_msg = "Client updated successfully.";
        }
        // CREATE
        elseif (isset($_POST['add_client'])) {
            $stmt = $pdo->prepare("INSERT INTO clients (name, company, email, sector, revenue, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['company'],
                $_POST['email'],
                $_POST['sector'],
                $_POST['revenue'],
                'Pending'
            ]);
            $success_msg = "Client added successfully!";
        }
    } catch (PDOException $e) {
        $error_msg = "Operation failed: " . $e->getMessage();
    }
}

// Fetch Clients (with simple search)
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

$query = "SELECT * FROM clients WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR company LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter && $filter !== 'All') {
    $query .= " AND status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>

<div class="main-content">
    <div class="top-header">
        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <form id="searchForm" style="width:100%;">
                <input type="hidden" name="page" value="clients">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search clients..." onblur="this.form.submit()">
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
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert-error" style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <div>
                <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Client Directory</h1>
                <p style="color:var(--text-muted);">Manage global relationships and contact details.</p>
            </div>
            <div style="display:flex; gap:10px;">
                 <form method="GET" style="display:flex;">
                     <input type="hidden" name="page" value="clients">
                     <select name="filter" onchange="this.form.submit()" class="form-select" style="width:auto; cursor:pointer;">
                         <option value="All">All Status</option>
                         <option value="Active" <?php if($filter=='Active') echo 'selected'; ?>>Active</option>
                         <option value="Pending" <?php if($filter=='Pending') echo 'selected'; ?>>Pending</option>
                         <option value="Inactive" <?php if($filter=='Inactive') echo 'selected'; ?>>Inactive</option>
                     </select>
                 </form>
                 <button onclick="openModal('add')" style="background:#22c55e; color:white; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 6px -1px rgba(22, 163, 74, 0.2);">
                     <i class="fa-solid fa-plus"></i> Add New Client
                 </button>
            </div>
        </div>

        <div class="content-card" style="padding:0; overflow:hidden; min-height: 400px; background:var(--bg-card); border:1px solid var(--border-color);">
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:var(--bg-column-header); border-bottom:1px solid var(--border-color);">
                    <tr>
                        <th style="text-align:left; padding:15px 25px; font-size:0.85rem; color:var(--text-muted); font-weight:600;">CLIENT NAME</th>
                        <th style="text-align:left; padding:15px 25px; font-size:0.85rem; color:var(--text-muted); font-weight:600;">SECTOR</th>
                        <th style="text-align:left; padding:15px 25px; font-size:0.85rem; color:var(--text-muted); font-weight:600;">STATUS</th>
                        <th style="text-align:left; padding:15px 25px; font-size:0.85rem; color:var(--text-muted); font-weight:600;">LIFETIME VALUE</th>
                        <th style="text-align:left; padding:15px 25px; font-size:0.85rem; color:var(--text-muted); font-weight:600;">LAST CONTACT</th>
                        <th style="text-align:left; padding:15px 25px; font-size:0.85rem; color:var(--text-muted); font-weight:600;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($clients) > 0): ?>
                        <?php foreach ($clients as $client): ?>
                        <tr class="data-table-row" style="border-bottom:1px solid var(--border-color); transition:background 0.2s;">
                            <td style="padding:15px 25px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:40px; height:40px; background:var(--bg-column); color:var(--brand-blue); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:0.9rem;">
                                        <?php echo strtoupper(substr($client['name'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600; color:var(--text-main);"><?php echo htmlspecialchars($client['name']); ?></div>
                                        <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($client['company']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:15px 25px;">
                                <div style="display:flex; align-items:center; gap:6px; font-size:0.9rem; color:var(--text-muted);">
                                    <i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($client['sector']); ?>
                                </div>
                            </td>
                            <td style="padding:15px 25px;">
                                <?php 
                                $statusClass = match($client['status']) {
                                    'Active' => 'status-active',
                                    'Pending' => 'status-pending',
                                    'Inactive' => 'status-inactive',
                                    default => 'status-inactive'
                                };
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($client['status']); ?>
                                </span>
                            </td>
                            <td style="padding:15px 25px; font-weight:500; color:var(--text-muted);">
                                ~ KES <?php echo number_format($client['revenue']); ?>
                            </td>
                             <td style="padding:15px 25px; color:var(--text-muted); font-size:0.9rem;">
                                <?php echo $client['last_contact']; ?>
                            </td>
                            <td style="padding:15px 25px; position:relative;">
                                 <button onclick="toggleDropdown(<?php echo $client['id']; ?>)" style="border:none; background:transparent; color:var(--text-muted); cursor:pointer; padding:5px;"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                 
                                 <!-- Dropdown Menu -->
                                 <div id="dropdown-<?php echo $client['id']; ?>" class="action-dropdown" style="display:none; position:absolute; right:20px; top:40px; background:var(--bg-card); border:1px solid var(--border-color); border-radius:8px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); z-index:50; min-width:140px;">
                                     <a href="#" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($client)); ?>)" style="display:block; padding:10px 15px; text-decoration:none; color:var(--text-main); font-size:0.9rem; transition:background 0.2s;">
                                        <i class="fa-solid fa-pen" style="margin-right:8px; color:var(--brand-blue);"></i> Edit
                                     </a>
                                     <form method="POST" onsubmit="return confirm('Are you sure you want to delete this client?');" style="margin:0;">
                                         <input type="hidden" name="delete_client" value="1">
                                         <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                         <button type="submit" style="width:100%; text-align:left; background:none; border:none; padding:10px 15px; cursor:pointer; color:#ef4444; font-size:0.9rem; display:flex; align-items:center;">
                                            <i class="fa-solid fa-trash" style="margin-right:8px;"></i> Delete
                                         </button>
                                     </form>
                                 </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="padding:30px; text-align:center; color:#64748b;">No clients found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.data-table-row:hover {
    background-color: var(--bg-column-header) !important;
}
.action-dropdown a:hover, .action-dropdown button:hover {
    background-color: var(--bg-column);
}
</style>

<!-- Modal -->
<div id="clientModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(2px);">
    <div style="background:var(--bg-card); width:500px; padding:30px; border-radius:16px; position:relative; animation: slideUp 0.3s ease-out; border:1px solid var(--border-color); box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 id="modalTitle" style="font-size:1.2rem; font-weight:700; color:var(--text-main);">Add New Client</h3>
            <button onclick="closeModal()" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <form method="POST" id="clientForm">
            <input type="hidden" name="add_client" id="formActionInput" value="1"> <!-- Default to Add -->
            <input type="hidden" name="client_id" id="editClientId"> <!-- Only for Edit -->
            
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" id="inputName" class="form-input" placeholder="Contact Person" required>
                </div>
                 <div style="flex:1;">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" id="inputCompany" class="form-input" placeholder="Company Name" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="inputEmail" class="form-input" placeholder="client@company.com" required>
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                 <div style="flex:1;">
                    <label class="form-label">Sector</label>
                    <select name="sector" id="inputSector" class="form-select" required>
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
                 <div style="flex:1;">
                    <label class="form-label">Initial Revenue</label>
                    <input type="number" name="revenue" id="inputRevenue" class="form-input" placeholder="0" required>
                </div>
            </div>

            <div class="form-group" id="statusGroup" style="display:none;">
                <label class="form-label">Status</label>
                <select name="status" id="inputStatus" class="form-select">
                    <option value="Active">Active</option>
                    <option value="Pending">Pending</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="closeModal()" style="padding:10px 20px; border:none; background:#f1f5f9; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" id="submitBtn" style="padding:10px 20px; border:none; background:#22c55e; color:white; border-radius:8px; font-weight:600; cursor:pointer;">Create Client</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleDropdown(id) {
        // Close all others
        document.querySelectorAll('.action-dropdown').forEach(el => {
            if (el.id !== 'dropdown-' + id) el.style.display = 'none';
        });
        
        const dropdown = document.getElementById('dropdown-' + id);
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    // Close dropdowns when clicking outside
    window.onclick = function(event) {
        if (!event.target.closest('td')) {
            document.querySelectorAll('.action-dropdown').forEach(el => el.style.display = 'none');
        }
        if (event.target == document.getElementById('clientModal')) {
            closeModal();
        }
    }

    function openModal(mode, data = null) {
        const modal = document.getElementById('clientModal');
        const title = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');
        const actionInput = document.getElementById('formActionInput');
        const idInput = document.getElementById('editClientId');
        const statusGroup = document.getElementById('statusGroup');

        modal.style.display = 'flex';

        if (mode === 'edit' && data) {
            title.textContent = 'Edit Client';
            submitBtn.textContent = 'Update Client';
            submitBtn.style.background = '#3b82f6'; // Blue for update
            actionInput.name = 'update_client'; // Change action
            idInput.value = data.id;
            statusGroup.style.display = 'block'; // Show status on edit

            // Populate fields
            document.getElementById('inputName').value = data.name;
            document.getElementById('inputCompany').value = data.company;
            document.getElementById('inputEmail').value = data.email;
            document.getElementById('inputSector').value = data.sector;
            document.getElementById('inputRevenue').value = parseFloat(data.revenue);
            document.getElementById('inputStatus').value = data.status;
        } else {
            title.textContent = 'Add New Client';
            submitBtn.textContent = 'Create Client';
            submitBtn.style.background = '#22c55e'; // Green for add
            actionInput.name = 'add_client';
            idInput.value = '';
            statusGroup.style.display = 'none'; // Hide status on add (default Pending)
            
            // Clear fields
            document.getElementById('clientForm').reset();
        }
    }

    function closeModal() {
        document.getElementById('clientModal').style.display = 'none';
    }

    // Auto-fade alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-success, .alert-error');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500); // Remove from DOM after fade
            }, 2000);
        });
    });
</script>

<?php
// Auto-open modal if view_id is set
if(isset($_GET['view_id'])) {
    $targetId = $_GET['view_id'];
    $targetData = null;
    foreach($clients as $c) {
        if($c['id'] == $targetId) {
            $targetData = $c;
            break;
        }
    }
    
    if ($targetData) {
        $json = json_encode($targetData);
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Reusing the Edit Modal logic but just to view for now, 
            // or we could create a specific view modal. 
            // Given the existing code, opening 'edit' mode is the closest detailed view.
            openModal('edit', $json);
        });
        </script>";
    }
}
?>
