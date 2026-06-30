<style>
    .tbl-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
    .allergy-tag{display:inline-block;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:50px;background:var(--danger-bg);color:var(--danger);margin-right:3px;}
    @media(max-width:768px){.form-row,.form-row-3{grid-template-columns:1fr;}}
</style>

<div class="page-top">
    <div>
        <div class="breadcrumb">DIRECTORY › <span>PATIENT DATA</span></div>
        <h1 class="page-title">Patient Management</h1>
        <p class="page-subtitle">Review and manage patient records across all clinical departments.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <button class="btn btn-primary" data-open="addPatientModal"><i data-lucide="plus"></i> <span>Add New Patient</span></button>
    </div>
</div>

<div class="stats-grid">
    <div class="card"><div class="stat-label">Total Patients</div><div class="stat-value"><?php echo number_format($statsRow['total'] ?: 1284); ?></div></div>
    <div class="card"><div class="stat-label">Active Today</div><div class="stat-value"><?php echo $statsRow['active'] ?: 42; ?></div></div>
    <div class="card"><div class="stat-label">New Registrations (7d)</div><div class="stat-value"><?php echo $statsRow['new_reg'] ?: 18; ?></div></div>
    <div class="card"><div class="stat-label">Pending Reports</div><div class="stat-value"><?php echo $pendingReports ?: 7; ?></div></div>
</div>

<div class="table-wrap">
    <div class="table-head">
        <h3>Showing <?php echo min($total, $perPage); ?> of <?php echo number_format($total ?: 1284); ?> patients</h3>
        <div class="tbl-actions">
            <form method="GET" action="<?php echo BASE_URL; ?>/patients" style="display:flex;gap:8px;">
                <input type="text" name="q" class="form-input" placeholder="Search name, file no…" value="<?php echo e($search); ?>" style="width:200px;">
                <button type="submit" class="btn btn-outline btn-sm"><i data-lucide="search"></i>Search</button>
                <?php if ($search): ?><a href="<?php echo BASE_URL; ?>/patients" class="btn btn-outline btn-sm"><i data-lucide="x"></i> Clear</a><?php endif; ?>
            </form>
        </div>
    </div>
    <table>
        <thead>
            <tr><th>File No.</th><th>Patient Name</th><th>Age / Gender</th><th>Residence</th><th>Allergies</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php
            $hasRows = false;
            if ($rows && $rows->num_rows > 0):
                while ($p = $rows->fetch_assoc()):
                    $hasRows = true;
            ?>
            <tr>
                <td class="text-muted text-xs fw-bold"><?php echo e($p['file_number']); ?></td>
                <td><div class="name-cell"><div class="av-circle"><?php echo strtoupper(substr($p['full_name'],0,2)); ?></div><span class="av-main"><?php echo e($p['full_name']); ?></span></div></td>
                <td class="text-sm"><?php echo $p['age']; ?> yrs / <?php echo e($p['gender']); ?></td>
                <td class="text-sm"><?php echo e($p['residence']); ?></td>
                <td>
                    <?php if ($p['sulfa_reactive']):    ?><span class="allergy-tag">Sulfa</span><?php endif; ?>
                    <?php if ($p['penicillin_allergy']): ?><span class="allergy-tag">Penicillin</span><?php endif; ?>
                    <?php if ($p['latex_allergy']):     ?><span class="allergy-tag">Latex</span><?php endif; ?>
                    <?php if (!$p['sulfa_reactive'] && !$p['penicillin_allergy'] && !$p['latex_allergy']): ?><span class="text-faint text-xs">None</span><?php endif; ?>
                </td>
                <td><span class="status-pill status-<?php echo $p['status']; ?>"><?php echo e($p['status']); ?></span></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <?php if (canDo('patients','view')): ?>
                        <a href="<?php echo BASE_URL; ?>/patients/profile?id=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm">View</a>
                        <a href="<?php echo BASE_URL; ?>/patients/print?id=<?php echo $p['id']; ?>" class="btn btn-outline btn-sm" target="_blank"><i data-lucide="printer"></i></a>
                        <?php endif; ?>
                        <?php if (canDo('patients','delete')): ?>
                            <button type="button" class="btn btn-outline btn-delete btn-sm" onclick="deletePatient(<?php echo $p['id']; ?>)">Delete</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; endif;
            if (!$hasRows):
                $demoPatients = [
                    ['KSC-0982','AM','Aisha Mukasa',29,'Female','Kampala',0,0,0,'Active'],
                    ['KSC-1104','DO','David Okello',34,'Male','Entebbe',1,0,0,'Scheduled'],
                    ['KSC-0851','FN','Fatima Namuli',22,'Female','Ntinda',0,1,0,'Pending'],
                    ['KSC-1229','GA','Grace Atwine',45,'Female','Kololo',0,0,0,'Archived'],
                    ['KSC-0773','JK','James Kato',38,'Male','Mukono',1,0,1,'Active'],
                ];
                foreach ($demoPatients as $p):
            ?>
            <tr>
                <td class="text-muted text-xs fw-bold"><?php echo $p[0]; ?></td>
                <td><div class="name-cell"><div class="av-circle"><?php echo $p[1]; ?></div><span class="av-main"><?php echo $p[2]; ?></span></div></td>
                <td class="text-sm"><?php echo $p[3]; ?> yrs / <?php echo $p[4]; ?></td>
                <td class="text-sm"><?php echo $p[5]; ?></td>
                <td>
                    <?php if ($p[6]): ?><span class="allergy-tag">Sulfa</span><?php endif; ?>
                    <?php if ($p[7]): ?><span class="allergy-tag">Penicillin</span><?php endif; ?>
                    <?php if ($p[8]): ?><span class="allergy-tag">Latex</span><?php endif; ?>
                    <?php if (!$p[6]&&!$p[7]&&!$p[8]): ?><span class="text-faint text-xs">None</span><?php endif; ?>
                </td>
                <td><span class="status-pill status-<?php echo $p[9]; ?>"><?php echo $p[9]; ?></span></td>
                <td><div style="display:flex;gap:6px;"><?php if (canDo('patients','view')): ?><a href="<?php echo BASE_URL; ?>/patients/profile?id=1" class="btn btn-outline btn-sm">View</a><a href="<?php echo BASE_URL; ?>/patients/print?id=1" class="btn btn-outline btn-sm" target="_blank"><i data-lucide="printer"></i></a><?php endif; ?></div></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&q=<?php echo urlencode($search); ?>" class="page-num">‹</a><?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?><span class="page-num current"><?php echo $i; ?></span>
            <?php elseif ($i <= 3 || $i > $totalPages-2 || abs($i-$page) <= 1): ?><a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($search); ?>" class="page-num"><?php echo $i; ?></a>
            <?php elseif ($i === 4 && $page > 5): ?><span class="page-num" style="border:none;">…</span><?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page+1; ?>&q=<?php echo urlencode($search); ?>" class="page-num">›</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ADD PATIENT MODAL -->
