<style>
    @media(max-width:768px){.form-row{grid-template-columns:1fr;}}
</style>

<div class="page-top">
    <div>
        <div class="breadcrumb">INVENTORY › <span>DRUGS</span></div>
        <h1 class="page-title">Drug Management</h1>
        <p class="page-subtitle"><?php echo e($pageSubtitle); ?></p>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if (canDo('drugs','insert')): ?>
        <button class="btn btn-primary" data-open="addDrugModal"><i data-lucide="plus"></i> <span>Add Drug</span></button>
        <?php endif; ?>
    </div>
</div>

<div class="stats-grid">
    <div class="card"><div class="stat-label">Total Drugs</div><div class="stat-value"><?php echo number_format($total); ?></div></div>
    <div class="card"><div class="stat-label">Most Used</div><div class="stat-value"><?php echo number_format($conn->query('SELECT COUNT(*) AS c FROM drug_prescriptions')->fetch_assoc()['c']??0); ?></div></div>
    <div class="card"><div class="stat-label">Stock Value</div><div class="stat-value">UGX <?php echo number_format($conn->query('SELECT COALESCE(SUM(unit_price*stock_qty),0) AS c FROM drugs')->fetch_assoc()['c']??0); ?></div></div>
    <div class="card"><div class="stat-label">Out of Stock</div><div class="stat-value"><?php echo number_format($conn->query("SELECT COUNT(*) AS c FROM drugs WHERE stock_qty<=0")->fetch_assoc()['c']??0); ?></div></div>
</div>

<div class="table-wrap">
    <div class="table-head">
        <h3>Drug Inventory</h3>
        <div class="tbl-actions">
            <form method="GET" action="<?php echo BASE_URL; ?>/drugs" style="display:flex;gap:8px;">
                <input class="form-input" type="search" name="q" placeholder="Search drug name or SKU…" value="<?php echo e($search ?? ''); ?>" style="width:220px;">
                <button type="submit" class="btn btn-outline btn-sm"><i data-lucide="search"></i> <span>Search</span></button>
                <?php if (!empty($search)): ?><a href="<?php echo BASE_URL; ?>/drugs" class="btn btn-outline btn-sm"><i data-lucide="x"></i> Clear</a><?php endif; ?>
            </form>
        </div>
    </div>
    <table>
        <thead><tr><th>ID</th><th>Drug Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if ($rows && $rows->num_rows): while ($drug = $rows->fetch_assoc()): ?>
            <tr>
                <td><?php echo e($drug['id']); ?></td>
                <td><?php echo e($drug['name']); ?></td>
                <td><?php echo e($drug['category']??'—'); ?></td>
                <td>UGX <?php echo number_format($drug['unit_price']); ?></td>
                <td><?php echo number_format($drug['stock_qty']); ?></td>
                <td><span class="status-pill status-<?php echo $drug['is_active']?'Active':'Archived'; ?>"><?php echo $drug['is_active']?'Active':'Inactive'; ?></span></td>
                <td style="display:flex;gap:6px;">
                    <?php if (canDo('drugs','update')): ?><button class="btn btn-outline btn-sm" onclick="editDrug(<?php echo htmlspecialchars(json_encode($drug)); ?>)">Edit</button><?php endif; ?>
                    <?php if (canDo('drugs','delete')): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/drugs" style="display:inline;" class="delete-drug-form">
                            <input type="hidden" name="action" value="delete_drug">
                            <input type="hidden" name="drug_id" value="<?php echo $drug['id']; ?>">
                            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?><tr><td colspan="7">No drugs found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&q=<?php echo urlencode($search); ?>" class="page-num">‹</a><?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?><?php if ($i===$page): ?><span class="page-num current"><?php echo $i; ?></span><?php else: ?><a class="page-num" href="?page=<?php echo $i; ?>&q=<?php echo urlencode($search); ?>"><?php echo $i; ?></a><?php endif; ?><?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page+1; ?>&q=<?php echo urlencode($search); ?>" class="page-num">›</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Drug Modal -->
