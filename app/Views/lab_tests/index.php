<div class="stats-grid">
    <div class="card"><div class="stat-label">Total Tests</div><div class="stat-value"><?php echo number_format($total); ?></div></div>
    <div class="card"><div class="stat-label">Pending</div><div class="stat-value"><?php echo number_format($conn->query("SELECT COUNT(*) AS c FROM lab_tests WHERE result_status='Pending'")->fetch_assoc()['c']??0); ?></div></div>
    <div class="card"><div class="stat-label">Completed</div><div class="stat-value"><?php echo number_format($conn->query("SELECT COUNT(*) AS c FROM lab_tests WHERE result_status='Completed'")->fetch_assoc()['c']??0); ?></div></div>
    <div class="card"><div class="stat-label">With Files</div><div class="stat-value"><?php echo number_format($hasFilePath ? ($conn->query("SELECT COUNT(*) AS c FROM lab_tests WHERE {$fileColumn} IS NOT NULL")->fetch_assoc()['c']??0) : 0); ?></div></div>
</div>

<div class="table-wrap">
    <div class="table-head">
        <h3>Lab Tests</h3>
        <div class="tbl-actions">
            <form method="GET" action="<?php echo BASE_URL; ?>/lab-tests" style="display:flex;gap:10px;align-items:center;">
                <input class="form-input" type="search" name="q" placeholder="Search patient or test name" value="<?php echo e($search); ?>" style="width:220px;">
                <button class="btn btn-outline btn-sm"><i data-lucide="search"></i> <span>Search</span></button>
            </form>
            <button class="btn btn-primary" data-open="addTestModal"><i data-lucide="plus"></i> <span>Add Test</span></button>
        </div>
    </div>
    <table>
        <thead><tr><th>Patient</th><th>Test Name</th><th>Status</th><th>Doctor</th><th>Date</th><th>File</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if ($rows && $rows->num_rows): while ($row = $rows->fetch_assoc()): ?>
            <tr>
                <td>
                    <a href="<?php echo BASE_URL; ?>/patients/profile?id=<?php echo $row['patient_id']; ?>" style="color:var(--primary);font-weight:600;"><?php echo e($row['full_name']); ?></a><br>
                    <span style="font-size:.75rem;color:var(--text-muted);">#<?php echo e($row['file_number']); ?></span>
                </td>
                <td><?php echo e($row['test_name']); ?></td>
                <td><span class="status-pill status-<?php echo $row['result_status']; ?>"><?php echo e($row['result_status']); ?></span></td>
                <td><?php echo e($row['doctor_name'] ?? 'N/A'); ?></td>
                <td><?php echo e(date('M j, Y', strtotime($row['created_at']))); ?></td>
                <td>
                    <?php $fp = $row['file_path'] ?? ''; if ($fp): ?>
                        <button type="button" class="btn btn-outline btn-sm" style="padding:4px 8px;" onclick="openFilePreview('<?php echo BASE_URL . '/' . e($fp); ?>')" title="Preview"><i data-lucide="eye" style="width:14px;height:14px;"></i><span style="margin-left:4px;">Open</span></button>
                    <?php else: ?><span style="color:var(--text-faint);">—</span><?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="editTest(<?php echo $row['id']; ?>,'<?php echo e($row['result_status']); ?>','<?php echo e(addslashes($row['result_notes'])); ?>')">Edit</button>
                    <?php if (canDo('lab_tests','delete')): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/lab-tests" style="display:inline;" onsubmit="event.preventDefault();confirmDeleteLabTest(this);">
                            <input type="hidden" name="action" value="delete_lab_test">
                            <input type="hidden" name="test_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="7">No lab tests recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&q=<?php echo e($search); ?>">&laquo; Previous</a><?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?><span class="current"><?php echo $i; ?></span>
        <?php else: ?><a href="<?php echo BASE_URL; ?>/lab-tests?page=<?php echo $i; ?>&q=<?php echo e($search); ?>"><?php echo $i; ?></a><?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page+1; ?>&q=<?php echo e($search); ?>">Next &raquo;</a><?php endif; ?>
</div>
<?php endif; ?>

