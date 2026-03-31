<?php
// Handle Add Interaction
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_interaction'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO interactions (client_id, type, subject, notes, outcome, interaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['client_id'],
            $_POST['type'],
            $_POST['subject'],
            $_POST['notes'],
            $_POST['outcome'],
            $_POST['interaction_date'] ?: date('Y-m-d H:i:s')
        ]);
        $success_msg = "Interaction logged successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error logging interaction: " . $e->getMessage();
    }
}

// Fetch Clients for Dropdown
$stmt = $pdo->query("SELECT id, name, company FROM clients ORDER BY name ASC");
$clients_list = $stmt->fetchAll();

// Fetch Interactions (Search & Filter)
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'All';

$query = "SELECT i.*, c.name as client_name, c.company as client_company 
          FROM interactions i 
          JOIN clients c ON i.client_id = c.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (i.subject LIKE ? OR c.name LIKE ? OR c.company LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter !== 'All') {
    $query .= " AND i.type = ?";
    $params[] = $type_filter;
}

$query .= " ORDER BY i.interaction_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$interactions = $stmt->fetchAll();
?>

<div class="main-content">
    <div class="top-header">
        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <form id="searchForm" style="width:100%;">
                <input type="hidden" name="page" value="interactions">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs..." onblur="this.form.submit()">
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

        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px;">
            <div>
                <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Client Interactions</h1>
                <p style="color:var(--text-muted);">Centralized log of all calls, emails, and meetings.</p>
                
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <?php 
                    $tabs = ['All', 'Call', 'Email', 'Meeting', 'Note'];
                    foreach($tabs as $t): 
                        $activeClass = $type_filter === $t ? 'active' : '';
                    ?>
                    <a href="?page=interactions&type=<?php echo $t; ?>&search=<?php echo urlencode($search); ?>" 
                       class="tab-pill <?php echo $activeClass; ?>">
                       <?php echo $t; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="button" onclick="document.getElementById('addInteractionModal').style.display='flex'" style="background:#16a34a; color:white; border:none; padding:12px 25px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:10px; box-shadow:0 4px 6px -1px rgba(22, 163, 74, 0.2); position:relative; z-index:100;">
                <i class="fa-solid fa-plus"></i> Add Interaction
            </button>
        </div>

        <div style="display:flex; flex-direction:column; gap:15px;">
            <?php if (count($interactions) > 0): ?>
                <?php foreach ($interactions as $log): ?>
                    <div class="interaction-card">
                        
                        <!-- Icon -->
                        <div style="width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.2rem;
                            <?php 
                            echo match($log['type']) {
                                'Call' => 'background:#eff6ff; color:#3b82f6;', // Blue
                                'Email' => 'background:#fefce8; color:#ca8a04;', // Yellow
                                'Meeting' => 'background:#f0fdf4; color:#16a34a;', // Green
                                'Note' => 'background:#f8fafc; color:#64748b;', // Grey
                            };
                            ?>">
                            <i class="fa-solid <?php 
                                echo match($log['type']) {
                                    'Call' => 'fa-phone',
                                    'Email' => 'fa-envelope',
                                    'Meeting' => 'fa-users',
                                    'Note' => 'fa-note-sticky',
                                };
                            ?>"></i>
                        </div>

                        <!-- Content -->
                        <div style="flex:1;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                <div style="font-weight:700; color:var(--text-main); font-size:1.1rem;"><?php echo htmlspecialchars($log['client_company']); ?></div>
                                <div style="font-size:0.85rem; color:#94a3b8; display:flex; align-items:center; gap:5px;">
                                    <i class="fa-regular fa-calendar"></i> <?php echo date('Y-m-d H:i', strtotime($log['interaction_date'])); ?>
                                </div>
                            </div>
                            
                            <div style="font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin-bottom:8px;">
                                <?php echo strtoupper($log['type']); ?>
                            </div>

                            <div style="color:var(--text-main); margin-bottom:8px; font-weight:500;">
                                <?php echo htmlspecialchars($log['subject']); ?>
                            </div>
                            
                            <div style="color:var(--text-muted); font-size:0.95rem; line-height:1.5; font-style:italic; border-left:3px solid var(--border-color); padding-left:10px;">
                                "<?php echo htmlspecialchars($log['notes']); ?>"
                            </div>

                            <div style="margin-top:15px; display:flex; gap:10px; align-items:center;">
                                <span style="font-size:0.75rem; font-weight:700; color:#94a3b8;">OUTCOME / NEXT STEPS</span>
                                <?php 
                                $outcomeColor = match($log['outcome']) {
                                    'Satisfied' => 'color:#16a34a;',
                                    'Dissatisfied' => 'color:#ef4444;',
                                    'Neutral' => 'color:#3b82f6;',
                                    'Pending' => 'color:#d97706;',
                                };
                                ?>
                                <span style="font-size:0.8rem; font-weight:600; <?php echo $outcomeColor; ?>">
                                    <?php echo htmlspecialchars($log['outcome']); ?>
                                </span>
                            </div>
                        </div>

                         <div style="display:flex; flex-direction:column; align-items:flex-end;">
                            <a href="?page=clients&search=<?php echo urlencode($log['client_name']); ?>" style="font-size:0.8rem; color:#3b82f6; text-decoration:none; margin-bottom:10px;">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> View Client
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#94a3b8;">
                    <i class="fa-regular fa-folder-open" style="font-size:3rem; margin-bottom:10px;"></i>
                    <p>No interactions found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Interaction Modal -->
