<?php
/** @var array $patient */
/** @var array $allPhotos */
/** @var mysqli_result|false $visits */
/** @var mysqli_result|false $labTests */
/** @var mysqli_result|false $visitOptions */
/** @var bool $hasVisitId */
/** @var bool $visitIdNullable */
/** @var mysqli $conn */
?>
<style>
    .profile-grid{display:grid;grid-template-columns:290px 1fr;gap:20px;align-items:start;}
    .profile-banner{height:80px;background:linear-gradient(135deg,#2563eb,#0ea5e9);border-radius:var(--radius) var(--radius) 0 0;}
    .profile-avatar{width:70px;height:70px;border-radius:50%;border:3px solid var(--surface);background:var(--primary-bg);color:var(--primary);font-size:1.3rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:-38px 0 0 18px;}
    .data-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-faint);font-weight:600;}
    .data-value{font-size:.9rem;font-weight:500;margin-top:2px;}
    .allergy-pill{display:inline-block;padding:3px 10px;border-radius:50px;background:var(--danger-bg);color:var(--danger);font-size:.72rem;font-weight:700;margin:2px;}
    .allergy-pill.sulfa{background:var(--warning-bg);color:var(--warning);}
    .timeline{padding-left:20px;position:relative;} .timeline::before{content:'';position:absolute;left:5px;top:0;bottom:0;width:2px;background:var(--border);}
    .tl-item{position:relative;padding-left:16px;margin-bottom:20px;}
    .tl-dot{position:absolute;left:-20px;top:5px;width:12px;height:12px;border-radius:50%;background:var(--primary);border:2px solid var(--surface);}
    .tl-dot.lab{background:#0ea5e9;} .tl-dot.diag{background:var(--warning);}
    .tl-badge{font-size:.62rem;text-transform:uppercase;letter-spacing:.07em;font-weight:700;padding:2px 8px;border-radius:50px;display:inline-block;margin-bottom:4px;}
    .badge-consult{background:var(--primary-bg);color:var(--primary);} .badge-lab{background:#e0f2fe;color:#0284c7;} .badge-diag{background:var(--warning-bg);color:var(--warning);}
    .tl-title{font-weight:700;font-size:.9rem;margin:2px 0;} .tl-meta{font-size:.73rem;color:var(--text-muted);margin-bottom:4px;} .tl-body{font-size:.83rem;line-height:1.6;color:var(--text-muted);}
    .photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;margin-top:10px;}
    .photo-card{position:relative;border:1px solid var(--border);border-radius:10px;overflow:hidden;background:var(--surface);}
    .photo-card img{width:100%;height:115px;object-fit:cover;display:block;cursor:pointer;}
    .photo-card-meta{padding:5px 8px;font-size:.7rem;display:flex;flex-direction:column;gap:1px;}
    .photo-card-type{font-weight:700;text-transform:capitalize;color:var(--text);}
    .photo-card-date{color:var(--text-muted);}
    .photo-del{position:absolute;top:5px;right:5px;width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;border:none;cursor:pointer;font-size:15px;line-height:22px;text-align:center;padding:0;display:none;}
    .photo-card:hover .photo-del{display:block;}
    .photo-empty{text-align:center;padding:32px 12px;color:var(--text-muted);font-size:.85rem;border:1px dashed var(--border);border-radius:10px;margin-top:10px;}
    .lab-table{width:100%;border-collapse:collapse;font-size:.85rem;}
    .lab-table thead tr{border-bottom:2px solid var(--border);}
    .lab-table thead th{padding:9px 14px;text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;white-space:nowrap;}
    .lab-table thead th.col-file{text-align:center;}
    .lab-table tbody tr{border-bottom:1px solid var(--border);}
    .lab-table tbody tr:last-child{border-bottom:none;}
    .lab-table td{padding:11px 14px;vertical-align:middle;}
    .lab-table td.col-file{text-align:center;}
    .lab-table .test-name{font-weight:600;color:var(--text);}
    .lab-table .test-notes{font-size:.75rem;color:var(--text-muted);margin-top:3px;line-height:1.4;}
    .lab-table .no-file{color:var(--text-faint,#c0c7d0);font-size:.85rem;}
    @media(max-width:860px){.profile-grid{grid-template-columns:1fr;}}
</style>

<div style="margin-bottom:16px;"><a href="<?php echo BASE_URL; ?>/patients" class="btn btn-outline btn-sm">Back to Patients</a></div>
<div style="margin-bottom:10px;"><span class="status-pill status-<?php echo $patient['status']; ?>"><?php echo e($patient['status']); ?> Treatment</span></div>

<div class="page-top">
    <div>
        <h1 class="page-title"><?php echo e($patient['full_name']); ?></h1>
        <p class="text-muted text-sm">ID: <?php echo e($patient['file_number']); ?> &nbsp;·&nbsp; Registered <?php echo date('M Y', strtotime($patient['created_at'])); ?></p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="<?php echo BASE_URL; ?>/patients/print?id=<?php echo $patient['id']; ?>" class="btn btn-outline" target="_blank">Print Form</a>
        <button type="button" class="btn btn-primary" data-open="editPatientModal">Edit Profile</button>
    </div>
</div>

<div class="profile-grid">
    <!-- LEFT COLUMN -->
    <div>
        <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
            <div class="profile-banner"></div>
            <div class="profile-avatar"><?php echo strtoupper(substr($patient['full_name'],0,2)); ?></div>
            <div style="padding:12px 18px 18px;">
                <div class="form-row" style="margin-bottom:10px;">
                    <div><div class="data-label">Age / Gender</div><div class="data-value"><?php echo $patient['age']; ?> yrs, <?php echo e($patient['gender']); ?></div></div>
                    <div><div class="data-label">Blood Type</div><div class="data-value" style="color:var(--danger);font-weight:700;"><?php echo e($patient['blood_type']); ?></div></div>
                </div>
                <div style="margin-bottom:8px;"><div class="data-label">Phone</div><div class="data-value"><?php echo e($patient['phone'] ?: '—'); ?></div></div>
                <div><div class="data-label">Residence</div><div class="data-value"><?php echo e($patient['residence']); ?></div></div>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <div class="section-title">Known Allergies</div>
            <?php if ($patient['penicillin_allergy']): ?><span class="allergy-pill">Penicillin</span><?php endif; ?>
            <?php if ($patient['sulfa_reactive']):     ?><span class="allergy-pill sulfa">Sulfa Drugs</span><?php endif; ?>
            <?php if ($patient['latex_allergy']):      ?><span class="allergy-pill">Latex</span><?php endif; ?>
            <?php if ($patient['other_allergies']):    ?><span class="allergy-pill"><?php echo e($patient['other_allergies']); ?></span><?php endif; ?>
            <?php if (!$patient['penicillin_allergy'] && !$patient['sulfa_reactive'] && !$patient['latex_allergy'] && !$patient['other_allergies']): ?><span class="text-muted text-sm">None recorded</span><?php endif; ?>
        </div>

        <div class="card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <div class="section-title" style="margin-bottom:0;">Clinical Photos</div>
                <button type="button" class="btn btn-primary btn-sm" data-open="uploadPhotoModal"><i data-lucide="plus"></i> Add Photo</button>
            </div>
            <?php if (empty($allPhotos)): ?>
            <div class="photo-empty"><i data-lucide="camera-off" style="width:28px;height:28px;opacity:.3;margin-bottom:6px;"></i><br>No photos uploaded yet.</div>
            <?php else: ?>
            <div class="photo-grid">
                <?php foreach ($allPhotos as $ph): ?>
                <div class="photo-card" data-id="<?php echo $ph['id']; ?>">
                    <img src="<?php echo BASE_URL . '/' . e($ph['file_path']); ?>" alt="<?php echo e($ph['photo_type']); ?>"
                         onclick="viewClinicalPhoto('<?php echo BASE_URL . '/' . htmlspecialchars($ph['file_path'], ENT_QUOTES); ?>')">
                    <div class="photo-card-meta">
                        <span class="photo-card-type"><?php echo e($ph['photo_type']); ?></span>
                        <span class="photo-card-date"><?php echo date('M d, Y', strtotime($ph['taken_at'])); ?></span>
                    </div>
                    <button class="photo-del" type="button" title="Delete" onclick="deletePhoto(<?php echo $ph['id']; ?>, this)">×</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <div class="section-title" style="margin-bottom:0;">Treatment History</div>
                <button class="btn btn-primary btn-sm" data-open="newVisitModal">+ New Entry</button>
            </div>
            <div class="timeline">
                <?php
                $hasVisits = false;
                if ($visits && $visits->num_rows > 0):
                    while ($v = $visits->fetch_assoc()): $hasVisits = true; ?>
                <div class="tl-item">
                    <div class="tl-dot"></div>
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                        <span class="tl-badge badge-consult">CONSULTATION</span>
                        <button type="button" class="btn btn-outline btn-sm" style="padding:2px 10px;font-size:.72rem;flex-shrink:0;"
                            onclick="openEditVisit(<?php echo htmlspecialchars(json_encode([
                                'id'             => (int)$v['id'],
                                'visit_type'     => $v['visit_type'],
                                'visit_date'     => substr($v['visit_date'], 0, 10),
                                'doctor_id'      => (int)$v['doctor_id'],
                                'chief_complaint'=> $v['chief_complaint'] ?? '',
                                'notes'          => $v['notes'] ?? '',
                                'drug_ids'       => array_values(array_filter(explode(',', $v['prescribed_drug_ids'] ?? ''))),
                            ]), ENT_QUOTES); ?>)">Edit</button>
                    </div>
                    <div class="tl-title"><?php echo e($v['visit_type']); ?></div>
                    <div class="tl-meta"><?php echo date('M d, Y', strtotime($v['visit_date'])); ?> · Dr. <?php echo e($v['doctor_name']); ?></div>
                    <?php if ($v['notes']): ?><div class="tl-body"><?php echo e($v['notes']); ?></div><?php endif; ?>
                    <?php if (!empty($v['prescribed_drugs'])): ?><div class="tl-body"><strong>Prescribed:</strong> <?php echo e($v['prescribed_drugs']); ?></div><?php endif; ?>
                </div>
                <?php endwhile; endif;
                if (!$hasVisits): ?>
                <div class="tl-item"><div class="tl-dot"></div><span class="tl-badge badge-consult">CONSULTATION</span><div class="tl-title">Severe Acne Vulgaris Follow-up</div><div class="tl-meta">Nov 14, 2023 · Dr. Musoke</div><div class="tl-body">Patient reports significant improvement after 4 weeks on Isotretinoin.</div></div>
                <div class="tl-item"><div class="tl-dot lab"></div><span class="tl-badge badge-lab">LAB TEST</span><div class="tl-title">Lab Results: Lipid Profile</div><div class="tl-meta">Oct 12, 2023 · Kampala Labs</div></div>
                <div class="tl-item"><div class="tl-dot diag"></div><span class="tl-badge badge-diag">DIAGNOSIS</span><div class="tl-title">Initial Diagnosis & Treatment Plan</div><div class="tl-meta">Sept 28, 2023 · Dr. Musoke</div><div class="tl-body">Cystic acne on cheeks and jawline. Prescribed Isotretinoin 20mg.</div></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="margin-top:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <div class="section-title" style="margin-bottom:0;">Lab Test Results</div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" data-open="addLabTestModal" style="margin-right:8px;"><i data-lucide="plus"></i> Add Test</button>
                    <a href="<?php echo BASE_URL; ?>/lab-tests" class="btn btn-outline btn-sm">View All</a>
                </div>
            </div>
            <table class="lab-table">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="col-file">Report</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hasLabTests = false;
                    if ($labTests && $labTests->num_rows > 0):
                        while ($lt = $labTests->fetch_assoc()): $hasLabTests = true;
                            $fp    = $lt['file_path'] ?? '';
                            $notes = trim($lt['result_notes'] ?? '');
                    ?>
                    <tr>
                        <td>
                            <div class="test-name"><?php echo e($lt['test_name']); ?></div>
                            <?php if ($notes): ?><div class="test-notes"><?php echo e($notes); ?></div><?php endif; ?>
                        </td>
                        <td><span class="status-pill status-<?php echo strtolower(e($lt['result_status'])); ?>" style="font-size:.72rem;"><?php echo e($lt['result_status']); ?></span></td>
                        <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($lt['created_at'])); ?></td>
                        <td class="col-file">
                            <?php if ($fp): ?>
                                <button type="button" class="btn btn-outline btn-sm" style="padding:4px 10px;" onclick="openLabFile('<?php echo BASE_URL . '/' . htmlspecialchars($fp, ENT_QUOTES); ?>')">
                                    <i data-lucide="file-text" style="width:13px;height:13px;"></i><span style="margin-left:4px;">View</span>
                                </button>
                            <?php else: ?><span class="no-file">—</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" style="padding:20px;text-align:center;color:var(--text-muted);font-size:.85rem;">No lab tests recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD LAB TEST MODAL -->
