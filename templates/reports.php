<?php
// --- 1. FILTER & EXPORT LOGIC ---

// Default Defaults
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');
$sector_filter = $_GET['sector'] ?? '';
$stage_filter = $_GET['stage'] ?? '';

// Build WHERE Clause
$where_sql = "d.expected_close_date BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($sector_filter) {
    $where_sql .= " AND c.sector = ?";
    $params[] = $sector_filter;
}
if ($stage_filter) {
    
    $where_sql .= " AND d.stage = ?";
    $params[] = $stage_filter;
}

// HANDLE EXPORT
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Clear any previous output (e.g. from header.php) to prevent HTML in CSV
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Deal', 'Client', 'Sector', 'Amount', 'Stage', 'Expected Close']);
    
    $sql = "SELECT d.deal_name, c.company, c.sector, d.amount, d.stage, d.expected_close_date 
            FROM deals d JOIN clients c ON d.client_id = c.id 
            WHERE $where_sql ORDER BY d.expected_close_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// --- 2. KPI QUERY EXECUTION ---

// Total Revenue (Closed Won only - independent of stage filter usually, but let's respect filters if set to 'Closed Won')
// Note: If user selects 'Lead' stage, Revenue should be 0 or 'Potential Revenue'. 
// For standard report, 'Revenue' usually implies Closed Won.
$rev_where = str_replace("d.stage = ?", "1=1", $where_sql); // Removing stage filter for Win Rate calculations context if strictly needed
// But actually, let's Stick to: "Revenue" = Sum of Won deals within the filtered constraints.
$sql_rev = "SELECT SUM(d.amount) FROM deals d JOIN clients c ON d.client_id = c.id WHERE $where_sql AND d.stage = 'Closed Won'";
$stmt = $pdo->prepare($sql_rev);
$stmt->execute($params);
$total_revenue = $stmt->fetchColumn() ?: 0;

// Pipeline Value (Open Deals)
$sql_pipe = "SELECT SUM(d.amount) FROM deals d JOIN clients c ON d.client_id = c.id WHERE $where_sql AND d.stage NOT IN ('Closed Won', 'Closed Lost')";
$stmt = $pdo->prepare($sql_pipe);
$stmt->execute($params);
$pipeline_value = $stmt->fetchColumn() ?: 0;

// Win Rate (Won / Total Closed) - Respects Sector/Date filters
$sql_win = "SELECT 
    SUM(CASE WHEN d.stage = 'Closed Won' THEN 1 ELSE 0 END) as won,
    SUM(CASE WHEN d.stage IN ('Closed Won', 'Closed Lost') THEN 1 ELSE 0 END) as closed
    FROM deals d JOIN clients c ON d.client_id = c.id WHERE $where_sql";
$stmt = $pdo->prepare($sql_win);
$stmt->execute($params);
$win_stats = $stmt->fetch();
$win_rate = ($win_stats['closed'] > 0) ? round(($win_stats['won'] / $win_stats['closed']) * 100) : 0;

// --- 3. CHARTS DATA ---

// Funnel Data (Group by Stage) - We want ALL counts even if filter is set, usually funnel is holistic.
// However, if filtering by Sector, we want to see that Sector's funnel.
// So we use $where_sql but REMOVE the stage filter if it exists.
$funnel_where = $where_sql;
$funnel_params = $params;
if($stage_filter) {
    // Remove the stage param and the SQL part
    $funnel_where = str_replace(" AND d.stage = ?", "", $where_sql);
    array_pop($funnel_params);
}

$sql_funnel = "SELECT d.stage, COUNT(*) as count FROM deals d JOIN clients c ON d.client_id = c.id WHERE $funnel_where GROUP BY d.stage";
$stmt = $pdo->prepare($sql_funnel);
$stmt->execute($funnel_params);
$funnel_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['Lead' => 5, ...]

// Ensure order
$stages_order = ['Lead', 'Negotiation', 'Proposal', 'Closed Won', 'Closed Lost'];
$funnel_data = [];
foreach($stages_order as $s) $funnel_data[] = $funnel_raw[$s] ?? 0;


// Revenue Trend (Same as before but filtered)
$sql_trend = "SELECT DATE_FORMAT(d.expected_close_date, '%b') as m, SUM(d.amount) as val 
              FROM deals d JOIN clients c ON d.client_id = c.id 
              WHERE $where_sql AND d.stage = 'Closed Won' 
              GROUP BY m ORDER BY DATE_FORMAT(d.expected_close_date, '%m')";
