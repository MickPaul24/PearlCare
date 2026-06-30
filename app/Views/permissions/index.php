<div class="card">
    <form method="POST" action="<?php echo BASE_URL; ?>/permissions">
        <div class="form-row" style="align-items:flex-end;">
            <div class="form-group" style="flex:1;">
                <label class="form-label">Select Role</label>
                <select name="role" class="form-input" onchange="window.location.href='<?php echo BASE_URL; ?>/permissions?role='+encodeURIComponent(this.value)">
                    <?php if ($roles): while ($role = $roles->fetch_assoc()): ?>
                        <option value="<?php echo e($role['role_key']); ?>" <?php echo $role['role_key']===$currentRole?'selected':''; ?>><?php echo e($role['role_name']); ?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
        </div>

        <div class="table-wrap" style="margin-top:20px;">
            <table>
                <thead><tr><th>Module</th><th>View</th><th>Create</th><th>Update</th><th>Delete</th></tr></thead>
                <tbody>
                    <?php if ($pages): while ($page = $pages->fetch_assoc()):
                        $perm = $rolePerms[$page['id']] ?? ['can_view'=>0,'can_insert'=>0,'can_update'=>0,'can_delete'=>0];
                    ?>
                    <tr>
                        <td><?php echo e($page['label']); ?></td>
                        <td><input type="checkbox" name="permissions[<?php echo $page['id']; ?>][view]"   <?php echo $perm['can_view']  ?'checked':''; ?>></td>
                        <td><input type="checkbox" name="permissions[<?php echo $page['id']; ?>][insert]" <?php echo $perm['can_insert']?'checked':''; ?>></td>
                        <td><input type="checkbox" name="permissions[<?php echo $page['id']; ?>][update]" <?php echo $perm['can_update']?'checked':''; ?>></td>
                        <td><input type="checkbox" name="permissions[<?php echo $page['id']; ?>][delete]" <?php echo $perm['can_delete']?'checked':''; ?>></td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="modal-footer" style="justify-content:flex-start;margin-top:16px;padding:0;">
            <button type="submit" class="btn btn-primary">Save Permissions</button>
        </div>
    </form>
</div>
