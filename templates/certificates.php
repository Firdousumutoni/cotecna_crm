<?php
// Handle Create Certificate
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_cert'])) {
    try {
        $insp_id = $_POST['inspection_id'];
        
        // Check if exists
        $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE inspection_id = ?");
        $stmtChk->execute([$insp_id]);
        if ($stmtChk->fetchColumn() > 0) {
            $error_msg = "Certificate already exists for this inspection.";
        } else {
            // Generate Num
            $year = date('Y');
            $stmtNum = $pdo->query("SELECT MAX(id) FROM certificates");
            $next_id = $stmtNum->fetchColumn() + 1;
            $cert_num = "CER-$year-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO certificates (inspection_id, certificate_number, issue_date, expiry_date, status) VALUES (?, ?, ?, ?, 'Active')");
            $stmt->execute([
                $insp_id,
                $cert_num,
                date('Y-m-d'),
                date('Y-m-d', strtotime('+1 year'))
            ]);
            
            // Update Inspection Status to 'certified'
            $stmtUpdate = $pdo->prepare("UPDATE inspections SET status = 'certified' WHERE id = ?");
            $stmtUpdate->execute([$insp_id]);

            $success_msg = "Certificate generated successfully!";
        }
    } catch (PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// Fetch Certificates
$stmt = $pdo->query("SELECT cert.*, i.product_name, c.company as client_company, i.category 
                     FROM certificates cert 
                     JOIN inspections i ON cert.inspection_id = i.id 
                     JOIN clients c ON i.client_id = c.id 
                     ORDER BY cert.created_at DESC");
$certificates = $stmt->fetchAll();

// Fetch Certified Inspections for Dropdown (that don't have certs)
$stmtInsp = $pdo->query("SELECT i.id, i.product_name, c.company 
                         FROM inspections i 
                         JOIN clients c ON i.client_id = c.id 
                         WHERE (i.status = 'certified' OR i.status = 'completed') 
                         AND i.id NOT IN (SELECT inspection_id FROM certificates)
                         ORDER BY i.inspection_date DESC");
$certified_inspections = $stmtInsp->fetchAll();
?>

<style>
    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #viewCertModal, #viewCertModal * {
            visibility: visible;
        }
        #viewCertModal {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: white !important;
            padding: 0;
            margin: 0;
            display: flex !important;
            align-items: center;
            justify-content: center;
            overflow: visible !important;
        }
        .no-print {
            display: none !important;
        }
        .cert-container-wrapper {
            width: 210mm;
            height: 297mm;
            padding: 0; /* Remove padding for print to let inner frame handle it */
            margin: 0;
            background: white !important;
            box-shadow: none !important;
            transform: scale(1) !important; /* Ensure scale is 1 for print */
        }
        .cert-frame {
            border: none !important; /* Optional: remove outer border on print if needed, but usually looks good */
        }
    }

    /* Screen Styles for Modal */
    #viewCertModal {
        backdrop-filter: blur(5px);
    }
    
    .cert-font {
        font-family: 'Times New Roman', Times, serif;
    }
    
    .cert-container-wrapper {
        width: 210mm;
        /* height: 297mm;  Let height be auto slightly to fit screens better if needed, but fixed layout is safer for certs */
        min-height: 297mm; 
        padding: 15mm;
        box-sizing: border-box;
        background-color: #ffffff; /* Explicit white */
        background: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.3); /* Add shadow for depth on screen */
        margin: 50px auto; /* Center with margin */
    }

    .cert-frame {
        border: 1px solid #94a3b8; /* Thinner sophisticated gray */
        padding: 8px; /* Gap between borders */
        height: 100%;
        box-sizing: border-box;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .cert-inner-frame {
        border: 5px solid #1e3a8a; /* Cotecna Blue */
        height: 100%;
        padding: 40px 50px;
        box-sizing: border-box;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: white; /* Ensure inner is white too */
    }
    
    /* Watermark effect */
    .cert-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.04; /* Very subtle */
        pointer-events: none;
        z-index: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cert-watermark i {
        font-size: 500px;
        color: #1e3a8a;
    }
    
    .cert-content-layer {
        position: relative;
        z-index: 1;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .cert-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 3rem;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 20px;
    }
    .cotecna-brand {
        font-family: 'Arial', sans-serif;
        color: #1e3a8a;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    
    .cert-title {
        text-align: center;
        font-size: 2.8rem;
        letter-spacing: 3px;
        color: #334155;
        margin-bottom: 2rem;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .cert-body {
        flex-grow: 1;
        text-align: center;
        font-size: 1.1rem;
        color: #334155;
        line-height: 1.8;
    }
    
    .cert-body strong {
        display: block;
        margin: 8px 0;
        color: #0f172a;
        font-size: 1.5rem;
    }
    
    .data-label {
        font-size: 0.95rem;
        color: #64748b;
        margin-top: 1.5rem;
        font-style: italic;
    }
    
    .cert-footer {
        margin-top: auto;
        padding-top: 30px;
    }
    .cert-footer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: end;
    }
    .signature-box {
        text-align: center;
    }
    .signature-line {
        border-top: 1px solid #334155;
        width: 220px;
        margin: 10px auto 0 auto;
    }
</style>

<div class="main-content">
    <div class="top-header no-print">
        <div class="search-bar">
             <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Search certificates..." style="width:100%; border:none; outline:none; font-size:0.95rem; color:var(--text-main); background:transparent;">
        </div>
        <div class="user-profile">
            <?php include __DIR__ . '/notification_dropdown.php'; ?>
            <?php include __DIR__ . '/header_profile_link.php'; ?>
        </div>
    </div>

    <div class="content-scroll">
        <?php if ($success_msg): ?>
            <div class="alert-success no-print" style="background:#dcfce7; color:#16a34a; padding:15px; border-radius:8px; margin-bottom:20px;">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert-error no-print" style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <script>
        // Auto-fade alerts after 2 seconds
        setTimeout(function() {
            var successAlert = document.querySelector('.alert-success');
            var errorAlert = document.querySelector('.alert-error');
            
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(function() { successAlert.style.display = 'none'; }, 500);
            }
            
            if (errorAlert) {
                errorAlert.style.transition = 'opacity 0.5s ease';
                errorAlert.style.opacity = '0';
                setTimeout(function() { errorAlert.style.display = 'none'; }, 500);
            }
        }, 2000);
        </script>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;" class="no-print">
            <div>
                <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main);">Certificates</h1>
                <p style="color:var(--text-muted);">Manage and issue compliance certificates.</p>
            </div>
            <button onclick="document.getElementById('newCertModal').style.display='flex'" style="background:#0891b2; color:white; border:none; padding:12px 25px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:10px; box-shadow:0 4px 6px -1px rgba(8, 145, 178, 0.2);">
                <i class="fa-solid fa-certificate"></i> Generate Certificate
            </button>
        </div>

        <div class="content-card no-print" style="padding:0; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <tr>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; color:#64748b;">CERT NO.</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; color:#64748b;">CLIENT</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; color:#64748b;">PRODUCT</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; color:#64748b;">ISSUE DATE</th>
                        <th style="text-align:left; padding:15px 20px; font-size:0.8rem; color:#64748b;">STATUS</th>
                        <th style="text-align:right; padding:15px 20px; font-size:0.8rem; color:#64748b;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($certificates as $c): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:15px 20px; font-family:monospace; font-weight:600; color:#334155;"><?php echo $c['certificate_number']; ?></td>
                        <td style="padding:15px 20px; font-weight:600;"><?php echo htmlspecialchars($c['client_company']); ?></td>
                        <td style="padding:15px 20px;"><?php echo htmlspecialchars($c['product_name']); ?></td>
                        <td style="padding:15px 20px;"><?php echo $c['issue_date']; ?></td>
                        <td style="padding:15px 20px;">
                            <span style="background:#dcfce7; color:#16a34a; padding:4px 10px; border-radius:12px; font-size:0.75rem; font-weight:700;">Active</span>
                        </td>
                        <td style="padding:15px 20px; text-align:right;">
                            <button onclick="viewCert(<?php echo htmlspecialchars(json_encode($c)); ?>)" style="background:none; border:none; color:#0891b2; font-weight:600; cursor:pointer;">
                                <i class="fa-solid fa-eye"></i> View / Print
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($certificates)): ?>
                        <tr><td colspan="6" style="padding:30px; text-align:center; color:#94a3b8;">No certificates issued yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Cert Modal -->
