<?php
/** @var array $patient */
/** @var mysqli_result|false $visits */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Report — <?php echo e($patient['full_name']); ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #111; background: #fff; }
        @media screen { body { padding: 24px; max-width: 680px; margin: 0 auto; } }
        @media print { .no-print { display: none !important; } body { padding: 0; } }

        /* Screen-only action bar */
        .no-print { margin-bottom: 18px; }
        .btn-back { display: inline-block; padding: 8px 18px; background: #f3f4f6; color: #111; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; margin-right: 8px; }
        .btn-print { display: inline-block; padding: 8px 20px; background: #2563eb; color: #fff; border: none; border-radius: 7px; font-size: 13px; font-weight: 700; cursor: pointer; }

        /* Card */
        .card { border: 1.5px solid #555; padding: 22px 24px 18px; }

        /* Header */
        .clinic-header { text-align: center; margin-bottom: 14px; }
        .clinic-name { font-size: 22px; font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; }
        .clinic-address { font-size: 11.5px; margin-top: 3px; }
        .clinic-contact { font-size: 11.5px; margin-top: 1px; }

        /* No. row */
        .no-row { text-align: right; font-size: 12px; font-weight: 700; margin-bottom: 10px; }

        /* Patient info lines */
        .info-line { display: flex; align-items: baseline; gap: 0; margin-bottom: 6px; font-size: 12.5px; border-bottom: 1px dotted #888; padding-bottom: 3px; }
        .info-label { font-weight: 700; white-space: nowrap; margin-right: 4px; }
        .info-value { flex: 1; font-size: 12px; }
        .info-sep { display: inline-flex; align-items: baseline; gap: 0; }
        .age-sex { display: flex; gap: 28px; margin-left: auto; }
        .age-sex span { font-weight: 700; white-space: nowrap; margin-right: 4px; }
        .age-sex .val { min-width: 60px; border-bottom: 1px dotted #888; display: inline-block; font-size: 12px; }

        /* Visits table */
        .visits-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .visits-table th { border: 1.5px solid #444; padding: 5px 8px; font-size: 12px; font-weight: 700; text-align: center; background: #fff; }
        .visits-table th.col-date { width: 18%; }
        .visits-table th.col-doctor { width: 22%; }
        .visits-table td { border-left: 1.5px solid #444; border-right: 1.5px solid #444; padding: 0; min-height: 22px; font-size: 12px; vertical-align: top; }
        .visits-table td.cell-date { border-right: 1.5px solid #444; padding: 3px 6px; border-bottom: 1px dotted #aaa; }
        .visits-table td.cell-doctor { border-right: 1.5px solid #444; padding: 3px 6px; border-bottom: 1px dotted #aaa; }
        .visits-table td.cell-note { padding: 3px 8px; border-bottom: 1px dotted #aaa; }
        .visits-table tr:last-child td { border-bottom: 1.5px solid #444; }
        .empty-row td { color: #bbb; }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="<?php echo BASE_URL; ?>/patients/profile?id=<?php echo $patient['id']; ?>" class="btn-back">← Back to Profile</a>
        <button class="btn-print" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="card">
        <div class="clinic-header">
            <div class="clinic-name">Kampala Skin Clinic</div>
            <div class="clinic-address">Plot 160 Kasule Rd. Wandegeya</div>
            <div class="clinic-contact">P.O. Box 8487 Kampala &nbsp;Tel: 0414-535043, 0392-176904</div>
        </div>

        <div class="no-row">No. &nbsp;<?php echo e($patient['file_number']); ?></div>

        <div class="info-line">
            <span class="info-label">Name:</span>
            <span class="info-value"><?php echo e($patient['full_name']); ?></span>
            <div class="age-sex">
                <span>Age:</span><span class="val"><?php echo e($patient['age']); ?></span>
                <span>Sex:</span><span class="val"><?php echo e($patient['gender']); ?></span>
            </div>
        </div>
        <div class="info-line">
            <span class="info-label">Address:</span>
            <span class="info-value"><?php echo e($patient['residence']); ?></span>
            <div class="age-sex">
                <span>Tel.:</span><span class="val" style="min-width:100px"><?php echo e($patient['phone'] ?: '—'); ?></span>
            </div>
        </div>

        <table class="visits-table">
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-doctor">Doctor</th>
                    <th>Diagnosis / Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $visitRows = [];
                if ($visits) {
                    while ($v = $visits->fetch_assoc()) {
                        $visitRows[] = $v;
                    }
                }
                $totalRows = 20;
                foreach ($visitRows as $v):
                    $totalRows--;
                ?>
                <tr>
                    <td class="cell-date"><?php echo e(date('d/m/Y', strtotime($v['visit_date']))); ?></td>
                    <td class="cell-doctor"><?php echo e($v['doctor_name'] ?? '—'); ?></td>
                    <td class="cell-note">
                        <?php
                            $clinical = implode(' · ', array_filter([$v['chief_complaint'] ?? '', $v['notes'] ?? '']));
                            if ($clinical === '') $clinical = $v['visit_type'] ?? '';
                            echo e($clinical);
                            if (!empty($v['prescribed_drugs'])): ?>
                            <br><strong>Prescribed:</strong> <?php echo e($v['prescribed_drugs']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php for ($i = 0; $i < $totalRows; $i++): ?>
                <tr class="empty-row">
                    <td class="cell-date">&nbsp;</td>
                    <td class="cell-doctor">&nbsp;</td>
                    <td class="cell-note">&nbsp;</td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