<!-- Moved outside main-content for Z-Index Safety -->
<div id="addInteractionModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-card); width:600px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); animation: zoomIn 0.2s ease-out; border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.2rem; font-weight:700;">Log New Interaction</h3>
            <button type="button" onclick="document.getElementById('addInteractionModal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="add_interaction" value="1">
            
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select" required>
                        <option value="">Select Client...</option>
                        <?php foreach($clients_list as $client): ?>
                            <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['company']); ?> (<?php echo htmlspecialchars($client['name']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div style="flex:1;">
                    <label class="form-label">Date</label>
                    <input type="datetime-local" name="interaction_date" class="form-input" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                 <div style="flex:1;">
                    <label class="form-label">Interaction Type</label>
                    <select name="type" class="form-select" required>
                        <option value="Call">Call</option>
                        <option value="Email">Email</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Note">Note</option>
                    </select>
                </div>
                <div style="flex:1;">
                     <label class="form-label">Outcome / Satisfaction</label>
                     <select name="outcome" class="form-select">
                        <option value="Pending">Pending</option>
                        <option value="Satisfied">Satisfied</option>
                        <option value="Neutral">Neutral</option>
                        <option value="Dissatisfied">Dissatisfied</option>
                     </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Purpose / Subject</label>
                <input type="text" name="subject" class="form-input" placeholder="e.g. Discuss Annual Renewal" required>
            </div>

             <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="4" placeholder="Detailed notes about the interaction..." style="height:auto; font-family:sans-serif;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="document.getElementById('addInteractionModal').style.display='none'" style="padding:10px 20px; border:none; background:#f1f5f9; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:10px 20px; border:none; background:#16a34a; color:white; border-radius:8px; font-weight:600; cursor:pointer;">Save Log</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Only Animation Keyframes if needed
    if (!document.getElementById('animStyles')) {
        const style = document.createElement('style');
        style.id = 'animStyles';
        style.innerHTML = `
            @keyframes zoomIn {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
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
    });
</script>

<!-- View Interaction Modal (Added for Context View) -->
<div id="viewInteractionModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-card); width:500px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid var(--border-color);">
         <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.2rem; font-weight:700;">Interaction Details</h3>
            <button type="button" onclick="document.getElementById('viewInteractionModal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>
        
        <div style="margin-bottom:15px;">
            <div style="font-size:0.8rem; font-weight:700; color:#94a3b8; text-transform:uppercase;">Subject</div>
            <div id="viewIntSubject" style="font-size:1.1rem; font-weight:600; color:var(--text-main);"></div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
             <div>
                <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Client</div>
                <div id="viewIntClient" style="font-size:0.95rem; color:var(--text-main);"></div>
            </div>
             <div>
                 <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Type</div>
                <div id="viewIntType" style="font-size:0.95rem; color:var(--text-main);"></div>
            </div>
        </div>

         <div style="margin-bottom:15px;">
            <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Notes</div>
            <div id="viewIntNotes" style="font-size:0.95rem; color:var(--text-main); background:var(--bg-body); padding:10px; border-radius:8px; line-height:1.5;"></div>
        </div>

         <div style="text-align:right;">
             <button onclick="document.getElementById('viewInteractionModal').style.display='none'" class="btn-primary" style="background:var(--bg-sidebar); color:white; border:none; padding:8px 20px; border-radius:8px; cursor:pointer;">Close</button>
         </div>
    </div>
</div>

<?php
// Auto-open modal if view_id is present
if(isset($_GET['view_id'])) {
    $targetId = $_GET['view_id'];
    $targetData = null;
    foreach($interactions as $log) {
        if($log['id'] == $targetId) {
            $targetData = $log;
            break;
        }
    }
    
    if ($targetData) {
        $cleanData = [
            'subject' => htmlspecialchars($targetData['subject']),
            'client' => htmlspecialchars($targetData['client_company']),
            'type' => htmlspecialchars($targetData['type']),
            'notes' => htmlspecialchars($targetData['notes']),
        ];
        $json = json_encode($cleanData);
        
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const data = $json;
            document.getElementById('viewIntSubject').innerText = data.subject;
            document.getElementById('viewIntClient').innerText = data.client;
            document.getElementById('viewIntType').innerText = data.type;
            document.getElementById('viewIntNotes').innerText = data.notes;
            
            document.getElementById('viewInteractionModal').style.display = 'flex';
        });
        </script>";
    }
}
?>