<div class="modal-overlay" id="addDrugModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="pill" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Add New Drug</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Enter drug details and stock information</div></div></div>
            <button class="modal-close" data-close="addDrugModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/drugs">
            <input type="hidden" name="action" value="save_drug">
            <div class="modal-body">
                <div class="form-row"><div class="form-group"><label class="form-label">Drug Name *</label><input type="text" name="drug_name" class="form-input" required></div><div class="form-group"><label class="form-label">Category</label><input type="text" name="category" class="form-input" placeholder="e.g., Antibiotic, Skincare"></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Unit Price (UGX) *</label><input type="number" name="unit_price" class="form-input" required step="0.01" min="0"></div><div class="form-group"><label class="form-label">Stock Quantity *</label><input type="number" name="stock_qty" class="form-input" required min="0"></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" class="form-input" value="10" min="0"></div><div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-input" rows="2"></textarea></div></div>
                <div class="form-group"><label class="form-label"><input type="checkbox" name="is_active" checked> Active</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" data-close="addDrugModal">Cancel</button><button type="submit" class="btn btn-primary">Save Drug</button></div>
        </form>
    </div>
</div>

<!-- Edit Drug Modal -->
<div class="modal-overlay" id="editDrugModal">
    <div class="modal-box">
        <div style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);margin:-24px -24px 24px;padding:22px 24px;border-radius:var(--radius-sm) var(--radius-sm) 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:rgba(255,255,255,0.18);border-radius:12px;display:grid;place-items:center;flex-shrink:0;"><i data-lucide="pencil" style="width:20px;height:20px;color:#fff;"></i></div><div><div style="color:#fff;font-size:1.05rem;font-weight:700;">Edit Drug</div><div style="color:rgba(255,255,255,0.72);font-size:.78rem;margin-top:2px;">Update drug details and stock levels</div></div></div>
            <button class="modal-close" data-close="editDrugModal" style="color:#fff;opacity:.75;font-size:1.5rem;">×</button>
        </div>
        <form method="POST" action="<?php echo BASE_URL; ?>/drugs">
            <input type="hidden" name="action" value="update_drug">
            <input type="hidden" name="drug_id" id="edit_drug_id">
            <div class="modal-body">
                <div class="form-row"><div class="form-group"><label class="form-label">Drug Name *</label><input type="text" name="drug_name" id="edit_drug_name" class="form-input" required></div><div class="form-group"><label class="form-label">Category</label><input type="text" name="category" id="edit_category" class="form-input"></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Unit Price (UGX) *</label><input type="number" name="unit_price" id="edit_unit_price" class="form-input" required step="0.01" min="0"></div><div class="form-group"><label class="form-label">Stock Quantity *</label><input type="number" name="stock_qty" id="edit_stock_qty" class="form-input" required min="0"></div></div>
                <div class="form-row"><div class="form-group"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" id="edit_reorder_level" class="form-input" min="0"></div><div class="form-group"><label class="form-label">Description</label><textarea name="description" id="edit_description" class="form-input" rows="2"></textarea></div></div>
                <div class="form-group"><label class="form-label"><input type="checkbox" name="is_active" id="edit_is_active"> Active</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" data-close="editDrugModal">Cancel</button><button type="submit" class="btn btn-primary">Update Drug</button></div>
        </form>
    </div>
</div>

<script>
function editDrug(drug) {
    document.getElementById('edit_drug_id').value = drug.id;
    document.getElementById('edit_drug_name').value = drug.name;
    document.getElementById('edit_category').value = drug.category||'';
    document.getElementById('edit_unit_price').value = drug.unit_price;
    document.getElementById('edit_stock_qty').value = drug.stock_qty;
    document.getElementById('edit_reorder_level').value = drug.reorder_level;
    document.getElementById('edit_description').value = drug.description||'';
    document.getElementById('edit_is_active').checked = drug.is_active===1||drug.is_active==='1';
    openModal('editDrugModal');
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-drug-form').forEach(function(form) {
        form.addEventListener('submit', function(ev) {
            ev.preventDefault();
            showConfirm('Delete this drug?','Confirm Delete',function(ok){ if(ok) form.submit(); });
        });
    });
});
</script>
