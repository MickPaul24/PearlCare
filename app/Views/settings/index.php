<?php
/** @var string $currency */
/** @var string $recordsPerPage */
/** @var array  $visibleColumns */
/** @var string $financeAmountPriority */
/** @var string $financeAmountUrgent */
/** @var string $financeAmountRoutine */
?>
<div class="card">
    <form method="POST" action="<?php echo BASE_URL; ?>/settings">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Currency</label><input type="text" name="currency" class="form-input" value="<?php echo e($currency); ?>" required></div>
            <div class="form-group"><label class="form-label">Records Per Page</label><input type="number" name="records_per_page" class="form-input" value="<?php echo e($recordsPerPage); ?>" min="5" max="100" required></div>
        </div>

        <div class="card" style="padding:20px;margin-top:20px;">
            <h3>Finance Fee Settings</h3>
            <p class="text-muted">Set the default invoice amounts by queue priority category.</p>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Priority Fee</label><input type="number" name="finance_amount_priority" class="form-input" value="<?php echo e($financeAmountPriority); ?>" min="0" step="1000" required></div>
                <div class="form-group"><label class="form-label">Urgent Fee</label><input type="number" name="finance_amount_urgent" class="form-input" value="<?php echo e($financeAmountUrgent); ?>" min="0" step="1000" required></div>
                <div class="form-group"><label class="form-label">Routine Fee</label><input type="number" name="finance_amount_routine" class="form-input" value="<?php echo e($financeAmountRoutine); ?>" min="0" step="1000" required></div>
            </div>
        </div>

        <div class="card" style="padding:20px;margin-top:20px;">
            <h3>Table Columns</h3>
            <p class="text-muted">Select which columns should be visible in the main modules.</p>
            <?php $columns = ['file_number'=>'File Number','full_name'=>'Patient Name','age'=>'Age','gender'=>'Gender','residence'=>'Residence','status'=>'Status','drug_name'=>'Drug Name','stock'=>'Stock','price'=>'Price']; ?>
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
                <?php foreach ($columns as $key => $label): ?>
                <label class="check-label">
                    <input type="checkbox" name="visible_columns[]" value="<?php echo e($key); ?>" <?php echo in_array($key,$visibleColumns,true)?'checked':''; ?>>
                    <?php echo e($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="padding:20px;margin-top:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <h3 style="margin:0;">Dashboard Configuration</h3>
                <span style="font-size:.75rem;color:var(--text-muted);">Changes save with the form</span>
            </div>
            <p class="text-muted" style="margin-bottom:16px;">Control which sections each role sees on their dashboard.</p>

            <!-- Role tabs -->
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px;" id="roleTabs">
                <?php foreach(['admin','doctor','nurse','receptionist','records'] as $i=>$r): ?>
                <button type="button" class="role-tab <?php echo $i===0?'active':''; ?>" data-role="<?php echo $r; ?>"
                    style="padding:7px 18px;border-radius:50px;border:1.5px solid var(--border);font-size:.8rem;font-weight:600;cursor:pointer;background:<?php echo $i===0?'var(--primary)':'var(--surface)'; ?>;color:<?php echo $i===0?'#fff':'var(--text-muted)'; ?>;transition:all .15s;text-transform:capitalize;">
                    <?php echo $r; ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div id="dashboardConfigContainer"></div>
        </div>

        <div class="modal-footer" style="justify-content:flex-start;padding:0;">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<style>
.role-tab:hover{background:var(--primary-bg)!important;color:var(--primary)!important;border-color:var(--primary)!important;}
.role-tab.active{background:var(--primary)!important;color:#fff!important;border-color:var(--primary)!important;}
.db-toggle{position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0;}
.db-toggle input{opacity:0;width:0;height:0;}
.db-toggle-slider{position:absolute;inset:0;background:#d1d5db;border-radius:50px;cursor:pointer;transition:.2s;}
.db-toggle-slider::before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;}
.db-toggle input:checked + .db-toggle-slider{background:var(--primary);}
.db-toggle input:checked + .db-toggle-slider::before{transform:translateX(18px);}
.db-section-row{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;background:var(--surface);}
.db-section-row:last-child{margin-bottom:0;}
.db-section-icon{width:32px;height:32px;border-radius:8px;display:grid;place-items:center;flex-shrink:0;margin-right:12px;}
.db-section-label{font-size:.85rem;font-weight:600;}
.db-section-desc{font-size:.72rem;color:var(--text-muted);margin-top:1px;}
.db-group-title{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-faint);font-weight:700;margin:14px 0 8px;}
</style>

<script>
const AJAX_URL = '<?php echo BASE_URL; ?>/ajax';

const SECTIONS = [
    {key:'stats_cards',         label:'Statistics Cards',        desc:'Visits, queue, revenue, labs & patients overview', icon:'layout-dashboard', color:'#2563eb', bg:'#eff6ff', group:'Overview'},
    {key:'weekly_visits_chart', label:'Weekly Visits Chart',     desc:'Bar chart of patient visits over the last 7 days',  icon:'bar-chart-2',      color:'#0ea5e9', bg:'#f0f9ff', group:'Charts'},
    {key:'queue_donut',         label:'Queue Status Chart',      desc:'Live donut showing today\'s queue breakdown',       icon:'pie-chart',         color:'#d97706', bg:'#fefce8', group:'Charts'},
    {key:'revenue_chart',       label:'Revenue Trend Chart',     desc:'Monthly revenue bar chart — last 6 months',         icon:'trending-up',       color:'#16a34a', bg:'#f0fdf4', group:'Charts'},
    {key:'lab_distribution_chart',label:'Lab Results Chart',    desc:'Distribution of all-time lab test outcomes',        icon:'activity',          color:'#dc2626', bg:'#fef2f2', group:'Charts'},
    {key:'queue_list',          label:'Today\'s Queue List',     desc:'Live list of patients currently in queue',          icon:'list-ordered',      color:'#7c3aed', bg:'#f5f3ff', group:'Data'},
    {key:'top_drugs',           label:'Top Prescribed Drugs',    desc:'Most frequently prescribed medications',            icon:'pill',              color:'#0891b2', bg:'#ecfeff', group:'Data'},
    {key:'recent_patients',     label:'Recent Patients',         desc:'Latest registered patient records',                 icon:'users',             color:'#059669', bg:'#ecfdf5', group:'Data'},
    {key:'recent_tests_table',  label:'Recent Lab Tests',        desc:'Most recent lab test entries with status',          icon:'flask-conical',     color:'#ea580c', bg:'#fff7ed', group:'Data'},
];
const GROUPS = ['Overview','Charts','Data'];
const ROLES  = ['admin','doctor','nurse','receptionist','records'];
const ROLE_LABELS = {admin:'Administrator',doctor:'Doctor',nurse:'Nurse',receptionist:'Receptionist',records:'Records'};

let _config = {};
let _activeRole = 'admin';

async function loadDashboardConfig() {
    try {
        const fd = new FormData();
        fd.append('action', 'get_dashboard_config');
        const res = await fetch(AJAX_URL, {method:'POST', body:fd});
        const data = await res.json();
        if (data.success) { _config = data.config || {}; renderPanel(_activeRole); }
    } catch(e) { console.error('Dashboard config load error', e); }
}

function renderPanel(role) {
    const el = document.getElementById('dashboardConfigContainer');
    let html = '';
    GROUPS.forEach(function(group) {
        const secs = SECTIONS.filter(s => s.group === group);
        html += '<div class="db-group-title">' + group + '</div>';
        secs.forEach(function(sec) {
            const cfgKey  = role + '_' + sec.key;
            const checked = (_config[cfgKey] !== false) ? true : false;
            html += '<div class="db-section-row">'
                + '<div style="display:flex;align-items:center;flex:1;min-width:0;">'
                + '<div class="db-section-icon" style="background:' + sec.bg + ';">'
                + '<i data-lucide="' + sec.icon + '" style="width:15px;height:15px;color:' + sec.color + ';"></i>'
                + '</div>'
                + '<div><div class="db-section-label">' + sec.label + '</div>'
                + '<div class="db-section-desc">' + sec.desc + '</div></div>'
                + '</div>'
                + '<label class="db-toggle" style="margin-left:16px;">'
                + '<input type="checkbox" name="dashboard_sections[]" value="' + cfgKey + '" data-role="' + role + '" data-section="' + sec.key + '"' + (checked ? ' checked' : '') + '>'
                + '<span class="db-toggle-slider"></span>'
                + '</label>'
                + '</div>';
        });
    });
    el.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons({nodes: [el]});
}

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardConfig();

    document.getElementById('roleTabs').addEventListener('click', function(e) {
        const tab = e.target.closest('.role-tab');
        if (!tab) return;
        // Save current panel state into _config before switching
        document.querySelectorAll('input[name="dashboard_sections[]"]').forEach(function(cb) {
            _config[cb.value] = cb.checked;
        });
        _activeRole = tab.dataset.role;
        document.querySelectorAll('.role-tab').forEach(function(t) { t.classList.remove('active'); });
        tab.classList.add('active');
        renderPanel(_activeRole);
    });

    document.querySelector('form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        // Capture current tab state
        document.querySelectorAll('input[name="dashboard_sections[]"]').forEach(function(cb) {
            _config[cb.value] = cb.checked;
        });
        // Build full payload from all roles & sections
        const payload = [];
        ROLES.forEach(function(role) {
            SECTIONS.forEach(function(sec) {
                const key = role + '_' + sec.key;
                payload.push({key:key, role:role, section:sec.key, enabled: _config[key] !== false});
            });
        });
        try {
            const fd = new FormData();
            fd.append('action', 'update_dashboard_config');
            fd.append('dashboard_sections_config', JSON.stringify(payload));
            await fetch(AJAX_URL, {method:'POST', body:fd});
        } catch(err) { console.error('Dashboard config save error', err); }
        form.submit();
    });
});
</script>
