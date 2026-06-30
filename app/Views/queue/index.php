<?php
/** @var array $waiting */
/** @var array $consult */
/** @var array $completed */
/** @var string $currentUserName */
?>
<style>
    .queue-board{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:20px;}
    .col-header{display:flex;align-items:center;gap:8px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:12px;}
    .col-dot{width:8px;height:8px;border-radius:50%;}
    .dot-yellow{background:#f59e0b;} .dot-blue{background:var(--primary);} .dot-green{background:var(--success);}
    .col-count{background:var(--surface2);color:var(--text-muted);padding:1px 8px;border-radius:50px;font-size:.72rem;}
    .flow-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;}
    .flow-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;display:grid;gap:10px;}
    .flow-card-title{text-transform:uppercase;font-size:.75rem;letter-spacing:.08em;color:var(--text-muted);font-weight:700;}
    .flow-card-count{font-size:2.25rem;font-weight:800;color:var(--text-dark);}
    .flow-card-note{font-size:.9rem;color:var(--text-muted);line-height:1.4;}
    .flow-card-note strong{color:var(--text);}
    .flow-card.waiting{border-left:4px solid #f59e0b;} .flow-card.consulting{border-left:4px solid var(--primary);} .flow-card.completed{border-left:4px solid var(--success);}
    .q-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:10px;}
    .q-card-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;}
    .q-prio-tag{font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:50px;}
    .prio-urgent{background:var(--danger-bg);color:var(--danger);} .prio-priority{background:var(--warning-bg);color:var(--warning);} .prio-routine{background:var(--primary-bg);color:var(--primary);}
    .q-wait-time{font-size:.73rem;color:var(--text-muted);} .q-name{font-weight:700;font-size:.95rem;} .q-reason{font-size:.78rem;color:var(--text-muted);margin-top:2px;}
    .q-actions{display:flex;gap:6px;margin-top:8px;} .q-actions .btn{flex:1;padding:6px 10px;font-size:.75rem;}
    .q-active-card{background:var(--surface);border:2px solid var(--primary);border-radius:var(--radius);padding:16px;margin-bottom:10px;}
    .q-active-name{font-family:'Sora',sans-serif;font-size:1.05rem;color:var(--primary);font-weight:700;}
    .q-meta-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:10px 0;} .q-meta-box{background:var(--surface2);border-radius:8px;padding:9px;}
    .q-meta-lbl{font-size:.63rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-faint);} .q-meta-val{font-weight:700;font-size:.9rem;margin-top:2px;}
    .done-row{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:9px;background:var(--surface2);margin-bottom:6px;}
    .done-check{width:26px;height:26px;border-radius:50%;background:var(--success-bg);color:var(--success);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
    .done-name{font-weight:600;font-size:.85rem;} .done-sub{font-size:.7rem;color:var(--text-muted);}
    .queue-footer{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px 20px;display:flex;gap:32px;align-items:center;flex-wrap:wrap;}
    .q-stat-label{font-size:.7rem;color:var(--text-muted);} .q-stat-value{font-family:'Sora',sans-serif;font-size:1.15rem;font-weight:700;}
    .sync-note{margin-left:auto;font-size:.75rem;color:var(--text-muted);} .sync-note strong{color:var(--success);}
    .modal-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1200;justify-content:center;align-items:center;}
    .modal-content{position:relative;z-index:1201;}
    .modal-overlay.open{display:flex;}
    .modal-content{background:var(--surface);border-radius:var(--radius);padding:24px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 25px rgba(0,0,0,0.3);}
    .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border);}
    .modal-header h2{font-size:1.3rem;margin:0;} .modal-close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-muted);}
    .form-group{margin-bottom:16px;} .form-group label{display:block;font-weight:600;margin-bottom:6px;font-size:.9rem;}
    .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:6px;font-size:.9rem;background:var(--surface);color:var(--text);}
    .form-group textarea{resize:vertical;min-height:80px;}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .checkbox-group{display:flex;gap:16px;flex-wrap:wrap;} .checkbox-item{display:flex;align-items:center;gap:6px;} .checkbox-item input{width:auto;}
    .modal-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:12px;border-top:1px solid var(--border);}
    .page-actions{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
    .refresh-badge{display:inline-flex;align-items:center;gap:6px;font-size:.75rem;color:var(--text-muted);}
    .spinner{display:inline-block;width:12px;height:12px;border:2px solid var(--primary-bg);border-top-color:var(--primary);border-radius:50%;animation:spin 1s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg);}}
    @media(max-width:860px){.queue-board{grid-template-columns:1fr;}.form-row{grid-template-columns:1fr;}}
    #q_suggestions{display:none;position:absolute;top:calc(100% + 2px);left:0;right:0;background:var(--surface);border:1px solid var(--border);border-radius:8px;z-index:1300;max-height:220px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.18);}
    .q-sug-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);transition:background .12s;}
    .q-sug-item:last-child{border-bottom:none;}
    .q-sug-item:hover,.q-sug-item.focused{background:var(--primary-bg);}
    .q-sug-name{font-weight:600;font-size:.88rem;}
    .q-sug-sub{font-size:.72rem;color:var(--text-muted);margin-top:2px;}
    #q_match_badge{display:none;}
    #q_match_badge.visible{display:flex;}
</style>

<div class="page-top">
    <div>
        <div class="breadcrumb">CLINIC MANAGEMENT › <span>QUEUE LIVE BOARD</span></div>
        <h1 class="page-title">Queue Management</h1>
        <p class="page-subtitle">Real-time patient flow and consultation tracking.</p>
    </div>
