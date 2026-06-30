<div class="stats-grid">
    <div class="card"><div class="stat-label">Revenue</div><div class="stat-value">UGX <?php echo number_format($revenue); ?></div></div>
    <div class="card"><div class="stat-label">Due</div><div class="stat-value">UGX <?php echo number_format($due); ?></div></div>
    <div class="card"><div class="stat-label">Paid Invoices</div><div class="stat-value"><?php echo number_format($paidCount); ?></div></div>
    <div class="card"><div class="stat-label">Total Records</div><div class="stat-value"><?php echo number_format($total); ?></div></div>
</div>

<div class="card" style="margin-bottom:20px;">
    <h3>Current Fee Settings</h3>
    <div class="stats-grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:12px;">
        <div class="card"><div class="stat-label">Priority Fee</div><div class="stat-value">UGX <?php echo number_format((float)$financeAmountPriority); ?></div></div>
        <div class="card"><div class="stat-label">Urgent Fee</div><div class="stat-value">UGX <?php echo number_format((float)$financeAmountUrgent); ?></div></div>
        <div class="card"><div class="stat-label">Routine Fee</div><div class="stat-value">UGX <?php echo number_format((float)$financeAmountRoutine); ?></div></div>
    </div>
</div>

<div class="table-wrap">
    <div class="table-head">
        <h3>Invoices</h3>
        <form method="GET" action="<?php echo BASE_URL; ?>/finances" style="display:flex;gap:10px;align-items:center;">
            <input class="form-input" type="search" name="q" placeholder="Search invoice or patient" value="<?php echo e($search); ?>" style="width:220px;">
            <button class="btn btn-outline btn-sm"><i data-lucide="search"></i>Search</button>
        </form>
    </div>
    <table>
        <thead><tr><th>Invoice</th><th>Patient</th><th>Category</th><th>Amount Paid</th><th>Amount Due</th><th>Status</th></tr></thead>
        <tbody>
            <?php if ($rows && $rows->num_rows): while ($row = $rows->fetch_assoc()): ?>
            <tr>
                <td><?php echo e($row['invoice_number']); ?></td>
                <td><?php echo e($row['patient_name']); ?></td>
                <td><?php echo e($row['category']??'Unknown'); ?></td>
                <td>UGX <?php echo number_format($row['amount_paid']); ?></td>
                <td>UGX <?php echo number_format($row['amount_due']); ?></td>
                <td><?php echo $row['paid'] ? '<span class="status-pill status-Completed">Paid</span>' : '<span class="status-pill status-Pending">Due</span>'; ?></td>
            </tr>
            <?php endwhile; else: ?><tr><td colspan="6">No financial records found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a class="page-num" href="?page=<?php echo $page-1; ?>&q=<?php echo urlencode($search); ?>">‹</a><?php endif; ?>
        <?php for ($i=1;$i<=$totalPages;$i++): ?><?php if($i===$page): ?><span class="page-num current"><?php echo $i;?></span><?php else: ?><a class="page-num" href="?page=<?php echo $i;?>&q=<?php echo urlencode($search);?>"><?php echo $i;?></a><?php endif;?><?php endfor; ?>
        <?php if ($page < $totalPages): ?><a class="page-num" href="?page=<?php echo $page+1; ?>&q=<?php echo urlencode($search); ?>">›</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