<div id="newCertModal" class="no-print" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:999999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
    <div style="background:white; width:500px; padding:30px; border-radius:16px; position:relative; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">
         <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:1.2rem; font-weight:700; color: #1e293b;">Generate Certificate</h3>
            <button onclick="document.getElementById('newCertModal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color: #64748b;">&times;</button>
        </div>
        
        <?php if(empty($certified_inspections)): ?>
            <div style="padding:20px; text-align:center; background:#f8fafc; border-radius:8px; color:#64748b;">
                <i class="fa-solid fa-circle-info"></i> No pending certified inspections found.
            </div>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="generate_cert" value="1">
            <div class="form-group">
                <label class="form-label" style="display:block; margin-bottom:8px; color:#475569; font-weight:600;">Select Certified Inspection</label>
                <select name="inspection_id" class="form-select" required style="width:100%; padding:10px; border-radius:6px; border:1px solid #cbd5e1; outline:none;">
                    <?php foreach($certified_inspections as $ci): ?>
                        <option value="<?php echo $ci['id']; ?>">
                            <?php echo htmlspecialchars($ci['company'] . ' - ' . $ci['product_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p style="font-size:0.8rem; color:#64748b; margin-top:5px;">Only "Certified" inspections appear here.</p>
            </div>
            <div style="text-align:right; margin-top:20px;">
                <button type="submit" style="padding:10px 25px; background:#0891b2; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Generate</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- View Certificate Modal (Updated Design) -->
<div id="viewCertModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.85); z-index:999999; align-items:start; justify-content:center; overflow-y:auto; padding-top:40px; padding-bottom:40px;">
    <!-- Wrapper for print sizing -->
    <div class="cert-container-wrapper" style="position:relative;">
        <button onclick="document.getElementById('viewCertModal').style.display='none'" class="no-print" style="position:absolute; top:20px; right:-60px; background:transparent; border:none; font-size:2.5rem; cursor:pointer; color:white; font-weight:bold; display:flex; align-items:center; justify-content:center;">&times;</button>

        <div class="cert-font cert-frame">
            <div class="cert-inner-frame">
                <!-- Watermark -->
                <div class="cert-watermark">
                     <i class="fa-solid fa-globe"></i>
                </div>

                <div class="cert-content-layer">
                    <div class="cert-header">
                        <div class="cotecna-brand">COTECNA</div>
                        <!-- Placeholder for QR or ID -->
                        <div style="border:1px solid #cbd5e1; width:70px; height:70px; display:flex; align-items:center; justify-content:center; border-radius:4px; background:#f8fafc;">
                             <i class="fa-solid fa-qrcode" style="font-size:35px; color:#1e293b;"></i>
                        </div>
                    </div>

                    <div class="cert-title">CERTIFICATE OF CONFORMITY</div>

                    <div class="cert-body">
                        <div class="data-label">Cotecna Kenya Limited, Certified that</div>
                        
                        <strong id="certClient" style="text-transform:uppercase;">Client Name</strong>
                        
                        <div class="data-label">Site Address</div>
                        <div style="font-size:1.1rem; color:#1e293b;">P.O BOX 47744-00100, NAIROBI, KENYA</div>
                        
                        <div class="data-label">Has been ascertained to comply with the requirements of</div>
                        
                        <strong style="font-family:'Arial', sans-serif;">GSO - 993:2015</strong>
                        
                        <div class="data-label">This certificate is applicable to:</div>
                        
                        <div style="font-size:1.4rem; color:#0f172a; margin:10px 0; font-weight:700;">
                            Shipment of <span id="certProduct" style="text-transform:uppercase;">Product Name</span>
                        </div>

                        <div style="margin-top:20px; font-weight:bold; font-size:1.1rem;">
                            Category: <span id="certCategory">General</span>
                        </div>
                        
                        <div style="margin-top:30px; font-family:monospace; font-weight:700; font-size:1.3rem; border:2px dashed #94a3b8; display:inline-block; padding:10px 20px; border-radius:8px;">
                            CERTIFICATE NO: <span id="certNum">KE/2407/103</span>
                        </div>
                        
                    </div>

                    <div class="cert-footer">
                        <div class="cert-footer-grid">
                            <div style="text-align:left; display:flex; align-items:center; gap:15px; min-height:80px;">
                                <!-- Conditional Halal Logo -->
                                <div id="halalLogo" style="display:none; border:2px solid #16a34a; color:#16a34a; padding:5px; border-radius:50%; width:70px; height:70px; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; text-align:center; flex-shrink:0;">
                                    HALAL<br><i class="fa-solid fa-leaf"></i>
                                </div>
                                <div style="font-size:0.8rem; color:#64748b; line-height:1.4;">
                                    This certificate is valid from<br>
                                    <strong style="color:#334155; font-size:1rem;" id="certDateSpan">Date</strong> to <strong style="color:#334155; font-size:1rem;" id="certExpirySpan">Date</strong>
                                </div>
                            </div>
                            <div class="signature-box">
                                <!-- Signature Placeholder -->
                                <div id="signerName" style="font-family:'Brush Script MT', cursive; font-size:2rem; color:#1e293b; margin-bottom:5px;">Arisur Rehman</div>
                                <div class="signature-line"></div>
                                <div style="font-weight:700; font-size:0.9rem; margin-top:5px; text-transform:uppercase;">Authorized Signature</div>
                                <div style="font-size:0.8rem; color:#64748b;">Cotecna Kenya Limited</div>
                            </div>
                        </div>
                         <div style="font-size:0.8rem; color:#94a3b8; text-align:center; margin-top:20px; border-top:1px solid #e2e8f0; padding-top:15px; letter-spacing:3px; text-transform:uppercase;">
                            Trust for a Moving World
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="no-print" style="position:absolute; bottom:-60px; left:50%; transform:translateX(-50%); display:flex; gap:10px;">
            <button onclick="window.print()" style="padding:12px 30px; background:white; color:#1e293b; border:none; border-radius:30px; font-weight:600; cursor:pointer; box-shadow:0 4px 6px rgba(0,0,0,0.2); transition:all 0.2s;">
                <i class="fa-solid fa-print"></i> Print / Download PDF
            </button>
            <button onclick="document.getElementById('viewCertModal').style.display='none'" style="padding:12px 30px; background:#ef4444; color:white; border:none; border-radius:30px; font-weight:600; cursor:pointer; box-shadow:0 4px 6px rgba(0,0,0,0.2);">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function viewCert(data) {
    document.getElementById('certNum').innerText = data.certificate_number;
    document.getElementById('certClient').innerText = data.client_company;
    document.getElementById('certProduct').innerText = data.product_name;
    document.getElementById('certCategory').innerText = data.category || 'General';
    
    // Dates
    document.getElementById('certDateSpan').innerText = data.issue_date;
    var issueDate = new Date(data.issue_date);
    var expiryDate = new Date(issueDate.setFullYear(issueDate.getFullYear() + 1));
    document.getElementById('certExpirySpan').innerText = expiryDate.toISOString().split('T')[0];
    
    // Halal Logic
    var category = (data.category || '').toLowerCase();
    var product = (data.product_name || '').toLowerCase();
    var halalLogo = document.getElementById('halalLogo');
    
    if (category.includes('food') || category.includes('tea') || category.includes('meat') || product.includes('tea') || product.includes('food')) {
        halalLogo.style.display = 'flex';
    } else {
        halalLogo.style.display = 'none';
    }
    
    // Set Modal Flex
    var modal = document.getElementById('viewCertModal');
    modal.style.display = 'flex';
}
</script>