</div>

<div class="page-actions">
    <button class="btn btn-primary" onclick="openAddPatientModal()">
        <i data-lucide="plus"></i> Add Patient to Queue
    </button>
    <button class="btn btn-outline" onclick="forceRefreshQueue()">
        <i data-lucide="refresh-cw"></i> Force Refresh for New Day
    </button>
    <div style="margin-left:auto;">
        <span class="refresh-badge">Auto-updating <span class="spinner"></span></span>
    </div>
</div>

<div class="flow-cards">
    <div class="flow-card waiting">
        <div class="flow-card-title">Waiting</div>
        <div class="flow-card-count"><?php echo count($waiting); ?></div>
        <div class="flow-card-note" id="waiting-note">
            <?php echo count($waiting) > 0 ? '<strong>' . e($waiting[0]['full_name']) . '</strong> is next in line.' : 'No patients waiting right now.'; ?>
        </div>
    </div>
    <div class="flow-card consulting">
        <div class="flow-card-title">In Consultation</div>
        <div class="flow-card-count"><?php echo count($consult); ?></div>
        <div class="flow-card-note" id="consult-note">
            <?php echo count($consult) > 0 ? e($consult[0]['full_name']) . ' is currently in consultation.' : 'No active consultations currently.'; ?>
        </div>
    </div>
    <div class="flow-card completed">
        <div class="flow-card-title">Completed</div>
        <div class="flow-card-count"><?php echo count($completed); ?></div>
        <div class="flow-card-note" id="completed-note">
            <?php echo count($completed) > 0 ? 'Last completed: <strong>' . e($completed[0]['full_name']) . '</strong>.' : 'No completed consultations today.'; ?>
        </div>
    </div>
</div>

<div class="queue-board">
    <!-- WAITING -->
    <div>
        <div class="col-header"><span class="col-dot dot-yellow"></span>Waiting <span class="col-count" id="waiting-count"><?php echo count($waiting); ?></span></div>
        <div id="waiting-list">
            <?php foreach ($waiting as $q):
                $waitMins = round((time() - strtotime($q['check_in_time'])) / 60);
                $prioClass = 'prio-' . strtolower($q['priority']);
            ?>
            <div class="q-card" data-queue-id="<?php echo $q['id']; ?>">
                <div class="q-card-top">
                    <span class="q-prio-tag <?php echo $prioClass; ?>"><?php echo e($q['priority']); ?></span>
                    <span class="q-wait-time"><?php echo $waitMins; ?>m wait</span>
                </div>
                <div class="q-name"><?php echo e($q['full_name']); ?></div>
                <div class="q-reason"><?php echo e($q['visit_type']); ?></div>
                <div class="q-actions">
                    <button class="btn btn-primary" onclick="moveToConsultation(this, <?php echo $q['id']; ?>)">→ Next</button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!count($waiting)): ?><div style="padding:20px;text-align:center;color:var(--text-muted);">No patients waiting</div><?php endif; ?>
        </div>
    </div>

    <!-- IN CONSULTATION -->
    <div>
        <div class="col-header"><span class="col-dot dot-blue"></span>In Consultation <span class="col-count" id="consult-count"><?php echo count($consult); ?></span></div>
        <div id="consult-list">
            <?php foreach ($consult as $i => $q):
                $activeMins = round((time() - strtotime($q['start_time'])) / 60);
            ?>
            <?php if ($i === 0): ?>
            <div class="q-active-card" data-queue-id="<?php echo $q['id']; ?>">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;"><?php echo strtoupper(substr($q['full_name'], 0, 2)); ?></div>
                    <div>
                        <div class="q-active-name"><?php echo e($q['full_name']); ?></div>
                        <div class="text-xs" style="color:var(--primary);">⏱ <span class="timer-display" data-start="<?php echo $q['start_time']; ?>">Duration: <?php echo $activeMins; ?>m</span></div>
                    </div>
                </div>
                <div class="q-meta-row">
                    <div class="q-meta-box"><div class="q-meta-lbl">ROOM</div><div class="q-meta-val"><?php echo e($q['assigned_room'] ?? '—'); ?></div></div>
                    <div class="q-meta-box"><div class="q-meta-lbl">ASSIGNED DR.</div><div class="q-meta-val">Dr. <?php echo e($q['doctor_name'] ?? $currentUserName); ?></div></div>
                </div>
                <div style="display:flex;gap:8px;">
                    <?php if (!empty($q['patient_id']) && canDo('patients','view')): ?>
                    <a href="<?php echo BASE_URL; ?>/patients/profile?id=<?php echo $q['patient_id']; ?>" class="btn btn-outline btn-sm" target="_blank" style="flex:1;justify-content:center;">
                        <i data-lucide="user-round" style="width:14px;height:14px;"></i> View Profile
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-success <?php echo (!empty($q['patient_id']) && canDo('patients','view')) ? 'btn-sm' : ''; ?>" style="flex:1;justify-content:center;" onclick="completeConsultation(this, <?php echo $q['id']; ?>)">✓ Complete</button>
                </div>
            </div>
            <?php else: ?>
            <div class="q-card" data-queue-id="<?php echo $q['id']; ?>">
                <div class="q-card-top"><span class="q-prio-tag prio-routine">Follow-up</span><span class="q-wait-time"><?php echo $activeMins; ?>m active</span></div>
                <div class="q-name"><?php echo e($q['full_name']); ?></div>
                <div class="q-reason"><?php echo e($q['visit_type']); ?></div>
                <div class="text-xs text-muted" style="margin-top:5px;"><?php echo e($q['assigned_room'] ?? ''); ?> • Dr. <?php echo e($q['doctor_name'] ?? $currentUserName); ?></div>
                <div class="q-actions" style="display:flex;gap:6px;">
                    <?php if (!empty($q['patient_id']) && canDo('patients','view')): ?>
                    <a href="<?php echo BASE_URL; ?>/patients/profile?id=<?php echo $q['patient_id']; ?>" class="btn btn-outline btn-sm" target="_blank"><i data-lucide="user-round" style="width:13px;height:13px;"></i> Profile</a>
                    <?php endif; ?>
                    <button class="btn btn-success btn-sm" onclick="completeConsultation(this, <?php echo $q['id']; ?>)">✓ Complete</button>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!count($consult)): ?><div style="padding:20px;text-align:center;color:var(--text-muted);">No consultations in progress</div><?php endif; ?>
        </div>
    </div>

    <!-- COMPLETED -->
    <div>
        <div class="col-header"><span class="col-dot dot-green"></span>Completed <span class="col-count" id="completed-count"><?php echo count($completed); ?></span></div>
        <div id="completed-list">
            <?php foreach ($completed as $q):
                $agoMins = round((time() - strtotime($q['end_time'])) / 60);
            ?>
            <div class="done-row">
                <div class="done-check">✓</div>
                <div>
                    <div class="done-name"><?php echo e($q['full_name']); ?></div>
                    <div class="done-sub">Finished <?php echo $agoMins; ?>m ago • <?php echo e($q['visit_type']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!count($completed)): ?><div style="padding:20px;text-align:center;color:var(--text-muted);">No completed consultations today</div><?php endif; ?>
        </div>
    </div>
