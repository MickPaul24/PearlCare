<div class="stats-grid">
    <div class="card"><div class="stat-label">Total Patients</div><div class="stat-value"><?php echo number_format($totalPatients); ?></div><span class="stat-badge badge-up">Current</span></div>
    <div class="card"><div class="stat-label">Total Revenue</div><div class="stat-value">UGX <?php echo number_format($totalRevenue); ?></div><span class="stat-badge badge-up">Real-time</span></div>
    <div class="card"><div class="stat-label">Pending Lab Tests</div><div class="stat-value"><?php echo number_format($pendingLabTests); ?></div><span class="stat-badge badge-info">Active</span></div>
    <div class="card"><div class="stat-label">Waiting Queue</div><div class="stat-value"><?php echo number_format($activeQueue); ?></div><span class="stat-badge badge-info">Live</span></div>
</div>

<div class="card">
    <h3>Most Prescribed Drugs</h3>
    <div class="table-wrap" style="margin-top:16px;">
        <table style="cursor:pointer;">
            <thead><tr><th>Drug Name</th><th>Times Used</th><th>Action</th></tr></thead>
            <tbody>
                <?php if ($topDrugs && $topDrugs->num_rows): while ($row = $topDrugs->fetch_assoc()): ?>
                <tr style="cursor:pointer;" onclick="viewDrugDetails('<?php echo e($row['drug_name']); ?>',event)">
                    <td><?php echo e($row['drug_name']); ?></td>
                    <td><?php echo number_format($row['uses']); ?></td>
                    <td style="text-align:center;"><i data-lucide="arrow-right" style="width:16px;height:16px;"></i></td>
                </tr>
                <?php endwhile; else: ?><tr><td colspan="3">No prescription data available yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>Recent Lab Results</h3>
    <div class="table-wrap" style="margin-top:16px;">
        <table style="cursor:pointer;">
            <thead><tr><th>Patient</th><th>Test</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
                <?php if ($recentLab && $recentLab->num_rows): while ($row = $recentLab->fetch_assoc()): ?>
                <tr style="cursor:pointer;" onclick="viewLabDetails(<?php echo $row['lab_test_id']??0; ?>,'<?php echo e($row['full_name']); ?>','<?php echo e($row['test_name']); ?>',event)">
                    <td><?php echo e($row['full_name']); ?></td>
                    <td><?php echo e($row['test_name']); ?></td>
                    <td><span class="status-pill status-<?php echo strtolower($row['result_status']); ?>"><?php echo e($row['result_status']); ?></span></td>
                    <td><?php echo e(date('M j, Y', strtotime($row['created_at']))); ?></td>
                    <td style="text-align:center;"><i data-lucide="arrow-right" style="width:16px;height:16px;"></i></td>
                </tr>
                <?php endwhile; else: ?><tr><td colspan="5">No lab tests recorded yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Drug Details Modal -->
<div class="modal-overlay" id="drugDetailsModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#7c3aed 0%,#6d28d9 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="bar-chart-2" style="width:20px;height:20px;color:#fff;"></i></div><div id="drugName" style="color:#fff;font-size:1.05rem;font-weight:700;">Drug Details</div></div>
            <button class="modal-close" data-close="drugDetailsModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <div class="modal-body"><div id="drugDetailsContent">Loading...</div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline" data-close="drugDetailsModal">Close</button></div>
    </div>
</div>

<!-- Lab Test Details Modal -->
<div class="modal-overlay" id="labDetailsModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="microscope" style="width:20px;height:20px;color:#fff;"></i></div><div id="labTitle" style="color:#fff;font-size:1.05rem;font-weight:700;">Lab Test Details</div></div>
            <button class="modal-close" data-close="labDetailsModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <div class="modal-body"><div id="labDetailsContent">Loading...</div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline" data-close="labDetailsModal">Close</button></div>
    </div>
</div>

<script>
const AJAX_URL = '<?php echo BASE_URL; ?>/ajax';

function viewDrugDetails(drugName, e) {
    e.stopPropagation();
    document.getElementById('drugName').textContent = 'Prescriptions for: ' + drugName;
    document.getElementById('drugDetailsContent').innerHTML = '<p style="color:var(--text-muted);">Loading...</p>';
    openModal('drugDetailsModal');
    const fd = new FormData(); fd.append('action','get_drug_prescriptions'); fd.append('drug_name',drugName);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success&&data.prescriptions){
            let html='<div class="table-wrap"><table style="width:100%;"><thead><tr><th>Patient</th><th>Date</th><th>Doctor</th></tr></thead><tbody>';
            data.prescriptions.forEach(p=>{html+=`<tr><td>${p.patient_name}</td><td>${p.date}</td><td>${p.doctor_name}</td></tr>`;});
            html+='</tbody></table></div>';
            document.getElementById('drugDetailsContent').innerHTML=html;
        }else{document.getElementById('drugDetailsContent').innerHTML='<p style="color:var(--text-muted);">No prescription history found.</p>';}
    }).catch(()=>{document.getElementById('drugDetailsContent').innerHTML='<p style="color:var(--danger);">Error loading details.</p>';});
}

function viewLabDetails(testId, patientName, testName, e) {
    e.stopPropagation();
    document.getElementById('labTitle').textContent = 'Lab Test: ' + testName;
    document.getElementById('labDetailsContent').innerHTML = '<p style="color:var(--text-muted);">Loading...</p>';
    openModal('labDetailsModal');
    const fd = new FormData(); fd.append('action','get_lab_details'); fd.append('lab_test_id',testId);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if(data.success&&data.lab){
            const lab=data.lab;
            let html=`<div style="background:var(--surface-2,var(--surface));padding:12px;border-radius:8px;margin-bottom:16px;"><p><strong>Patient:</strong> ${lab.patient_name}</p><p><strong>Test Name:</strong> ${lab.test_name}</p><p><strong>Status:</strong> <span class="status-pill status-${lab.result_status.toLowerCase()}">${lab.result_status}</span></p><p><strong>Doctor:</strong> ${lab.doctor_name}</p><p><strong>Date:</strong> ${lab.date}</p>`;
            if(lab.file_path)html+=`<p><strong>File:</strong> <a href="${lab.file_path}" target="_blank" class="btn btn-primary btn-sm" style="text-decoration:none;"><i data-lucide="download"></i> Download</a></p>`;
            if(lab.notes)html+=`<p><strong>Notes:</strong> ${lab.notes}</p>`;
            html+='</div>';
            document.getElementById('labDetailsContent').innerHTML=html;
            try{if(typeof lucide!=='undefined')lucide.createIcons();}catch(e){}
        }else{document.getElementById('labDetailsContent').innerHTML='<p style="color:var(--text-muted);">No details found.</p>';}
    }).catch(()=>{document.getElementById('labDetailsContent').innerHTML='<p style="color:var(--danger);">Error loading details.</p>';});
}
</script>