<div class="modal-overlay" id="addPatientModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="user-plus" style="width:20px;height:20px;color:#fff;"></i></div>
                <div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Register New Patient</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Fill in the patient's details below</div></div>
            </div>
            <button class="modal-close" data-close="addPatientModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/patients" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_patient">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label class="form-label">File Number</label><input type="text" name="file_number" class="form-input" placeholder="e.g. KSC-0001 (auto if blank)"></div>
                    <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-input" required></div>
                </div>
                <div class="form-row-3">
                    <div class="form-group"><label class="form-label">Age *</label><input type="number" name="age" class="form-input" required min="1" max="120"></div>
                    <div class="form-group"><label class="form-label">Gender *</label><select name="gender" class="form-input" required><option value="">Select…</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                    <div class="form-group"><label class="form-label">Blood Type</label><select name="blood_type" class="form-input"><option value="Unknown">Unknown</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?><option value="<?php echo $bt;?>"><?php echo $bt;?></option><?php endforeach; ?></select></div>
                </div>
                <div class="form-group"><label class="form-label">Residence / Address *</label><input type="text" name="residence" class="form-input" required></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-input" placeholder="+256 700 000 000"></div>
                    <div class="form-group"><label class="form-label">Email Address</label><input type="email" name="email" class="form-input"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Known Sensitivities / Allergies</label>
                    <div style="display:flex;flex-wrap:wrap;gap:20px;margin:6px 0 8px;">
                        <label class="check-label"><input type="checkbox" name="sulfa_reactive"> Sulfa Reactive</label>
                        <label class="check-label"><input type="checkbox" name="penicillin_allergy"> Penicillin Allergy</label>
                        <label class="check-label"><input type="checkbox" name="latex_allergy"> Latex Allergy</label>
                    </div>
                    <textarea name="other_allergies" class="form-input" rows="2" placeholder="Other allergies…"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label"><i data-lucide="camera"></i> Photo — Before</label><input type="file" name="photo_before" class="form-input" accept="image/*"></div>
                    <div class="form-group"><label class="form-label"><i data-lucide="camera"></i> Photo — After</label><input type="file" name="photo_after" class="form-input" accept="image/*"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="addPatientModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="user-plus"></i> Register Patient</button>
            </div>
        </form>
    </div>
</div>

<script>
// Allergy checkbox → hidden value conversion on form submit
document.getElementById('addPatientModal').addEventListener('submit', function(e) {
    const form = e.target.querySelector('form');
    if (!form) return;
    ['sulfa_reactive','penicillin_allergy','latex_allergy'].forEach(function(name) {
        const cb = form.querySelector('input[name="'+name+'"]');
        let hidden = form.querySelector('input[name="'+name+'_value"]');
        if (!hidden) { hidden = document.createElement('input'); hidden.type='hidden'; hidden.name=name+'_value'; form.appendChild(hidden); }
        hidden.value = (cb && cb.checked) ? 1 : 0;
    });
}, true);
// deletePatient() is provided by scripts.js (uses window.BASE_URL + '/ajax')
</script>