<div class="modal-overlay" id="addLabTestModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="flask-conical" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Add Lab Test Result</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Record a new laboratory test</div></div></div>
            <button class="modal-close" data-close="addLabTestModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form id="addLabTestForm">
            <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Test Name *</label><input type="text" name="test_name" class="form-input" required></div>
                <div class="form-group">
                    <label class="form-label">Associate Visit <?php if ($hasVisitId && !$visitIdNullable) echo '*'; ?></label>
                    <select name="visit_id" class="form-input" <?php if ($hasVisitId && !$visitIdNullable) echo 'required'; ?>>
                        <?php if (!$hasVisitId || $visitIdNullable): ?><option value="">No visit</option><?php else: ?><option value="">Select visit...</option><?php endif; ?>
                        <?php if ($visitOptions && $visitOptions->num_rows > 0): while ($vo = $visitOptions->fetch_assoc()): ?>
                            <option value="<?php echo $vo['id']; ?>"><?php echo e(date('M d, Y', strtotime($vo['visit_date'])) . ' — ' . $vo['visit_type']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Result Status *</label><select name="result_status" class="form-input" required><option value="">Select status...</option><option>Pending</option><option>In Progress</option><option>Clear</option><option>Reactive</option><option>Cancelled</option></select></div>
                <div class="form-group"><label class="form-label">Test File (PDF/Image, max 5MB)</label><input type="file" name="test_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.gif"></div>
                <div class="form-group"><label class="form-label">Notes</label><textarea name="result_notes" class="form-input" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="addLabTestModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveLabTestBtn"><i data-lucide="save"></i> Save Test</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT PATIENT MODAL -->
<div class="modal-overlay" id="editPatientModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="user-pen" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Edit Patient Profile</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Update patient information</div></div></div>
            <button class="modal-close" data-close="editPatientModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form id="editPatientForm" enctype="multipart/form-data">
            <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label class="form-label">File Number</label><input type="text" name="file_number" class="form-input" value="<?php echo e($patient['file_number']); ?>"></div>
                    <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-input" value="<?php echo e($patient['full_name']); ?>" required></div>
                </div>
                <div class="form-row-3">
                    <div class="form-group"><label class="form-label">Age *</label><input type="number" name="age" class="form-input" value="<?php echo $patient['age']; ?>" required min="1" max="120"></div>
                    <div class="form-group"><label class="form-label">Gender *</label><select name="gender" class="form-input" required><option value="">Select…</option><option value="Male" <?php echo $patient['gender']==='Male'?'selected':''; ?>>Male</option><option value="Female" <?php echo $patient['gender']==='Female'?'selected':''; ?>>Female</option><option value="Other" <?php echo $patient['gender']==='Other'?'selected':''; ?>>Other</option></select></div>
                    <div class="form-group"><label class="form-label">Blood Type</label><select name="blood_type" class="form-input"><option value="Unknown" <?php echo $patient['blood_type']==='Unknown'?'selected':''; ?>>Unknown</option><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?><option value="<?php echo $bt; ?>" <?php echo $patient['blood_type']===$bt?'selected':''; ?>><?php echo $bt; ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="form-group"><label class="form-label">Residence *</label><input type="text" name="residence" class="form-input" value="<?php echo e($patient['residence']); ?>" required></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" value="<?php echo e($patient['phone']); ?>"></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="<?php echo e($patient['email']); ?>"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Allergies</label>
                    <div style="display:flex;flex-wrap:wrap;gap:20px;margin:6px 0 8px;">
                        <label class="check-label"><input type="checkbox" name="sulfa_reactive" value="1" <?php echo $patient['sulfa_reactive']?'checked':''; ?>> Sulfa Reactive</label>
                        <label class="check-label"><input type="checkbox" name="penicillin_allergy" value="1" <?php echo $patient['penicillin_allergy']?'checked':''; ?>> Penicillin</label>
                        <label class="check-label"><input type="checkbox" name="latex_allergy" value="1" <?php echo $patient['latex_allergy']?'checked':''; ?>> Latex</label>
                    </div>
                    <textarea name="other_allergies" class="form-input" rows="2"><?php echo e($patient['other_allergies']); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Photo — Before</label><input type="file" name="photo_before" class="form-input" accept="image/*"></div>
                    <div class="form-group"><label class="form-label">Photo — After</label><input type="file" name="photo_after" class="form-input" accept="image/*"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="editPatientModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="savePatientBtn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- NEW VISIT MODAL -->
<div class="modal-overlay" id="newVisitModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="calendar-plus" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Record New Visit</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Document visit details and prescriptions</div></div></div>
            <button class="modal-close" data-close="newVisitModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form id="newVisitForm">
            <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Visit Type *</label><select name="visit_type" class="form-input" required><option value="">Select...</option><option>Follow-up Consultation</option><option>Initial Diagnosis</option><option>Acne Assessment</option><option>Post-Treatment Review</option><option>Emergency Visit</option></select></div>
                <div class="form-group"><label class="form-label">Visit Date *</label><input type="date" name="visit_date" class="form-input" required value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="form-group"><label class="form-label">Doctor *</label>
                    <select name="doctor_id" class="form-input" required>
                        <option value="">Select...</option>
                        <?php $docResult = $conn->query("SELECT id, full_name FROM users WHERE role = 'doctor' ORDER BY full_name"); if ($docResult) { while ($doc = $docResult->fetch_assoc()) { echo '<option value="' . $doc['id'] . '">' . e($doc['full_name']) . '</option>'; } } ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Clinical Notes</label><textarea name="notes" class="form-input" rows="4"></textarea></div>
                <div class="form-group"><label class="form-label">Prescribe Drugs</label>
                    <div style="border:1px solid var(--border);border-radius:9px;padding:12px;max-height:200px;overflow-y:auto;background:var(--bg);">
                        <?php $drugsResult = $conn->query("SELECT id, name, category FROM drugs WHERE is_active = 1 ORDER BY name"); if ($drugsResult && $drugsResult->num_rows > 0): while ($drug = $drugsResult->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);">
                            <input type="checkbox" name="prescribed_drugs[]" value="<?php echo $drug['id']; ?>" id="drug_<?php echo $drug['id']; ?>">
                            <label for="drug_<?php echo $drug['id']; ?>" style="cursor:pointer;flex:1;margin:0;"><strong><?php echo e($drug['name']); ?></strong><span style="color:var(--text-muted);font-size:.8rem;"> (<?php echo e($drug['category']??'General'); ?>)</span></label>
                        </div>
                        <?php endwhile; else: ?><p style="color:var(--text-muted);font-size:.85rem;margin:0;">No drugs available.</p><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="newVisitModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveVisitBtn"><i data-lucide="save"></i><span> Save Visit</span></button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT VISIT MODAL -->
<div class="modal-overlay" id="editVisitModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="pencil" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Edit Visit</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Update visit details and prescriptions</div></div></div>
            <button class="modal-close" data-close="editVisitModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form id="editVisitForm">
            <input type="hidden" id="ev_visit_id" name="visit_id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Visit Type *</label><select id="ev_visit_type" name="visit_type" class="form-input" required><option value="">Select...</option><option>Follow-up Consultation</option><option>Initial Diagnosis</option><option>Acne Assessment</option><option>Post-Treatment Review</option><option>Emergency Visit</option></select></div>
                <div class="form-group"><label class="form-label">Visit Date *</label><input type="date" id="ev_visit_date" name="visit_date" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Doctor *</label>
                    <select id="ev_doctor_id" name="doctor_id" class="form-input" required>
                        <option value="">Select...</option>
                        <?php $docResult2 = $conn->query("SELECT id, full_name FROM users WHERE role = 'doctor' ORDER BY full_name"); if ($docResult2) { while ($doc = $docResult2->fetch_assoc()) { echo '<option value="' . $doc['id'] . '">' . e($doc['full_name']) . '</option>'; } } ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Chief Complaint</label><textarea id="ev_chief_complaint" name="chief_complaint" class="form-input" rows="2"></textarea></div>
                <div class="form-group"><label class="form-label">Clinical Notes</label><textarea id="ev_notes" name="notes" class="form-input" rows="4"></textarea></div>
                <div class="form-group"><label class="form-label">Prescribed Drugs</label>
                    <div style="border:1px solid var(--border);border-radius:9px;padding:12px;max-height:200px;overflow-y:auto;background:var(--bg);">
                        <?php $drugsResult2 = $conn->query("SELECT id, name, category FROM drugs WHERE is_active = 1 ORDER BY name"); if ($drugsResult2 && $drugsResult2->num_rows > 0): while ($drug = $drugsResult2->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border);">
                            <input type="checkbox" name="prescribed_drugs[]" value="<?php echo $drug['id']; ?>" id="ev_drug_<?php echo $drug['id']; ?>">
                            <label for="ev_drug_<?php echo $drug['id']; ?>" style="cursor:pointer;flex:1;margin:0;"><strong><?php echo e($drug['name']); ?></strong><span style="color:var(--text-muted);font-size:.8rem;"> (<?php echo e($drug['category']??'General'); ?>)</span></label>
                        </div>
                        <?php endwhile; else: ?><p style="color:var(--text-muted);font-size:.85rem;margin:0;">No drugs available.</p><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="editVisitModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveEditVisitBtn"><i data-lucide="save"></i><span> Save Changes</span></button>
            </div>
        </form>
    </div>
</div>

<!-- UPLOAD PHOTO MODAL -->
<div class="modal-overlay" id="uploadPhotoModal">
    <div class="modal-box" style="max-width:440px;">
        <div style="background:linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%);margin:-26px -26px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="camera" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Add Clinical Photo</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Upload image to patient record</div></div></div>
            <button class="modal-close" data-close="uploadPhotoModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form id="uploadPhotoForm" enctype="multipart/form-data">
            <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Photo * <span style="color:var(--text-faint);font-weight:400;">(JPG, PNG, WEBP · max 5MB)</span></label><input type="file" name="photo_file" class="form-input" accept="image/*" required></div>
                <div class="form-group"><label class="form-label">Label *</label>
                    <select name="photo_type" class="form-input" required>
                        <option value="Before">Before</option>
                        <option value="After">After</option>
                        <option value="Progress">Progress</option>
                        <option value="Close-up">Close-up</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Date Taken</label><input type="date" name="taken_at" class="form-input" value="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="uploadPhotoModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="uploadPhotoBtn"><i data-lucide="upload"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- PHOTO VIEWER -->
<div class="modal-overlay" id="photoViewerModal">
    <div class="modal-box" style="max-width:860px;width:95%;background:#000;border-color:#333;">
        <div style="display:flex;justify-content:flex-end;padding:10px;">
            <button class="modal-close" data-close="photoViewerModal" style="color:#fff;font-size:1.8rem;background:none;border:none;cursor:pointer;line-height:1;">×</button>
        </div>
        <div style="padding:0 16px 20px;text-align:center;">
            <img id="photoViewerImg" src="" alt="" style="max-width:100%;max-height:75vh;border-radius:8px;object-fit:contain;">
        </div>
    </div>
</div>

<!-- Lab File Viewer Modal -->
<div class="modal-overlay" id="labFileViewerModal">
    <div class="modal-box" style="max-width:900px;width:95%;">
        <div style="background:linear-gradient(135deg,#475569 0%,#334155 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="file-search" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">View File</div></div></div>
            <button type="button" id="closeLabFileViewer" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <div class="modal-body viewer-body" style="padding:0;display:flex;justify-content:center;align-items:center;"></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline" id="downloadLabFileBtn">Download</button><button type="button" class="btn btn-primary" id="closeLabFileBtn">Close</button></div>
    </div>
</div>

<script>
const AJAX_URL = '<?php echo BASE_URL; ?>/ajax';

function openLabFile(url) {
    const modal = document.getElementById('labFileViewerModal');
    const body  = modal.querySelector('.viewer-body');
    const ext   = (url.split('.').pop()||'').toLowerCase();
    body.innerHTML = ['jpg','jpeg','png','gif'].includes(ext)
        ? '<img src="'+url+'" style="max-width:100%;max-height:70vh;display:block;margin:0 auto;">'
        : '<iframe src="'+url+'" style="width:100%;height:70vh;border:0;"></iframe>';
    document.getElementById('downloadLabFileBtn').onclick = () => window.open(url,'_blank');
    openModal('labFileViewerModal');
}
function closeLabFile() { const m = document.getElementById('labFileViewerModal'); closeModal('labFileViewerModal'); setTimeout(()=>{ m.querySelector('.viewer-body').innerHTML=''; },300); }
document.getElementById('closeLabFileBtn')?.addEventListener('click', closeLabFile);
document.getElementById('closeLabFileViewer')?.addEventListener('click', closeLabFile);

document.getElementById('addLabTestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this); fd.append('action','add_lab_test');
    const btn = document.getElementById('saveLabTestBtn'); const orig = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader"></i> Saving...'; btn.disabled = true;
    fetch(AJAX_URL, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json()).then(result => { btn.innerHTML=orig; btn.disabled=false; if (result.success) { showNotification('success','Lab test added'); document.querySelector('[data-close="addLabTestModal"]').click(); setTimeout(()=>location.reload(),500); } else showNotification('error','Error: '+(result.error||'Failed')); })
        .catch(err => { btn.innerHTML=orig; btn.disabled=false; showNotification('error','Error: '+err.message); });
});

