<!-- templates/settings.php -->
<div class="glass-panel" style="padding:20px; max-width:600px;">
    <h2>System Settings</h2>
    
    <div style="margin-bottom:30px;">
        <h3 style="border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px; margin-bottom:20px;">General Configuration</h3>
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px;">Company Name</label>
            <input type="text" class="glass-input" value="Cotecna Kenya Limited">
        </div>
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px;">Timezone</label>
            <select class="glass-input" style="background:#1e293b;">
                <option>Africa/Nairobi</option>
                <option>UTC</option>
            </select>
        </div>
    </div>

    <div style="margin-bottom:30px;">
        <h3 style="border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px; margin-bottom:20px;">Appearances</h3>
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:15px;">
            <span>Dark Mode</span>
            <label class="switch">
                <input type="checkbox" checked disabled>
                <span class="slider round" style="background:#3b82f6;"></span>
            </label>
        </div>
        <div style="opacity:0.6; font-size:0.9rem;">Theme is currently locked to 'Glass Dark'.</div>
    </div>

    <button class="glass-btn">Save Changes</button>
</div>