$stmt = $pdo->prepare($sql_trend);
$stmt->execute($params);
$trend_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$months = array_keys($trend_raw);
$revenues = array_values($trend_raw);


// --- 4. ROTTING DEALS (Stalled > 14 Days) ---
// Assuming created_at is the proxy for last activity for now.
$rotting_sql = "SELECT d.*, c.company 
                FROM deals d JOIN clients c ON d.client_id = c.id 
                WHERE d.stage NOT IN ('Closed Won', 'Closed Lost') 
                AND d.created_at < DATE_SUB(NOW(), INTERVAL 14 DAY)
                ORDER BY d.amount DESC LIMIT 5";
$stmt = $pdo->query($rotting_sql);
$rotting_deals = $stmt->fetchAll();

?>

<div class="main-content">
    
    <!-- Top Header (Added for consistency) -->
    <div class="top-header">
        <div class="search-bar">
            <!-- Optional Search -->
        </div>

        <div class="user-profile">
            <?php include __DIR__ . '/notification_dropdown.php'; ?>
            <?php include __DIR__ . '/header_profile_link.php'; ?>
        </div>
    </div>

    <!-- HEADER & FILTERS -->
    <div style="background:var(--bg-card); padding:20px; border-radius:12px; border:1px solid var(--border-color); margin-bottom:30px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:20px;">
        <div>
            <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-main); margin-bottom:5px;">Sales Intelligence</h1>
            <p style="color:var(--text-muted); font-size:0.9rem;">Analyze metrics, pipeline health, and efficiency.</p>
        </div>
        
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="page" value="reports">
            
            <div>
                <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--text-muted); margin-bottom:5px;">Date Range</label>
                <div style="display:flex; gap:5px;">
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" style="padding:8px; border:1px solid var(--border-color); background:var(--bg-body); color:var(--text-main); border-radius:6px; font-size:0.85rem;">
                    <span style="align-self:center; color:var(--text-muted);">to</span>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" style="padding:8px; border:1px solid var(--border-color); background:var(--bg-body); color:var(--text-main); border-radius:6px; font-size:0.85rem;">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:0.75rem; font-weight:700; color:var(--text-muted); margin-bottom:5px;">Sector</label>
                <select name="sector" style="padding:8px; border:1px solid var(--border-color); background:var(--bg-body); color:var(--text-main); border-radius:6px; font-size:0.85rem; min-width:120px;">
                    <option value="">All Sectors</option>
                    <option value="Agriculture" <?php if($sector_filter=='Agriculture') echo 'selected'; ?>>Agriculture</option>
                    <option value="Logistics" <?php if($sector_filter=='Logistics') echo 'selected'; ?>>Logistics</option>
                    <option value="Government" <?php if($sector_filter=='Government') echo 'selected'; ?>>Government</option>
                </select>
            </div>

            <button type="submit" style="height:35px; margin-top:18px; padding:0 20px; background:var(--bg-sidebar); color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Filter</button>
            
            <a href="index.php?page=reports<?php echo '&start_date='.$start_date.'&end_date='.$end_date.'&sector='.$sector_filter.'&stage='.$stage_filter.'&export=csv'; ?>" target="_blank" style="height:35px; margin-top:18px; padding:0 15px; background:var(--bg-card); border:1px solid var(--border-color); color:var(--text-main); border-radius:6px; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:5px;">
                <i class="fa-solid fa-download"></i> Export
            </a>
        </form>
    </div>

    <div class="content-scroll">
        
        <!-- KPI Row -->
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:20px; margin-bottom:30px;">
            <div style="background:var(--bg-card); padding:20px; border-radius:12px; border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.85rem; font-weight:700; text-transform:uppercase;">Revenue (Won)</div>
                <div style="font-size:1.6rem; font-weight:800; color:var(--text-main); margin-top:5px;">KES <?php echo number_format($total_revenue / 1000000, 2); ?>M</div>
            </div>
            <div style="background:var(--bg-card); padding:20px; border-radius:12px; border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.85rem; font-weight:700; text-transform:uppercase;">Pipeline Value</div>
                <div style="font-size:1.6rem; font-weight:800; color:#3b82f6; margin-top:5px;">KES <?php echo number_format($pipeline_value / 1000000, 2); ?>M</div>
            </div>
            <div style="background:var(--bg-card); padding:20px; border-radius:12px; border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.85rem; font-weight:700; text-transform:uppercase;">Win Rate</div>
                <div style="font-size:1.6rem; font-weight:800; color:#10b981; margin-top:5px;"><?php echo $win_rate; ?>%</div>
            </div>
             <div style="background:var(--bg-card); padding:20px; border-radius:12px; border:1px solid var(--border-color);">
                <div style="color:var(--text-muted); font-size:0.85rem; font-weight:700; text-transform:uppercase;">Stalled (>14 Days)</div>
                <div style="font-size:1.6rem; font-weight:800; color:#ef4444; margin-top:5px;"><?php echo count($rotting_deals); ?> Deals</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px; margin-bottom:30px;">
            
            <!-- Revenue Trend -->
            <div style="background:var(--bg-card); padding:25px; border-radius:16px; border:1px solid var(--border-color);">
                <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-main); margin-bottom:20px;">Revenue Performance</h3>
                <div style="height:300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Pipeline Funnel -->
            <div style="background:var(--bg-card); padding:25px; border-radius:16px; border:1px solid var(--border-color);">
                <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-main); margin-bottom:20px;">Sales Funnel</h3>
                <div style="height:300px;">
                    <canvas id="funnelChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RISK MANAGEMENT: Stalled Deals -->
        <div style="background:var(--bg-card); padding:25px; border-radius:16px; border:1px solid var(--border-color);">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="font-size:1.1rem; font-weight:700; color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> At Risk: Stalled Opportunities</h3>
                <div style="font-size:0.85rem; color:var(--text-muted);">No activity in 14+ days</div>
            </div>
            
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid var(--border-color);">
                        <th style="padding:10px; text-align:left; font-size:0.8rem; color:var(--text-muted);">Deal Name</th>
                        <th style="padding:10px; text-align:left; font-size:0.8rem; color:var(--text-muted);">Client</th>
                        <th style="padding:10px; text-align:left; font-size:0.8rem; color:var(--text-muted);">Current Stage</th>
                        <th style="padding:10px; text-align:right; font-size:0.8rem; color:var(--text-muted);">Value</th>
                        <th style="padding:10px; text-align:right; font-size:0.8rem; color:var(--text-muted);">Stalled Since</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rotting_deals as $deal): ?>
                    <tr style="border-bottom:1px solid var(--border-color);">
                        <td style="padding:15px 10px; font-weight:600; color:var(--text-main);"><?php echo htmlspecialchars($deal['deal_name']); ?></td>
                        <td style="padding:15px 10px; color:var(--text-muted);"><?php echo htmlspecialchars($deal['company']); ?></td>
                        <td style="padding:15px 10px;"><span style="background:var(--bg-body); color:var(--text-main); padding:3px 8px; border-radius:4px; font-size:0.8rem; border:1px solid var(--border-color);"><?php echo $deal['stage']; ?></span></td>
                        <td style="padding:15px 10px; text-align:right; font-weight:600; color:var(--text-main);">KES <?php echo number_format($deal['amount']); ?></td>
                        <td style="padding:15px 10px; text-align:right; color:#ef4444; font-size:0.85rem;"><?php echo date('M d', strtotime($deal['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($rotting_deals)): ?>
                        <tr><td colspan="5" style="padding:30px; text-align:center; color:#10b981;"><i class="fa-solid fa-circle-check"></i> Great job! No stalled deals found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($revenues); ?>,
                borderColor: '#3b82f6',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(59, 130, 246, 0.05)'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { borderDash: [5,5] } }, x: { grid: { display: false } } }
        }
    });

    // Funnel Chart
    const funnelLabels = <?php echo json_encode($stages_order); ?>;
    const funnelData = <?php echo json_encode($funnel_data); ?>;
    
    new Chart(document.getElementById('funnelChart'), {
        type: 'bar',
        data: {
            labels: funnelLabels,
            datasets: [{
                label: 'Deals',
                data: funnelData,
                backgroundColor: ['#cbd5e1', '#93c5fd', '#3b82f6', '#10b981', '#ef4444'],
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y', // Horizontal Bar Chart implies Funnel
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
        }
    });
</script>