window.openEditVisit = function(data) {
    document.getElementById('ev_visit_id').value        = data.id;
    document.getElementById('ev_visit_date').value      = data.visit_date || '';
    document.getElementById('ev_chief_complaint').value = data.chief_complaint || '';
    document.getElementById('ev_notes').value           = data.notes || '';
    var vtSel = document.getElementById('ev_visit_type');
    var found = false;
    for (var i = 0; i < vtSel.options.length; i++) { if (vtSel.options[i].value === data.visit_type) { vtSel.selectedIndex = i; found = true; break; } }
    if (!found) { var opt = new Option(data.visit_type, data.visit_type, true, true); vtSel.add(opt); }
    document.getElementById('ev_doctor_id').value = data.doctor_id || '';
    var drugIds = (data.drug_ids || []).map(String);
    document.querySelectorAll('#editVisitModal input[name="prescribed_drugs[]"]').forEach(function(cb) {
        cb.checked = drugIds.includes(cb.value);
    });
    openModal('editVisitModal');
};

document.getElementById('editVisitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this); fd.append('action', 'update_visit');
    const btn = document.getElementById('saveEditVisitBtn'); const orig = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader"></i><span> Saving...</span>'; btn.disabled = true;
    fetch(AJAX_URL, { method:'POST', body:fd })
        .then(r => r.json()).then(result => { btn.innerHTML=orig; btn.disabled=false; if (result.success) { showNotification('success','Visit updated'); document.querySelector('[data-close="editVisitModal"]').click(); setTimeout(()=>location.reload(),500); } else showNotification('error','Error: '+(result.error||'Failed')); })
        .catch(err => { btn.innerHTML=orig; btn.disabled=false; showNotification('error','Error: '+err.message); });
});

