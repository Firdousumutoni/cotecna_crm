<!-- templates/dashboard.php -->
<div class="main-content">
    
    <!-- Top Header -->
    <div class="top-header">
        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Search CRM...">
        </div>

        <div class="user-profile">
            <?php include __DIR__ . '/notification_dropdown.php'; ?>
            <?php include __DIR__ . '/header_profile_link.php'; ?>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="content-scroll">
        
        <div style="margin-bottom:30px;">
            <h1 style="font-size:1.8rem; font-weight:700; color:var(--text-main); margin-bottom:5px;">Command Center</h1>
            <p style="color:var(--text-muted);">Real-time operational overview for Cotecna Kenya.</p>
        </div>

        <!-- Stats Grid -->
        <div class="dashboard-grid animate-entry">
            <!-- Revenue Card -->
            <div class="stat-card">
                <div class="stat-trend trend-up">
                    <i class="fa-solid fa-arrow-trend-up"></i> +12.5%
                </div>
                <div class="stat-icon blue">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
                <div class="stat-value">KES 2,300,000</div>
                <div class="stat-label">Total Revenue</div>
            </div>

            <!-- Active Clients -->
            <div class="stat-card">
                <div class="stat-trend trend-up">
                    <i class="fa-solid fa-arrow-trend-up"></i> +4.2%
                </div>
                <div class="stat-icon green">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-value">7</div>
                <div class="stat-label">Active Clients</div>
            </div>

            <!-- Pending Jobs -->
            <div class="stat-card">
                <div class="stat-trend trend-down">
                    <i class="fa-solid fa-arrow-trend-down"></i> -2.1%
                </div>
                <div class="stat-icon orange">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div class="stat-value">9</div>
                <div class="stat-label">Pending Jobs</div>
            </div>

            <!-- Deals Pipeline -->
            <div class="stat-card">
                <div class="stat-arrow-right" style="position:absolute; top:25px; right:25px; color:#cbd5e1;">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
                <div class="stat-icon purple">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <div class="stat-value">15</div>
                <div class="stat-label">Deals Pipeline</div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid animate-entry" style="animation-delay: 0.1s;">
            <!-- Financial Performance -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">Financial Performance (2024/2025)</div>
                    <a href="#" class="card-action">View Detailed Report</a>
                </div>
                <div style="height: 300px; width: 100%;">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>

            <!-- Customer Satisfaction -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fa-regular fa-star" style="color:#f59e0b; margin-right:8px;"></i> Customer Satisfaction
                    </div>
                </div>
                <div style="height: 200px; width: 100%; display:flex; justify-content:center; align-items:center; position:relative;">
                     <canvas id="satisfactionChart"></canvas>
                     <div style="position:absolute; text-align:center;">
                         <div style="font-size:2rem; font-weight:700; color:var(--text-main);">85%</div>
                         <div style="font-size:0.8rem; color:var(--text-muted);">Satisfied</div>
                     </div>
                </div>
                <div style="margin-top:20px; display:flex; gap:15px; justify-content:center; font-size:0.85rem;">
                     <div style="display:flex; align-items:center; gap:5px;"><span style="width:8px; height:8px; background:#22c55e; border-radius:50%;"></span> Satisfied: 85%</div>
                     <div style="display:flex; align-items:center; gap:5px;"><span style="width:8px; height:8px; background:#f59e0b; border-radius:50%;"></span> Neutral: 10%</div>
                </div>
            </div>
        </div>

        <!-- Bottom Grid: Recent Activity & Team -->
        <div class="charts-grid animate-entry" style="animation-delay: 0.2s;">
            <!-- Recent Activities -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fa-regular fa-clock" style="color:var(--brand-blue); margin-right:8px;"></i> Recent Activities
                    </div>
                </div>
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-time">Just now</div>
                        <div class="activity-text">
                            <strong>Inspection #111</strong> updated to Completed
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-time">10 mins ago</div>
                        <div class="activity-text">
                            <strong>Inspection #111</strong> marked as In Progress
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-time">1 hour ago</div>
                        <div class="activity-text">
                            New Client <strong>John Doe</strong> added to Directory
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-time">3 hours ago</div>
                        <div class="activity-text">
                            Q4 Revenue Report generated
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Team Availability -->
            <div class="content-card">
                <div class="card-header">
                    <div class="card-title">Team Availability</div>
                    <span class="status-badge status-available">2 Available</span>
                </div>
                
                <div style="display:flex; justify-content:space-between; margin-bottom:20px; text-align:center;">
                    <div>
                        <div style="font-size:1.5rem; font-weight:700; color:#16a34a;">2</div>
                        <div style="font-size:0.8rem; color:#64748b;">Ready</div>
                    </div>
                    <div>
                        <div style="font-size:1.5rem; font-weight:700; color:#dc2626;">3</div>
                        <div style="font-size:0.8rem; color:#64748b;">Deployed</div>
                    </div>
                </div>

                <ul class="activity-list">
                    <li class="activity-item" style="align-items:center;">
                        <div class="profile-avatar" style="width:32px; height:32px; font-size:0.8rem; background:#e2e8f0; color:#64748b;">JO</div>
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:0.9rem;">James Omondi</div>
                            <div style="font-size:0.75rem; color:#64748b;">@ Mombasa Port</div>
                        </div>
                        <span style="width:8px; height:8px; background:#dc2626; border-radius:50%;" title="Deployed"></span>
                    </li>
                    <li class="activity-item" style="align-items:center;">
                        <div class="profile-avatar" style="width:32px; height:32px; font-size:0.8rem; background:#e2e8f0; color:#64748b;">SW</div>
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:0.9rem;">Sarah Wanjiku</div>
                            <div style="font-size:0.75rem; color:#64748b;">Available</div>
                        </div>
                        <span style="width:8px; height:8px; background:#16a34a; border-radius:50%;" title="Available"></span>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Financial Chart
    const ctx = document.getElementById('financeChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
            datasets: [{
                label: 'Revenue (KES)',
                data: [2500, 3000, 2000, 4800, 4500, 6000, 5800, 8000, 9500, 11000],
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ef4444',
                pointRadius: 0,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#f1f5f9' },
                    ticks: { callback: function(value) { return value; } }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Satisfaction Chart
    const ctxSat = document.getElementById('satisfactionChart').getContext('2d');
    new Chart(ctxSat, {
        type: 'doughnut',
        data: {
            labels: ['Satisfied', 'Neutral', 'Unsatisfied'],
            datasets: [{
                data: [85, 10, 5],
                backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Hover effect for stat cards
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            // Can add sound or JS animation here if needed
        });
    });
});
</script>
