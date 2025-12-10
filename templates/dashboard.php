<!-- templates/dashboard.php -->
<div class="grid-cols-3">
    <!-- Revenue Card -->
    <div class="glass-panel stat-card">
        <div class="stat-label">Total Revenue (Monthly)</div>
        <div class="stat-value text-success">$124,500</div>
        <div style="font-size:0.8rem; color:#34d399;">
            <i class="fa-solid fa-arrow-up"></i> +12% from last month
        </div>
    </div>

    <!-- Active Inspections -->
    <div class="glass-panel stat-card">
        <div class="stat-label">Pending Inspections</div>
        <div class="stat-value text-warning">42</div>
        <div style="font-size:0.8rem; color:#fbbf24;">
            <i class="fa-solid fa-clock"></i> 8 urgent
        </div>
    </div>

    <!-- Active Clients -->
    <div class="glass-panel stat-card">
        <div class="stat-label">Active Clients</div>
        <div class="stat-value">1,208</div>
        <div style="font-size:0.8rem; color:#60a5fa;">
            <i class="fa-solid fa-user-plus"></i> +14 new this week
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-top:20px;">
    <!-- Recent Inspections List -->
    <div class="glass-panel" style="padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Recent Inspections</h3>
            <button class="glass-btn" style="padding:8px 16px; font-size:0.8rem;">View All</button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Wheat Grain (Bulk)</td>
                    <td>Grain Bulk Handlers</td>
                    <td>Oct 24, 2023</td>
                    <td><span class="badge badge-warning">In Progress</span></td>
                </tr>
                <tr>
                    <td>Steel Coils</td>
                    <td>Mabati Rolling Mills</td>
                    <td>Oct 23, 2023</td>
                    <td><span class="badge badge-success">Certified</span></td>
                </tr>
                <tr>
                    <td>Vegetable Oil</td>
                    <td>Bidco Africa</td>
                    <td>Oct 22, 2023</td>
                    <td><span class="badge badge-info">Scheduled</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Revenue Chart Area -->
    <div class="glass-panel" style="padding:20px;">
        <h3>Revenue Trends</h3>
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Agriculture', 'Metals', 'Consumer Goods'],
            datasets: [{
                data: [45, 30, 25],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(245, 158, 11, 0.5)'
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: 'white' }
                }
            }
        }
    });
</script>
