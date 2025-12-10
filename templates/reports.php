<!-- templates/reports.php -->
<div class="glass-panel" style="padding:20px;">
    <h2>Reports Generation</h2>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px;">
        <div class="glass-panel" style="padding:20px; background:rgba(255,255,255,0.02);">
            <div style="display:flex; align-items:center; gap:15px; margin-bottom:15px;">
                <div style="background:rgba(59,130,246,0.2); width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-file-invoice-dollar" style="font-size:1.5rem; color:#60a5fa;"></i>
                </div>
                <div>
                    <h3 style="margin:0;">Revenue Report</h3>
                    <div style="opacity:0.6; font-size:0.9rem;">Monthly financial summary</div>
                </div>
            </div>
            <p style="opacity:0.7; font-size:0.9rem;">Generate a detailed breakdown of revenue by product category and client type.</p>
            <button class="glass-btn" style="width:100%;">Download PDF</button>
        </div>

        <div class="glass-panel" style="padding:20px; background:rgba(255,255,255,0.02);">
            <div style="display:flex; align-items:center; gap:15px; margin-bottom:15px;">
                <div style="background:rgba(16,185,129,0.2); width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-certificate" style="font-size:1.5rem; color:#34d399;"></i>
                </div>
                <div>
                    <h3 style="margin:0;">Inspection Certificates</h3>
                    <div style="opacity:0.6; font-size:0.9rem;">Issued COCs Log</div>
                </div>
            </div>
            <p style="opacity:0.7; font-size:0.9rem;">List of all Certificates of Conformity issued within the selected date range.</p>
            <button class="glass-btn" style="width:100%;">Export CSV</button>
        </div>
    </div>
</div>