document.getElementById('newVisitForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this); fd.append('action','add_visit');
    const btn = document.getElementById('saveVisitBtn'); const orig = btn.textContent;
    btn.textContent = 'Saving...'; btn.disabled = true;
    fetch(AJAX_URL, { method:'POST', body:fd })
        .then(r => r.json()).then(result => { btn.textContent=orig; btn.disabled=false; if (result.success) { showNotification('success','Visit added'); document.querySelector('[data-close="newVisitModal"]').click(); setTimeout(()=>location.reload(),500); } else showNotification('error','Error: '+(result.error||'Failed')); })
        .catch(err => { btn.textContent=orig; btn.disabled=false; showNotification('error','Error: '+err.message); });
});

document.getElementById('editPatientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this); fd.append('action','update_patient');
    ['sulfa_reactive','penicillin_allergy','latex_allergy'].forEach(name => { const cb = this.querySelector('input[name="'+name+'"]'); fd.set(name, (cb&&cb.checked)?1:0); });
    const btn = document.getElementById('savePatientBtn'); const orig = btn.textContent;
    btn.textContent = 'Saving...'; btn.disabled = true;
    fetch(AJAX_URL, { method:'POST', body:fd })
        .then(r => r.json()).then(result => { btn.textContent=orig; btn.disabled=false; if (result.success) { document.querySelector('[data-close="editPatientModal"]').click(); location.reload(); } else showNotification('error','Error: '+(result.error||'Failed')); })
        .catch(err => { btn.textContent=orig; btn.disabled=false; showNotification('error','Error: '+err.message); });
});