<!-- Add Test Modal -->
<div class="modal-overlay" id="addTestModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);margin:-26px -26px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="flask-conical" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Add Lab Test Result</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Record a new patient laboratory test</div></div></div>
            <button class="modal-close" data-close="addTestModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/lab-tests" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_lab_test">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-input" required>
                        <option value="">Select a patient…</option>
                        <?php if ($patients_result) { $patients_result->data_seek(0); while ($p = $patients_result->fetch_assoc()): ?><option value="<?php echo $p['id']; ?>"><?php echo e($p['full_name']); ?></option><?php endwhile; } ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Test Name *</label><input type="text" name="test_name" class="form-input" placeholder="e.g., Blood Work, Skin Culture" required></div>
                <div class="form-group"><label class="form-label">Result Status *</label>
                    <select name="result_status" class="form-input" required>
                        <option value="">Select status…</option>
                        <option>Pending</option>
                        <option>In Progress</option>
                        <option>Clear</option>
                        <option>Reactive</option>
                        <option>Cancelled</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Test File (PDF/Image, max 5MB)</label><input type="file" name="test_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.gif"></div>
                <div class="form-group"><label class="form-label">Notes</label><textarea name="result_notes" class="form-input" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close="addTestModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Test</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Test Modal -->
<div class="modal-overlay" id="editTestModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="pencil" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Update Lab Test</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Edit status and result notes</div></div></div>
            <button class="modal-close" data-close="editTestModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/lab-tests">
            <input type="hidden" name="action" value="update_lab_test">
            <input type="hidden" name="test_id" id="edit_test_id">
            <div class="modal-body">
                <div class="form-group"><label>Status</label><select name="result_status" id="edit_status" class="form-input"><option>Pending</option><option>In Progress</option><option>Completed</option></select></div>
                <div class="form-group"><label>Result Notes</label><textarea name="result_notes" id="edit_notes" class="form-input" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" data-close="editTestModal">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>

<!-- File Preview Modal -->
<div class="modal-overlay" id="filePreviewModal">
    <div class="modal-box" style="max-width:900px;width:95%;">
        <div style="background:linear-gradient(135deg,#475569 0%,#334155 100%);margin:-26px -26px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="file-search" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">View File</div></div></div>
            <button type="button" id="closeFilePreviewBtn" style="color:#fff;opacity:.75;font-size:1.5rem;background:none;border:none;cursor:pointer;">×</button>
        </div>
        <div class="modal-body viewer-body" style="padding:0;display:flex;justify-content:center;align-items:center;"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" id="downloadFilePreviewBtn">Download</button>
            <button type="button" class="btn btn-primary" id="closeFilePreviewBtnFooter">Close</button>
        </div>
    </div>
</div>

<script>
function editTest(testId, status, notes) { document.getElementById('edit_test_id').value=testId; document.getElementById('edit_status').value=status; document.getElementById('edit_notes').value=notes; openModal('editTestModal'); }

function confirmDeleteLabTest(form) { showConfirm('Delete this lab test?','Confirm',function(ok){ if(ok) form.submit(); }); }

function openFilePreview(url) {
    var modal = document.getElementById('filePreviewModal');
    var body  = modal.querySelector('.viewer-body');
    var ext   = (url.split('.').pop() || '').toLowerCase();
    body.innerHTML = ['jpg','jpeg','png','gif','webp'].includes(ext)
        ? '<img src="'+url+'" style="max-width:100%;max-height:70vh;display:block;margin:0 auto;">'
        : '<iframe src="'+url+'" style="width:100%;height:70vh;border:0;"></iframe>';
    document.getElementById('downloadFilePreviewBtn').onclick = function(){ window.open(url,'_blank'); };
    openModal('filePreviewModal');
}
function closeFilePreview() {
    var modal = document.getElementById('filePreviewModal');
    closeModal('filePreviewModal');
    setTimeout(function(){ modal.querySelector('.viewer-body').innerHTML = ''; }, 300);
}
document.getElementById('closeFilePreviewBtn').addEventListener('click', closeFilePreview);
document.getElementById('closeFilePreviewBtnFooter').addEventListener('click', closeFilePreview);

document.addEventListener('DOMContentLoaded', function() {
    var flash = document.querySelector('.flash');
    if (flash) { var m = flash.className.match(/flash-(\w+)/); if (typeof showNotification==='function') { showNotification(m?m[1]:'success', flash.textContent.trim(), 5000); flash.parentNode.removeChild(flash); } }
});
</script>