</div>

<div class="queue-footer">
    <div><div class="q-stat-label"><i data-lucide="clock"></i> Waiting</div><div class="q-stat-value" id="stat-waiting"><?php echo count($waiting); ?></div></div>
    <div><div class="q-stat-label"><i data-lucide="user"></i> In Consultation</div><div class="q-stat-value" id="stat-consult"><?php echo count($consult); ?></div></div>
    <div><div class="q-stat-label"><i data-lucide="check-circle"></i> Completed</div><div class="q-stat-value" id="stat-completed"><?php echo count($completed); ?></div></div>
    <div class="sync-note">Auto-update active • <strong id="last-sync">Just now</strong></div>
</div>

<!-- ADD PATIENT MODAL -->
<div class="modal-overlay" id="addPatientModal">
    <div class="modal-content">
        <div style="background:linear-gradient(135deg,#7c3aed 0%,#6d28d9 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="users" style="width:20px;height:20px;color:#fff;"></i></div>
                <div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Add Patient to Queue</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Register a new patient and assign priority</div></div>
            </div>
            <button class="modal-close" onclick="closeAddPatientModal()" style="color:#fff;opacity:.75;font-size:1.5rem;">&times;</button>
        </div>
        <form id="addPatientForm" onsubmit="submitAddPatient(event)">
            <input type="hidden" id="q_file_number" name="file_number">
            <fieldset style="border:none;padding:0;">
                <legend style="font-weight:700;font-size:.9rem;margin-bottom:12px;color:var(--primary);">PATIENT INFORMATION</legend>
                <div class="form-row">
                    <div class="form-group" style="position:relative;">
                        <label>Full Name *</label>
                        <input type="text" id="q_full_name" name="full_name" required placeholder="e.g., John Doe" autocomplete="off">
                        <div id="q_suggestions"></div>
                        <div id="q_match_badge" style="display:none;margin-top:5px;padding:5px 10px;background:var(--primary-bg);color:var(--primary);border-radius:5px;font-size:.78rem;display:none;align-items:center;gap:6px;">
                            <i data-lucide="user-check" style="width:13px;height:13px;flex-shrink:0;"></i>
                            <span id="q_match_text"></span>
                            <a href="#" id="q_clear_match" style="margin-left:auto;color:inherit;opacity:.7;text-decoration:underline;">clear</a>
                        </div>
                    </div>
                    <div class="form-group"><label>Age *</label><input type="number" id="q_age" name="age" required min="1" max="150"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Gender *</label><select id="q_gender" name="gender" required><option value="">Select gender</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                    <div class="form-group"><label>Phone</label><input type="tel" id="q_phone" name="phone" placeholder="+256 701 234 567"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email</label><input type="email" id="q_email" name="email"></div>
                    <div class="form-group"><label>Blood Type</label>
                        <select id="q_blood_type" name="blood_type"><option value="Unknown">Unknown</option><option>O Positive</option><option>O Negative</option><option>A Positive</option><option>A Negative</option><option>B Positive</option><option>B Negative</option><option>AB Positive</option><option>AB Negative</option></select>
                    </div>
                </div>
                <div class="form-group"><label>Residence</label><input type="text" id="q_residence" name="residence"></div>
                <div class="form-group"><label>Allergies</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item"><input type="checkbox" id="sulfa_reactive" name="sulfa_reactive"><label for="sulfa_reactive" style="margin:0;">Sulfa Reactive</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="penicillin_allergy" name="penicillin_allergy"><label for="penicillin_allergy" style="margin:0;">Penicillin</label></div>
                        <div class="checkbox-item"><input type="checkbox" id="latex_allergy" name="latex_allergy"><label for="latex_allergy" style="margin:0;">Latex</label></div>
                    </div>
                </div>
                <div class="form-group"><label>Other Allergies</label><input type="text" id="q_other_allergies" name="other_allergies"></div>
            </fieldset>
            <fieldset style="border:none;padding:0;margin-top:20px;">
                <legend style="font-weight:700;font-size:.9rem;margin-bottom:12px;color:var(--primary);">VISIT INFORMATION</legend>
                <div class="form-row">
                    <div class="form-group"><label>Visit Type *</label><input type="text" name="visit_type" required placeholder="e.g., Skin Consultation"></div>
                    <div class="form-group"><label>Priority Level *</label><select name="priority" required><option value="Routine">Routine</option><option value="Priority">Priority</option><option value="Urgent">Urgent</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Assign Doctor</label>
                        <select name="assigned_doctor">
                            <option value="0">Default to current user</option>
                            <?php foreach ($doctorOptions as $doctor): ?>
                                <option value="<?php echo (int)$doctor['id']; ?>"><?php echo e($doctor['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Chief Complaint</label><textarea name="chief_complaint" placeholder="Describe the patient's main concern..."></textarea></div>
                </div>
            </fieldset>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddPatientModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add to Queue</button>
            </div>
        </form>
    </div>
</div>

<!-- PREVIOUS VISITS PRINT MODAL -->
<div class="modal-overlay" id="printVisitsModal">
    <div class="modal-box" style="max-width:720px;">
        <div style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);margin:-26px -26px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="file-text" style="width:20px;height:20px;color:#fff;"></i></div>
                <div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Previous Visit Report</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Existing patient — review history before adding to queue</div></div>
            </div>
            <button type="button" onclick="closePrintVisitsModal()" style="color:#fff;opacity:.75;font-size:1.5rem;background:none;border:none;cursor:pointer;">×</button>
        </div>
        <div class="modal-body" id="printVisitsBody" style="min-height:120px;">
            <div style="text-align:center;padding:40px;color:var(--text-muted);">Loading…</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closePrintVisitsModal()">Cancel</button>
            <button type="button" class="btn btn-outline" id="printReportBtn" onclick="doPrintReport()"><i data-lucide="printer"></i> Print Report</button>
            <button type="button" class="btn btn-primary" id="confirmAddQueueBtn" onclick="doConfirmedAddToQueue()"><i data-lucide="plus-circle"></i> Add to Queue</button>
        </div>
    </div>
</div>

<script>
const AJAX_URL = '<?php echo BASE_URL; ?>/ajax';
const currentUserName = <?php echo json_encode($currentUserName); ?>;
const CAN_VIEW_PATIENTS = <?php echo canDo('patients','view') ? 'true' : 'false'; ?>;

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();

    window.openAddPatientModal = function() { document.getElementById('addPatientModal').classList.add('open'); };
    window.closeAddPatientModal = function() { document.getElementById('addPatientModal').classList.remove('open'); document.getElementById('addPatientForm').reset(); };
    document.getElementById('addPatientModal').addEventListener('click', function(e) { if (e.target === this) closeAddPatientModal(); });

    var _pendingQueueFormData = null;
    var _printData = { patient: null, visits: [] };

    function doAddToQueue(formData) {
        fetch(AJAX_URL, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:formData })
            .then(r => r.json()).then(data => {
                if (data.success) { showNotification('success', '✓ Patient added! File: ' + data.file_number); closeAddPatientModal(); refreshQueueData(); }
                else throw new Error(data.message || 'Unable to add patient.');
            }).catch(err => showNotification('error', '✗ ' + err.message));
    }

    window.submitAddPatient = function(event) {
        event.preventDefault();
        const form = document.getElementById('addPatientForm');
        const formData = new FormData(form);
        formData.append('action', 'queue_add_patient');
        formData.set('sulfa_reactive', document.getElementById('sulfa_reactive').checked ? 1 : 0);
        formData.set('penicillin_allergy', document.getElementById('penicillin_allergy').checked ? 1 : 0);
        formData.set('latex_allergy', document.getElementById('latex_allergy').checked ? 1 : 0);

        const patientId = window._qSelectedPatientId || 0;
        if (patientId) {
            _pendingQueueFormData = formData;
            openPrintVisitsModal(patientId);
        } else {
            doAddToQueue(formData);
        }
    };

    window.closePrintVisitsModal = function() {
        document.getElementById('printVisitsModal').classList.remove('open');
        _pendingQueueFormData = null;
    };

    window.doConfirmedAddToQueue = function() {
        if (!_pendingQueueFormData) return;
        const fd = _pendingQueueFormData;
        document.getElementById('printVisitsModal').classList.remove('open');
        _pendingQueueFormData = null;
        doAddToQueue(fd);
    };

    window.doPrintReport = function() {
        var p = _printData.patient;
        var visits = _printData.visits;
        if (!p) { showNotification('error', 'No patient data loaded yet.'); return; }

        var TOTAL_ROWS = 20;
        var rows = '';
        var remaining = TOTAL_ROWS;
        visits.forEach(function(v) {
            if (remaining <= 0) return;
            remaining--;
            var d = v.visit_date ? new Date(v.visit_date) : null;
            var dateStr = d ? (String(d.getDate()).padStart(2,'0') + '/' + String(d.getMonth()+1).padStart(2,'0') + '/' + d.getFullYear()) : '';
            var clinical = [v.chief_complaint || '', v.notes || ''].filter(Boolean).join(' · ') || v.visit_type || '';
            var noteHtml = escapeHtml(clinical)
                + (v.prescribed_drugs ? '<br><strong>Prescribed:</strong> ' + escapeHtml(v.prescribed_drugs) : '');
            rows += '<tr>'
                + '<td class="cell-date">' + escapeHtml(dateStr) + '</td>'
                + '<td class="cell-doctor">' + escapeHtml(v.doctor_name || '—') + '</td>'
                + '<td class="cell-note">' + noteHtml + '</td>'
                + '</tr>';
        });
        for (var i = 0; i < remaining; i++) {
            rows += '<tr class="empty-row"><td class="cell-date">&nbsp;</td><td class="cell-doctor">&nbsp;</td><td class="cell-note">&nbsp;</td></tr>';
        }

        var html = '<!DOCTYPE html><html><head><title>Patient Report &mdash; ' + escapeHtml(p.full_name) + '</title><style>'
            + '*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}'
            + 'body{font-family:Arial,sans-serif;font-size:13px;color:#111;background:#fff;}'
            + '@media screen{body{padding:24px;max-width:680px;margin:0 auto;}}'
            + '@media print{.no-print{display:none!important;}body{padding:0;}}'
            + '.no-print{margin-bottom:18px;}'
            + '.btn-print{display:inline-block;padding:8px 20px;background:#2563eb;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;}'
            + '.card{border:1.5px solid #555;padding:22px 24px 18px;}'
            + '.clinic-header{text-align:center;margin-bottom:14px;}'
            + '.clinic-name{font-size:22px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;}'
            + '.clinic-address,.clinic-contact{font-size:11.5px;margin-top:2px;}'
            + '.no-row{text-align:right;font-size:12px;font-weight:700;margin-bottom:10px;}'
            + '.info-line{display:flex;align-items:baseline;gap:0;margin-bottom:6px;font-size:12.5px;border-bottom:1px dotted #888;padding-bottom:3px;}'
            + '.info-label{font-weight:700;white-space:nowrap;margin-right:4px;}'
            + '.info-value{flex:1;font-size:12px;}'
            + '.age-sex{display:flex;gap:28px;margin-left:auto;}'
            + '.age-sex span{font-weight:700;white-space:nowrap;margin-right:4px;}'
            + '.age-sex .val{min-width:60px;border-bottom:1px dotted #888;display:inline-block;font-size:12px;}'
            + '.visits-table{width:100%;border-collapse:collapse;margin-top:14px;}'
            + '.visits-table th{border:1.5px solid #444;padding:5px 8px;font-size:12px;font-weight:700;text-align:center;background:#fff;}'
            + '.visits-table th.col-date{width:18%;}.visits-table th.col-doctor{width:22%;}'
            + '.visits-table td{border-left:1.5px solid #444;border-right:1.5px solid #444;padding:0;min-height:22px;font-size:12px;vertical-align:top;}'
            + '.visits-table td.cell-date{border-right:1.5px solid #444;padding:3px 6px;border-bottom:1px dotted #aaa;}'
            + '.visits-table td.cell-doctor{border-right:1.5px solid #444;padding:3px 6px;border-bottom:1px dotted #aaa;}'
            + '.visits-table td.cell-note{padding:3px 8px;border-bottom:1px dotted #aaa;}'
            + '.visits-table tr:last-child td{border-bottom:1.5px solid #444;}'
            + '.empty-row td{color:#bbb;}'
            + '</style></head><body>'
            + '<div class="no-print"><button class="btn-print" onclick="window.print()">Print / Save PDF</button></div>'
            + '<div class="card">'
            + '<div class="clinic-header">'
            + '<div class="clinic-name">Kampala Skin Clinic</div>'
            + '<div class="clinic-address">Plot 160 Kasule Rd. Wandegeya</div>'
            + '<div class="clinic-contact">P.O. Box 8487 Kampala &nbsp;Tel: 0414-535043, 0392-176904</div>'
            + '</div>'
            + '<div class="no-row">No. &nbsp;' + escapeHtml(p.file_number) + '</div>'
            + '<div class="info-line">'
            + '<span class="info-label">Name:</span><span class="info-value">' + escapeHtml(p.full_name) + '</span>'
            + '<div class="age-sex"><span>Age:</span><span class="val">' + escapeHtml(String(p.age)) + '</span><span>Sex:</span><span class="val">' + escapeHtml(p.gender) + '</span></div>'
            + '</div>'
            + '<div class="info-line">'
            + '<span class="info-label">Address:</span><span class="info-value">' + escapeHtml(p.residence || '') + '</span>'
            + '<div class="age-sex"><span>Tel.:</span><span class="val" style="min-width:100px">' + escapeHtml(p.phone || '—') + '</span></div>'
            + '</div>'
            + '<table class="visits-table"><thead><tr><th class="col-date">Date</th><th class="col-doctor">Doctor</th><th>Diagnosis / Notes</th></tr></thead>'
            + '<tbody>' + rows + '</tbody></table>'
            + '</div>'
            + '</body></html>';

        var win = window.open('', '_blank', 'width=720,height=700');
        win.document.write(html);
        win.document.close();
        win.focus();
        setTimeout(function(){ win.print(); }, 400);
    };

    function openPrintVisitsModal(patientId) {
        document.getElementById('printVisitsBody').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);">Loading…</div>';
        document.getElementById('printVisitsModal').classList.add('open');
        const fd = new FormData();
        fd.append('action', 'get_patient_visits');
        fd.append('patient_id', patientId);
        fetch(AJAX_URL, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
            .then(r => r.json()).then(data => {
                if (!data.success) throw new Error(data.message || 'Failed to load visits');
                _printData.patient = data.patient;
                _printData.visits  = data.visits;
                renderVisitReport(data.patient, data.visits);
            }).catch(err => {
                document.getElementById('printVisitsBody').innerHTML = '<div style="text-align:center;padding:40px;color:var(--danger);">Error: ' + escapeHtml(err.message) + '</div>';
            });
    }

    function renderVisitReport(patient, visits) {
        var html = '<h2 style="font-size:1rem;font-weight:700;margin:0 0 14px;">'
            + escapeHtml(patient.full_name)
            + ' <span style="font-size:.78rem;font-weight:400;color:var(--text-muted);">· ' + escapeHtml(patient.file_number) + '</span></h2>'
            + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 20px;padding:12px;background:var(--surface-alt,#f8fafc);border-radius:10px;margin-bottom:18px;">'
            + '<div><div style="font-size:.68rem;text-transform:uppercase;color:var(--text-faint);font-weight:600;">Age / Gender</div><div style="font-weight:600;font-size:.88rem;">' + escapeHtml(patient.age + ' yrs, ' + patient.gender) + '</div></div>'
            + '<div><div style="font-size:.68rem;text-transform:uppercase;color:var(--text-faint);font-weight:600;">Phone</div><div style="font-weight:600;font-size:.88rem;">' + escapeHtml(patient.phone || '—') + '</div></div>'
            + '</div>';

        if (!visits.length) {
            html += '<div style="text-align:center;padding:28px;color:var(--text-muted);font-size:.88rem;">No previous visits recorded.</div>';
        } else {
            html += '<div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;margin-bottom:8px;">Previous Visits (' + visits.length + ')</div>'
                + '<table class="lab-table" style="font-size:.82rem;">'
                + '<thead><tr><th>Date</th><th>Type</th><th>Doctor</th><th>Notes</th><th>Prescribed Drugs</th></tr></thead><tbody>';
            visits.forEach(function(v) {
                var dateStr = v.visit_date ? new Date(v.visit_date).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : '—';
                var notes = [v.chief_complaint, v.notes].filter(Boolean).join(' · ');
                var drugs = v.prescribed_drugs || '—';
                html += '<tr>'
                    + '<td style="white-space:nowrap;">' + escapeHtml(dateStr) + '</td>'
                    + '<td>' + escapeHtml(v.visit_type || '—') + '</td>'
                    + '<td style="white-space:nowrap;">' + escapeHtml(v.doctor_name || '—') + '</td>'
                    + '<td style="color:var(--text-muted);">' + escapeHtml(notes || '—') + '</td>'
                    + '<td style="color:var(--text-muted);">' + escapeHtml(drugs) + '</td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
        }
        document.getElementById('printVisitsBody').innerHTML = html;
        lucide.createIcons();
    }

    window.moveToConsultation = function(btn, queueId) {
        showConfirm('Move this patient to consultation?', function(confirmed) {
            if (!confirmed) return;
            showPrompt('Assign room (optional):', '', function(roomVal) {
                const formData = new FormData();
                formData.append('action', 'queue_next_patient');
                formData.append('queue_id', queueId);
                formData.append('assigned_doctor', '0');
                formData.append('assigned_room', roomVal || '');
                btn.disabled = true; btn.textContent = '...';
                fetch(AJAX_URL, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:formData })
                    .then(r => r.json()).then(data => { if (data.success) refreshQueueData(); else throw new Error(data.message); })
                    .catch(err => showNotification('error', 'Unable to move patient: ' + err.message))
                    .finally(() => { btn.disabled = false; btn.textContent = '→ Next'; });
            });
        });
    };

    window.completeConsultation = function(btn, queueId) {
        showConfirm('Mark consultation as completed?', function(confirmed) {
            if (!confirmed) return;
            const formData = new FormData();
            formData.append('action', 'queue_complete_consultation');
            formData.append('queue_id', queueId);
            btn.disabled = true; btn.textContent = '...';
            fetch(AJAX_URL, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:formData })
                .then(r => r.json()).then(data => { if (data.success) refreshQueueData(); else throw new Error(data.message); })
                .catch(err => showNotification('error', 'Unable to complete: ' + err.message))
                .finally(() => { btn.disabled = false; btn.textContent = '✓ Complete'; });
        });
    };

    window.forceRefreshQueue = function() {
        showConfirm('Clear queue and start new day?', function(confirmed) {
            if (!confirmed) return;
            const formData = new FormData();
            formData.append('action', 'queue_clear_day');
            fetch(AJAX_URL, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:formData })
                .then(r => r.json()).then(data => { if (data.success) { showNotification('success', 'Queue cleared'); location.reload(); } else throw new Error(data.message); })
                .catch(err => showNotification('error', 'Unable to clear: ' + err.message));
        });
    };

    function escapeHtml(t) { return t ? t.replace(/[&<>"'`]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'})[m]) : ''; }

    function renderWaitingList(items) {
        if (!items || !items.length) return '<div style="padding:20px;text-align:center;color:var(--text-muted);">No patients waiting</div>';
        return items.map(q => {
            var w = Math.round((Date.now() - new Date(q.check_in_time)) / 60000);
            var pc = 'prio-' + (q.priority || 'Routine').toLowerCase();
            return '<div class="q-card" data-queue-id="' + q.id + '"><div class="q-card-top"><span class="q-prio-tag ' + pc + '">' + escapeHtml(q.priority) + '</span><span class="q-wait-time">' + w + 'm wait</span></div><div class="q-name">' + escapeHtml(q.full_name) + '</div><div class="q-reason">' + escapeHtml(q.visit_type) + '</div><div class="q-actions"><button class="btn btn-primary" onclick="moveToConsultation(this,' + q.id + ')">→ Next</button></div></div>';
        }).join('');
    }

    function renderConsultingList(items) {
        if (!items || !items.length) return '<div style="padding:20px;text-align:center;color:var(--text-muted);">No consultations in progress</div>';
        var base = window.BASE_URL || '';
        return items.map((q, i) => {
            var m = q.start_time ? Math.round((Date.now() - new Date(q.start_time)) / 60000) : 0;
            var profileBtn = q.patient_id ? '<a href="' + base + '/patients/profile?id=' + q.patient_id + '" class="btn btn-outline btn-sm" target="_blank" style="flex:1;justify-content:center;">👤 View Profile</a>' : '';
            if (i === 0) {
                var actions = '<div style="display:flex;gap:8px;margin-top:10px;">'
                    + profileBtn
                    + '<button class="btn btn-success btn-sm" style="flex:1;justify-content:center;" onclick="completeConsultation(this,' + q.id + ')">✓ Complete</button>'
                    + '</div>';
                return '<div class="q-active-card" data-queue-id="' + q.id + '">'
                    + '<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">'
                    + '<div style="width:42px;height:42px;border-radius:50%;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;">' + escapeHtml((q.full_name||' ')[0].toUpperCase()) + '</div>'
                    + '<div><div class="q-active-name">' + escapeHtml(q.full_name) + '</div>'
                    + '<div class="text-xs" style="color:var(--primary);">⏱ <span class="timer-display" data-start="' + escapeHtml(q.start_time||'') + '">Duration: ' + m + 'm</span></div></div></div>'
                    + '<div class="q-meta-row">'
                    + '<div class="q-meta-box"><div class="q-meta-lbl">ROOM</div><div class="q-meta-val">' + escapeHtml(q.assigned_room||'—') + '</div></div>'
                    + '<div class="q-meta-box"><div class="q-meta-lbl">ASSIGNED DR.</div><div class="q-meta-val">Dr. ' + escapeHtml(q.doctor_name||currentUserName) + '</div></div>'
                    + '</div>'
                    + actions + '</div>';
            }
            return '<div class="q-card" data-queue-id="' + q.id + '">'
                + '<div class="q-card-top"><span class="q-prio-tag prio-routine">Follow-up</span><span class="q-wait-time">' + m + 'm active</span></div>'
                + '<div class="q-name">' + escapeHtml(q.full_name) + '</div>'
                + '<div class="q-reason">' + escapeHtml(q.visit_type) + '</div>'
                + '<div class="text-xs text-muted" style="margin-top:5px;">' + escapeHtml(q.assigned_room||'') + ' • Dr. ' + escapeHtml(q.doctor_name||currentUserName) + '</div>'
                + '<div class="q-actions" style="display:flex;gap:6px;">' + profileBtn + '<button class="btn btn-success btn-sm" onclick="completeConsultation(this,' + q.id + ')">✓ Complete</button></div>'
                + '</div>';
        }).join('');
    }

    function renderCompletedList(items) {
        if (!items || !items.length) return '<div style="padding:20px;text-align:center;color:var(--text-muted);">No completed consultations today</div>';
        return items.map(q => { var a = q.end_time ? Math.round((Date.now() - new Date(q.end_time)) / 60000) : 0; return '<div class="done-row"><div class="done-check">✓</div><div><div class="done-name">' + escapeHtml(q.full_name) + '</div><div class="done-sub">Finished ' + a + 'm ago • ' + escapeHtml(q.visit_type) + '</div></div></div>'; }).join('');
    }

    function updateTimers() {
        document.querySelectorAll('.timer-display[data-start]').forEach(function(el) {
            var secs = Math.floor((Date.now() - new Date(el.dataset.start)) / 1000);
            el.textContent = 'Duration: ' + Math.floor(secs/60) + 'm ' + String(secs%60).padStart(2,'0') + 's';
        });
    }

    function updateLastSync() {
        var n = new Date();
        document.getElementById('last-sync').textContent = String(n.getHours()).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
    }

    function refreshQueueData() {
        const fd = new FormData(); fd.append('action', 'get_queue_data');
        fetch(AJAX_URL, { method:'POST', body:fd }).then(r => r.json()).then(data => {
            if (!data.success) return;
            document.getElementById('waiting-count').textContent = data.waiting.length;
            document.getElementById('consult-count').textContent = data.consulting.length;
            document.getElementById('completed-count').textContent = data.completed.length;
            document.getElementById('stat-waiting').textContent = data.waiting.length;
            document.getElementById('stat-consult').textContent = data.consulting.length;
            document.getElementById('stat-completed').textContent = data.completed.length;
            var wn = document.getElementById('waiting-note');
            wn.innerHTML = data.waiting.length ? '<strong>' + escapeHtml(data.waiting[0].full_name) + '</strong> is next.' : 'No patients waiting.';
            var cn = document.getElementById('consult-note');
            cn.textContent = data.consulting.length ? escapeHtml(data.consulting[0].full_name) + ' is in consultation.' : 'No active consultations.';
            var dn = document.getElementById('completed-note');
            dn.innerHTML = data.completed.length ? 'Last: <strong>' + escapeHtml(data.completed[0].full_name) + '</strong>.' : 'No completed today.';
            document.getElementById('waiting-list').innerHTML = renderWaitingList(data.waiting);
            document.getElementById('consult-list').innerHTML = renderConsultingList(data.consulting);
            document.getElementById('completed-list').innerHTML = renderCompletedList(data.completed);
            updateTimers();
            updateLastSync();
        }).catch(err => console.error('Refresh error:', err));
    }

    updateTimers();
    setInterval(updateTimers, 1000);
    setInterval(refreshQueueData, 5000);
    refreshQueueData();

    // ── Patient autofill ────────────────────────────────────────────
    var qSearchTimer = null;
    var qSugPatients = [];
    var qFocusedIdx  = -1;

    function closeSuggestions() {
        document.getElementById('q_suggestions').style.display = 'none';
        qFocusedIdx = -1;
    }

    function renderSuggestions(patients) {
        var el = document.getElementById('q_suggestions');
        if (!patients.length) { closeSuggestions(); return; }
        qSugPatients = patients;
        el.innerHTML = patients.map(function(p, i) {
            return '<div class="q-sug-item" data-idx="' + i + '">'
                + '<div class="q-sug-name">' + escapeHtml(p.full_name) + '</div>'
                + '<div class="q-sug-sub">' + escapeHtml(p.file_number) + ' &nbsp;·&nbsp; ' + p.age + ' yrs &nbsp;·&nbsp; ' + escapeHtml(p.gender) + '</div>'
                + '</div>';
        }).join('');
        el.querySelectorAll('.q-sug-item').forEach(function(item) {
            item.addEventListener('mousedown', function(e) {
                e.preventDefault();
                fillPatientFields(qSugPatients[+this.dataset.idx]);
            });
        });
        el.style.display = 'block';
    }

    window._qSelectedPatientId = 0;

    function fillPatientFields(p) {
        document.getElementById('q_full_name').value       = p.full_name;
        document.getElementById('q_age').value             = p.age;
        document.getElementById('q_gender').value          = p.gender;
        document.getElementById('q_phone').value           = p.phone || '';
        document.getElementById('q_email').value           = p.email || '';
        document.getElementById('q_blood_type').value      = p.blood_type || 'Unknown';
        document.getElementById('q_residence').value       = p.residence || '';
        document.getElementById('sulfa_reactive').checked  = p.sulfa_reactive == 1;
        document.getElementById('penicillin_allergy').checked = p.penicillin_allergy == 1;
        document.getElementById('latex_allergy').checked   = p.latex_allergy == 1;
        document.getElementById('q_other_allergies').value = p.other_allergies || '';
        document.getElementById('q_file_number').value     = p.file_number;
        window._qSelectedPatientId = p.id || 0;

        var badge = document.getElementById('q_match_badge');
        document.getElementById('q_match_text').textContent = 'Existing patient — ' + p.file_number;
        badge.classList.add('visible');
        lucide.createIcons({ nodes: [badge] });

        closeSuggestions();
    }

    function clearPatientMatch() {
        document.getElementById('q_file_number').value = '';
        document.getElementById('q_match_badge').classList.remove('visible');
        window._qSelectedPatientId = 0;
        qSugPatients = [];
    }

    var nameInput = document.getElementById('q_full_name');

    nameInput.addEventListener('input', function() {
        clearPatientMatch();
        clearTimeout(qSearchTimer);
        var val = this.value.trim();
        if (val.length < 2) { closeSuggestions(); return; }
        qSearchTimer = setTimeout(function() {
            var fd = new FormData();
            fd.append('action', 'search_queue_patients');
            fd.append('q', val);
            fetch(AJAX_URL, { method:'POST', body:fd })
                .then(function(r) { return r.json(); })
                .then(function(data) { if (data.success) renderSuggestions(data.patients); })
                .catch(function() { closeSuggestions(); });
        }, 280);
    });

    nameInput.addEventListener('keydown', function(e) {
        var items = document.querySelectorAll('#q_suggestions .q-sug-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            items[qFocusedIdx]?.classList.remove('focused');
            qFocusedIdx = Math.min(qFocusedIdx + 1, items.length - 1);
            items[qFocusedIdx].classList.add('focused');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            items[qFocusedIdx]?.classList.remove('focused');
            qFocusedIdx = Math.max(qFocusedIdx - 1, 0);
            items[qFocusedIdx].classList.add('focused');
        } else if (e.key === 'Enter' && qFocusedIdx >= 0) {
            e.preventDefault();
            fillPatientFields(qSugPatients[qFocusedIdx]);
        } else if (e.key === 'Escape') {
            closeSuggestions();
        }
    });

    nameInput.addEventListener('blur', function() { setTimeout(closeSuggestions, 150); });

    document.getElementById('q_clear_match').addEventListener('click', function(e) {
        e.preventDefault();
        clearPatientMatch();
        document.getElementById('addPatientForm').reset();
        nameInput.focus();
    });

    var origCloseModal = window.closeAddPatientModal;
    window.closeAddPatientModal = function() { clearPatientMatch(); closeSuggestions(); origCloseModal(); };
    // ────────────────────────────────────────────────────────────────
});
</script>