// ── Clinical Photos ───────────────────────────────────────────────
window.viewClinicalPhoto = function(url) {
    document.getElementById('photoViewerImg').src = url;
    openModal('photoViewerModal');
};

document.getElementById('uploadPhotoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this); fd.append('action', 'upload_clinical_photo');
    const btn = document.getElementById('uploadPhotoBtn'); const orig = btn.innerHTML;
    btn.innerHTML = '⏳ Uploading...'; btn.disabled = true;
    fetch(AJAX_URL, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
        .then(r => r.json()).then(result => {
            btn.innerHTML = orig; btn.disabled = false;
            if (result.success) { showNotification('success', 'Photo uploaded'); document.querySelector('[data-close="uploadPhotoModal"]').click(); setTimeout(() => location.reload(), 400); }
            else showNotification('error', 'Error: ' + (result.error || 'Failed'));
        }).catch(err => { btn.innerHTML = orig; btn.disabled = false; showNotification('error', err.message); });
});

window.deletePhoto = function(photoId, btn) {
    var card = btn.closest('.photo-card');
    var label = card.querySelector('.photo-card-type')?.textContent || 'this photo';
    showConfirm(
        'Delete "' + label + '"? This cannot be undone.',
        'Delete Photo',
        function(confirmed) {
            if (!confirmed) return;
            var fd = new FormData(); fd.append('action', 'delete_photo'); fd.append('photo_id', photoId);
            fetch(AJAX_URL, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
                .then(r => r.json()).then(result => {
                    if (result.success) { card.remove(); showNotification('success', 'Photo deleted'); }
                    else showNotification('error', result.error || 'Failed to delete');
                }).catch(err => showNotification('error', err.message));
        },
        { type: 'danger', icon: 'trash-2', okLabel: 'Delete' }
    );
};
</script>
