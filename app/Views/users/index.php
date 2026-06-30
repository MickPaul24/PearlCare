<div class="stats-grid">
    <div class="card"><div class="stat-label">Total Accounts</div><div class="stat-value"><?php echo number_format($total); ?></div></div>
    <div class="card"><div class="stat-label">Active Roles</div><div class="stat-value"><?php echo number_format(count($roleOptions)); ?></div></div>
    <div class="card"><div class="stat-label">Active Users</div><div class="stat-value"><?php echo number_format($conn->query('SELECT COUNT(*) AS c FROM users WHERE is_active=1')->fetch_assoc()['c']??0); ?></div></div>
    <div class="card"><div class="stat-label">Inactive</div><div class="stat-value"><?php echo number_format($conn->query('SELECT COUNT(*) AS c FROM users WHERE is_active=0')->fetch_assoc()['c']??0); ?></div></div>
</div>

<div class="table-wrap">
    <div class="table-head">
        <h3>Staff Accounts</h3>
        <div class="tbl-actions">
            <form method="GET" action="<?php echo BASE_URL; ?>/users" style="display:flex;gap:10px;align-items:center;">
                <input class="form-input" type="search" name="q" placeholder="Search name or email" value="<?php echo e($search); ?>" style="width:220px;">
                <button class="btn btn-outline btn-sm"><i data-lucide="search"></i> <span>Search</span></button>
            </form>
            <button class="btn btn-primary" data-open="addUserModal"><i data-lucide="plus"></i> <span>Add User</span></button>
        </div>
    </div>
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if ($rows && $rows->num_rows): while ($row = $rows->fetch_assoc()): ?>
            <tr>
                <td><?php echo e($row['full_name']); ?></td>
                <td><?php echo e($row['email']); ?></td>
                <td><?php echo e(ucfirst($row['role'])); ?></td>
                <td><?php echo $row['is_active'] ? '<span class="status-pill status-Active">Active</span>' : '<span class="status-pill status-Pending">Inactive</span>'; ?></td>
                <td><?php echo e(date('M j, Y', strtotime($row['created_at']))); ?></td>
                <td>
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-outline btn-sm" data-open="editUserModal"
                            data-user-id="<?php echo $row['id']; ?>"
                            data-user-name="<?php echo e($row['full_name']); ?>"
                            data-user-email="<?php echo e($row['email']); ?>"
                            data-user-role="<?php echo e($row['role']); ?>"
                            data-user-active="<?php echo $row['is_active']; ?>">Edit</button>
                        <form method="POST" action="<?php echo BASE_URL; ?>/users" style="display:inline;" data-confirm="Are you sure you want to delete this user?">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-outline btn-delete btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; else: ?><tr><td colspan="6">No user accounts found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a class="page-num" href="?page=<?php echo $page-1;?>&q=<?php echo urlencode($search);?>">‹</a><?php endif; ?>
        <?php for($i=1;$i<=$totalPages;$i++): ?><?php if($i===$page): ?><span class="page-num current"><?php echo $i;?></span><?php else: ?><a class="page-num" href="?page=<?php echo $i;?>&q=<?php echo urlencode($search);?>"><?php echo $i;?></a><?php endif;?><?php endfor;?>
        <?php if ($page < $totalPages): ?><a class="page-num" href="?page=<?php echo $page+1;?>&q=<?php echo urlencode($search);?>">›</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="user-plus" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Create New User</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Set up a staff account and assign a role</div></div></div>
            <button class="modal-close" data-close="addUserModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/users">
            <input type="hidden" name="action" value="create_user">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-input" required></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-input" required><?php foreach($roleOptions as $role): ?><option value="<?php echo e($role['role_key']);?>"><?php echo e($role['role_name']);?></option><?php endforeach;?></select></div>
                </div>
                <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-input" required minlength="6"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" data-close="addUserModal">Cancel</button><button type="submit" class="btn btn-primary">Create Account</button></div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="user-cog" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Edit User</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Update account details</div></div></div>
            <button class="modal-close" data-close="editUserModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/users">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" id="edit_full_name" class="form-input" required></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Role</label><select name="role" id="edit_role" class="form-input" required><?php foreach($roleOptions as $role): ?><option value="<?php echo e($role['role_key']);?>"><?php echo e($role['role_name']);?></option><?php endforeach;?></select></div>
                </div>
                <div class="form-group"><label class="form-label">New Password (leave blank to keep current)</label><input type="password" name="password" class="form-input" minlength="6"></div>
                <div class="form-group"><label class="check-label"><input type="checkbox" name="is_active" id="edit_is_active" value="1"> Account is active</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" data-close="editUserModal">Cancel</button><button type="submit" class="btn btn-primary">Update Account</button></div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('[data-open="editUserModal"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_user_id').value   = this.dataset.userId;
        document.getElementById('edit_full_name').value = this.dataset.userName;
        document.getElementById('edit_email').value     = this.dataset.userEmail;
        document.getElementById('edit_role').value      = this.dataset.userRole;
        document.getElementById('edit_is_active').checked = this.dataset.userActive === '1';
    });
});
</script>
