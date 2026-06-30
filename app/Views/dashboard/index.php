<?php
/** @var int    $visitsToday     */
/** @var int    $visitsYesterday */
/** @var array  $queueToday      */
/** @var int    $queueTotal      */
/** @var float  $revenueThis     */
/** @var float  $revenueLast     */
/** @var int    $pendingLabs     */
/** @var int    $newPatientsThis */
/** @var int    $newPatientsLast */
/** @var int    $totalPatients   */
/** @var array  $weeklyVisits    */
/** @var array  $monthlyRevenue  */
/** @var array  $labDist         */
/** @var int    $labTotal        */
/** @var array  $topDrugs        */
/** @var mysqli_result|false $recentTests    */
/** @var mysqli_result|false $recentPatients */
/** @var mysqli_result|false $queueList      */
/** @var array  $dashboardConfig */

// ── Chart helpers ─────────────────────────────────────────────────────────────
function dashBarChart(array $data, string $fillColor = '#2563eb', string $labelColor = '#9ca3af'): string {
    $n   = count($data);
    if (!$n) return '';
    $W = 340; $H = 130; $padL = 8; $padR = 8; $padT = 18; $padB = 22;
    $innerW = $W - $padL - $padR;
    $innerH = $H - $padT - $padB;
    $gap    = max(3, (int)($innerW / $n * 0.2));
    $bw     = ($innerW - $gap * ($n - 1)) / $n;
    $maxV   = max(array_column($data, 'value') ?: [1]) ?: 1;

    $svg = "<svg viewBox=\"0 0 $W $H\" width=\"100%\" height=\"130\" xmlns=\"http://www.w3.org/2000/svg\">";
    // Grid lines
    for ($g = 0; $g <= 3; $g++) {
        $gy = $padT + $innerH - ($innerH * $g / 3);
        $svg .= "<line x1=\"$padL\" y1=\"$gy\" x2=\"" . ($W-$padR) . "\" y2=\"$gy\" stroke=\"#e5e7eb\" stroke-width=\"0.5\"/>";
    }
    // Bars + labels
    foreach ($data as $i => $d) {
        $x   = $padL + $i * ($bw + $gap);
        $bh  = $innerH * ($d['value'] / $maxV);
        $by  = $padT + $innerH - $bh;
        $cx  = $x + $bw / 2;
        $lbl = htmlspecialchars($d['label'] ?? '');
        $val = $d['value'];

        $svg .= "<rect x=\"$x\" y=\"$by\" width=\"$bw\" height=\"$bh\" rx=\"3\" fill=\"$fillColor\" opacity=\"0.88\"/>";
        $svg .= "<text x=\"$cx\" y=\"" . ($H - 5) . "\" text-anchor=\"middle\" font-size=\"8\" fill=\"$labelColor\">$lbl</text>";
        if ($val > 0)
            $svg .= "<text x=\"$cx\" y=\"" . ($by - 3) . "\" text-anchor=\"middle\" font-size=\"8\" font-weight=\"600\" fill=\"$fillColor\">$val</text>";
    }
    $svg .= "</svg>";
    return $svg;
}

function dashRevenueChart(array $data, string $fillColor = '#0ea5e9'): string {
    $n   = count($data);
    if (!$n) return '';
    $W = 340; $H = 130; $padL = 8; $padR = 8; $padT = 18; $padB = 22;
    $innerW = $W - $padL - $padR;
    $innerH = $H - $padT - $padB;
    $gap    = max(3, (int)($innerW / $n * 0.22));
    $bw     = ($innerW - $gap * ($n - 1)) / $n;
    $maxV   = max(array_column($data, 'value') ?: [1]) ?: 1;

    $svg = "<svg viewBox=\"0 0 $W $H\" width=\"100%\" height=\"130\" xmlns=\"http://www.w3.org/2000/svg\">";
    for ($g = 0; $g <= 3; $g++) {
        $gy = $padT + $innerH - ($innerH * $g / 3);
        $svg .= "<line x1=\"$padL\" y1=\"$gy\" x2=\"" . ($W-$padR) . "\" y2=\"$gy\" stroke=\"#e5e7eb\" stroke-width=\"0.5\"/>";
    }
    foreach ($data as $i => $d) {
        $x   = $padL + $i * ($bw + $gap);
        $bh  = $innerH * ($d['value'] / $maxV);
        $by  = $padT + $innerH - $bh;
        $cx  = $x + $bw / 2;
        $lbl = htmlspecialchars($d['label'] ?? '');
        $val = $d['value'] >= 1000 ? number_format($d['value']/1000, 0).'k' : number_format($d['value']);

        $svg .= "<rect x=\"$x\" y=\"$by\" width=\"$bw\" height=\"$bh\" rx=\"3\" fill=\"$fillColor\" opacity=\"0.85\"/>";
        $svg .= "<text x=\"$cx\" y=\"" . ($H - 5) . "\" text-anchor=\"middle\" font-size=\"8\" fill=\"#9ca3af\">$lbl</text>";
        if ($d['value'] > 0)
            $svg .= "<text x=\"$cx\" y=\"" . ($by - 3) . "\" text-anchor=\"middle\" font-size=\"7.5\" font-weight=\"600\" fill=\"$fillColor\">$val</text>";
    }
    $svg .= "</svg>";
    return $svg;
}

function dashDonut(array $segments, string $center = '', string $sub = ''): string {
    $total = array_sum(array_column($segments, 'value')) ?: 1;
    $r = 38; $cx = 50; $cy = 50; $c = 2 * M_PI * $r;
    $svg = "<svg viewBox=\"0 0 100 100\" width=\"130\" height=\"130\" xmlns=\"http://www.w3.org/2000/svg\">";
    $svg .= "<circle cx=\"$cx\" cy=\"$cy\" r=\"$r\" fill=\"none\" stroke=\"#f0f2f8\" stroke-width=\"11\"/>";
    $offset = -$c / 4;
    foreach ($segments as $seg) {
        if ($seg['value'] <= 0) continue;
        $dash = ($seg['value'] / $total) * $c;
        $color = htmlspecialchars($seg['color']);
        $svg .= "<circle cx=\"$cx\" cy=\"$cy\" r=\"$r\" fill=\"none\" stroke=\"$color\" stroke-width=\"11\" stroke-dasharray=\"" . round($dash,2) . " $c\" stroke-dashoffset=\"" . round($offset,2) . "\" stroke-linecap=\"butt\"/>";
        $offset -= $dash;
    }
    if ($center !== '') {
        $svg .= "<text x=\"$cx\" y=\"47\" text-anchor=\"middle\" font-size=\"14\" font-weight=\"700\" fill=\"currentColor\">" . htmlspecialchars($center) . "</text>";
        $svg .= "<text x=\"$cx\" y=\"57\" text-anchor=\"middle\" font-size=\"6\" fill=\"#9ca3af\">" . strtoupper(htmlspecialchars($sub)) . "</text>";
    }
    $svg .= "</svg>";
    return $svg;
}

function pctChange(int|float $now, int|float $prev): array {
    if ($prev == 0) return ['pct' => null, 'up' => true];
    $pct = round(($now - $prev) / $prev * 100, 1);
    return ['pct' => $pct, 'up' => $pct >= 0];
}

$visitChange  = pctChange($visitsToday, $visitsYesterday);
$revenueChange = pctChange($revenueThis, $revenueLast);
$patientChange = pctChange($newPatientsThis, $newPatientsLast);

$priorityColors = ['Urgent'=>'#dc2626','Priority'=>'#d97706','Routine'=>'#6b7280'];
$queueColors    = ['Waiting'=>'#d97706','In Consultation'=>'#2563eb','Completed'=>'#16a34a'];
$topDrugMax     = !empty($topDrugs) ? max(array_column($topDrugs,'cnt')) : 1;

// ── Config shortcuts ─────────────────────────────────────────────────────────
$cfg = $dashboardConfig ?? [];
$c = fn(string $k) => (bool)($cfg[$k] ?? true);

$showWeekly   = $c('weekly_visits_chart');
$showQueueDot = $c('queue_donut');
$showRevenue  = $c('revenue_chart');
$showLabDist  = $c('lab_distribution_chart');
$showQueueLst = $c('queue_list');
$showDrugs    = $c('top_drugs');
$showRecPts   = $c('recent_patients');

$chartRow1    = $showWeekly || $showQueueDot;
$chartRow2    = $showRevenue || $showLabDist;
$showRight    = $showDrugs || $showRecPts;
$showBottom   = $showQueueLst || $showRight;

$userName = $_SESSION['user']['full_name'] ?? 'there';
$firstName = explode(' ', $userName)[0];
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>

<style>
/* ── Dashboard layout ── */
.db-welcome{background:linear-gradient(135deg,#2563eb 0%,#0ea5e9 100%);border-radius:var(--radius);padding:22px 28px;color:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;overflow:hidden;position:relative;}
.db-welcome::after{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;background:rgba(255,255,255,.07);border-radius:50%;}
.db-welcome::before{content:'';position:absolute;right:60px;bottom:-60px;width:160px;height:160px;background:rgba(255,255,255,.05);border-radius:50%;}
.db-welcome-title{font-size:1.25rem;font-weight:700;margin-bottom:4px;}
.db-welcome-sub{font-size:.85rem;opacity:.82;}
.db-welcome-date{text-align:right;font-size:.8rem;opacity:.75;line-height:1.7;}

/* ── Stat cards ── */
.db-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:22px;}
.db-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px 16px;position:relative;overflow:hidden;}
.db-stat-icon{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;margin-bottom:12px;}
.db-stat-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);font-weight:700;margin-bottom:4px;}
.db-stat-value{font-size:1.6rem;font-weight:800;line-height:1;margin-bottom:6px;color:var(--text);}
.db-stat-sub{font-size:.72rem;color:var(--text-muted);display:flex;align-items:center;gap:4px;}
.db-badge{display:inline-flex;align-items:center;gap:3px;font-size:.7rem;font-weight:700;padding:2px 7px;border-radius:50px;}
.db-badge-up{background:#dcfce7;color:#16a34a;}
.db-badge-down{background:#fef2f2;color:#dc2626;}
.db-badge-neutral{background:#f3f4f6;color:#6b7280;}
.db-stat-bar{position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 0 var(--radius);}

/* ── Charts grid ── */
.db-charts{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.db-chart-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;}
.db-chart-title{font-size:.88rem;font-weight:700;color:var(--text);margin-bottom:2px;}
.db-chart-sub{font-size:.73rem;color:var(--text-muted);margin-bottom:14px;}

/* ── Donut layout ── */
.db-donut-wrap{display:flex;align-items:center;gap:20px;}
.db-donut-legend{flex:1;}
.db-donut-row{display:flex;align-items:center;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--border);font-size:.78rem;}
.db-donut-row:last-child{border-bottom:none;}
.db-donut-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-right:7px;}

/* ── Bottom grid ── */
.db-bottom{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.db-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:20px;}
.db-section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.db-section-title{font-size:.88rem;font-weight:700;color:var(--text);}

/* ── Queue list ── */
.db-queue-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);}
.db-queue-item:last-child{border-bottom:none;}
.db-queue-avatar{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;font-size:.72rem;font-weight:700;flex-shrink:0;}
.db-queue-name{font-size:.83rem;font-weight:600;}
.db-queue-meta{font-size:.72rem;color:var(--text-muted);}
.db-queue-status{margin-left:auto;flex-shrink:0;}

/* ── Drug bars ── */
.db-drug-row{margin-bottom:10px;}
.db-drug-name{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:3px;}
.db-drug-bar-bg{height:7px;background:var(--border);border-radius:50px;}
.db-drug-bar-fill{height:7px;border-radius:50px;background:linear-gradient(90deg,#2563eb,#0ea5e9);}

/* ── Patients list ── */
.db-patient-row{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--border);}
.db-patient-row:last-child{border-bottom:none;}
.db-patient-av{width:30px;height:30px;border-radius:50%;background:var(--primary-bg);color:var(--primary);font-size:.7rem;font-weight:700;display:grid;place-items:center;flex-shrink:0;}
.db-patient-name{font-size:.82rem;font-weight:600;}
.db-patient-meta{font-size:.7rem;color:var(--text-muted);}

/* ── Recent tests table ── */
.db-tests-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.db-tests-table th{padding:8px 10px;text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);font-weight:700;border-bottom:2px solid var(--border);}
.db-tests-table td{padding:10px 10px;border-bottom:1px solid var(--border);vertical-align:middle;}
.db-tests-table tbody tr:last-child td{border-bottom:none;}
.db-tests-table tbody tr:hover{background:var(--surface-alt,#f8fafc);}
.db-av{width:28px;height:28px;border-radius:50%;background:var(--primary-bg);color:var(--primary);font-size:.65rem;font-weight:700;display:inline-grid;place-items:center;flex-shrink:0;}

@media(max-width:1100px){.db-stats{grid-template-columns:repeat(3,1fr);}}
@media(max-width:860px){.db-stats{grid-template-columns:1fr 1fr;}.db-charts,.db-bottom{grid-template-columns:1fr;}}
@media(max-width:640px){.db-welcome{flex-direction:column;align-items:flex-start;gap:12px;padding:18px 18px;}.db-welcome-date{text-align:left;}.db-stats{grid-template-columns:1fr;}.db-donut-wrap{flex-direction:column;align-items:flex-start;gap:12px;}.db-queue-item{align-items:flex-start;}.db-queue-status{margin-left:0;margin-top:4px;}.db-tests-table{display:block;overflow-x:auto;-webkit-overflow-scrolling:touch;}.db-tests-table th,.db-tests-table td{white-space:nowrap;}}
</style>

<!-- ── Welcome banner ──────────────────────────────────────────── -->
<div class="db-welcome">
    <div style="position:relative;z-index:1;">
        <div class="db-welcome-title"><?php echo $greeting; ?>, <?php echo e($firstName); ?>!</div>
        <div class="db-welcome-sub">Here's what's happening at Kampala Skin Clinic today.</div>
    </div>
    <div class="db-welcome-date" style="position:relative;z-index:1;">
        <div style="font-size:1rem;font-weight:700;"><?php echo date('l'); ?></div>
        <div><?php echo date('F j, Y'); ?></div>
        <div id="db-clock" style="font-size:.95rem;font-weight:600;margin-top:2px;"></div>
    </div>
</div>

<!-- ── Stat cards ─────────────────────────────────────────────── -->
<?php if ($c('stats_cards')): ?>
<div class="db-stats">

    <!-- Visits today -->
    <div class="db-stat">
        <div class="db-stat-icon" style="background:#eff6ff;"><i data-lucide="users" style="width:18px;height:18px;color:#2563eb;"></i></div>
        <div class="db-stat-label">Patients Today</div>
        <div class="db-stat-value"><?php echo $visitsToday; ?></div>
        <div class="db-stat-sub">
            <?php if ($visitChange['pct'] !== null): ?>
            <span class="db-badge <?php echo $visitChange['up'] ? 'db-badge-up' : 'db-badge-down'; ?>">
                <i data-lucide="<?php echo $visitChange['up'] ? 'trending-up' : 'trending-down'; ?>" style="width:10px;height:10px;"></i>
                <?php echo abs($visitChange['pct']); ?>%
            </span>
            <span>vs yesterday</span>
            <?php else: ?><span style="color:var(--text-faint);">No data yet</span><?php endif; ?>
        </div>
        <div class="db-stat-bar" style="width:<?php echo $visitsToday > 0 ? min(100, $visitsToday * 5) : 5; ?>%;background:#2563eb;"></div>
    </div>

    <!-- Queue today -->
    <div class="db-stat">
        <div class="db-stat-icon" style="background:#fefce8;"><i data-lucide="list-ordered" style="width:18px;height:18px;color:#ca8a04;"></i></div>
        <div class="db-stat-label">Queue Today</div>
        <div class="db-stat-value"><?php echo $queueTotal; ?></div>
        <div class="db-stat-sub" style="flex-wrap:wrap;gap:4px;">
            <span style="color:#d97706;">⬤ <?php echo $queueToday['Waiting']; ?> waiting</span>
            <span style="color:#2563eb;">⬤ <?php echo $queueToday['In Consultation']; ?> consulting</span>
        </div>
        <div class="db-stat-bar" style="width:<?php echo $queueTotal > 0 ? min(100, $queueToday['Completed'] / max($queueTotal,1) * 100) : 5; ?>%;background:#16a34a;"></div>
    </div>

    <!-- Revenue -->
    <div class="db-stat">
        <div class="db-stat-icon" style="background:#f0fdf4;"><i data-lucide="banknote" style="width:18px;height:18px;color:#16a34a;"></i></div>
        <div class="db-stat-label">Revenue This Month</div>
        <div class="db-stat-value" style="font-size:1.2rem;">UGX&nbsp;<?php echo number_format($revenueThis); ?></div>
        <div class="db-stat-sub">
            <?php if ($revenueChange['pct'] !== null): ?>
            <span class="db-badge <?php echo $revenueChange['up'] ? 'db-badge-up' : 'db-badge-down'; ?>">
                <i data-lucide="<?php echo $revenueChange['up'] ? 'trending-up' : 'trending-down'; ?>" style="width:10px;height:10px;"></i>
                <?php echo abs($revenueChange['pct']); ?>%
            </span>
            <span>vs last month</span>
            <?php else: ?><span style="color:var(--text-faint);">First month</span><?php endif; ?>
        </div>
        <div class="db-stat-bar" style="width:<?php echo $revenueLast > 0 ? min(100, $revenueThis / max($revenueLast,1) * 80) : 20; ?>%;background:#16a34a;"></div>
    </div>

    <!-- Pending labs -->
    <div class="db-stat">
        <div class="db-stat-icon" style="background:#fff7ed;"><i data-lucide="flask-conical" style="width:18px;height:18px;color:#ea580c;"></i></div>
        <div class="db-stat-label">Pending Lab Tests</div>
        <div class="db-stat-value"><?php echo $pendingLabs; ?></div>
        <div class="db-stat-sub">
            <?php if ($pendingLabs > 0): ?>
            <span class="db-badge db-badge-neutral">Awaiting results</span>
            <?php else: ?>
            <span class="db-badge db-badge-up">All clear</span>
            <?php endif; ?>
        </div>
        <div class="db-stat-bar" style="width:<?php echo $pendingLabs > 0 ? min(100, $pendingLabs * 3) : 5; ?>%;background:#ea580c;"></div>
    </div>

    <!-- New patients -->
    <div class="db-stat">
        <div class="db-stat-icon" style="background:#f5f3ff;"><i data-lucide="user-plus" style="width:18px;height:18px;color:#7c3aed;"></i></div>
        <div class="db-stat-label">New Patients</div>
        <div class="db-stat-value"><?php echo $newPatientsThis; ?></div>
        <div class="db-stat-sub">
            <?php if ($patientChange['pct'] !== null): ?>
            <span class="db-badge <?php echo $patientChange['up'] ? 'db-badge-up' : 'db-badge-down'; ?>">
                <i data-lucide="<?php echo $patientChange['up'] ? 'trending-up' : 'trending-down'; ?>" style="width:10px;height:10px;"></i>
                <?php echo abs($patientChange['pct']); ?>%
            </span>
            <span>this month · <?php echo number_format($totalPatients); ?> total</span>
            <?php else: ?>
            <span style="color:var(--text-faint);"><?php echo number_format($totalPatients); ?> total patients</span>
            <?php endif; ?>
        </div>
        <div class="db-stat-bar" style="width:<?php echo $newPatientsThis > 0 ? min(100, $newPatientsThis * 5) : 5; ?>%;background:#7c3aed;"></div>
    </div>

</div>
<?php endif; ?>

<!-- ── Charts row 1: Weekly visits | Queue donut ─────────────── -->
<?php if ($chartRow1): ?>
<div class="db-charts" style="<?php echo (!$showWeekly || !$showQueueDot) ? 'grid-template-columns:1fr;' : ''; ?>">

<?php if ($showWeekly): ?>
    <div class="db-chart-card">
        <div class="db-chart-title">Weekly Patient Visits</div>
        <div class="db-chart-sub">Consultations recorded over the last 7 days</div>
        <?php echo dashBarChart($weeklyVisits, '#2563eb'); ?>
    </div>
<?php endif; ?>

<?php if ($showQueueDot): ?>
    <div class="db-chart-card">
        <div class="db-chart-title">Today's Queue</div>
        <div class="db-chart-sub">Real-time patient flow breakdown</div>
        <?php
        $queueSegments = [
            ['label'=>'Waiting',        'value'=>$queueToday['Waiting'],        'color'=>'#d97706'],
            ['label'=>'In Consultation', 'value'=>$queueToday['In Consultation'],'color'=>'#2563eb'],
            ['label'=>'Completed',       'value'=>$queueToday['Completed'],      'color'=>'#16a34a'],
        ];
        ?>
        <div class="db-donut-wrap">
            <?php echo dashDonut($queueSegments, (string)$queueTotal, 'total'); ?>
            <div class="db-donut-legend">
                <?php foreach ($queueSegments as $seg): ?>
                <div class="db-donut-row">
                    <span style="display:flex;align-items:center;"><span class="db-donut-dot" style="background:<?php echo $seg['color']; ?>"></span><?php echo $seg['label']; ?></span>
                    <span style="font-weight:700;"><?php echo $seg['value']; ?></span>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:10px;font-size:.7rem;color:var(--text-muted);">
                    <?php $donePct = $queueTotal > 0 ? round($queueToday['Completed']/$queueTotal*100) : 0; ?>
                    <span class="db-badge db-badge-up"><?php echo $donePct; ?>% completed</span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

</div>
<?php endif; ?>

<!-- ── Charts row 2: Revenue | Lab distribution ──────────────── -->
<?php if ($chartRow2): ?>
<div class="db-charts" style="margin-bottom:16px;<?php echo (!$showRevenue || !$showLabDist) ? 'grid-template-columns:1fr;' : ''; ?>">

<?php if ($showRevenue): ?>
    <div class="db-chart-card">
        <div class="db-chart-title">Revenue Trend</div>
        <div class="db-chart-sub">Monthly revenue (UGX) — last 6 months</div>
        <?php echo dashRevenueChart($monthlyRevenue, '#0ea5e9'); ?>
    </div>
<?php endif; ?>

<?php if ($showLabDist): ?>
    <div class="db-chart-card">
        <div class="db-chart-title">Lab Test Results</div>
        <div class="db-chart-sub">All-time result status distribution</div>
        <div class="db-donut-wrap">
            <?php echo dashDonut($labDist, (string)$labTotal, 'total tests'); ?>
            <div class="db-donut-legend">
                <?php foreach ($labDist as $seg): ?>
                <div class="db-donut-row">
                    <span style="display:flex;align-items:center;"><span class="db-donut-dot" style="background:<?php echo $seg['color']; ?>"></span><?php echo e($seg['label']); ?></span>
                    <span style="font-weight:700;"><?php echo $labTotal > 0 ? round($seg['value']/$labTotal*100) : 0; ?>%</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($labDist)): ?><div style="color:var(--text-muted);font-size:.8rem;">No lab tests yet.</div><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

</div>
<?php endif; ?>

<!-- ── Bottom row: Queue list | Top drugs + Recent patients ──── -->
<?php if ($showBottom): ?>
<div class="db-bottom" style="margin-bottom:16px;<?php echo (!$showQueueLst || !$showRight) ? 'grid-template-columns:1fr;' : ''; ?>">

<?php if ($showQueueLst): ?>
    <!-- Today's queue list -->
    <div class="db-section">
        <div class="db-section-head">
            <div class="db-section-title">Today's Queue</div>
            <a href="<?php echo BASE_URL; ?>/queue" class="btn btn-outline btn-sm">View Queue</a>
        </div>
        <?php
        $hasQueue = false;
        if ($queueList) {
            while ($qi = $queueList->fetch_assoc()):
                $hasQueue = true;
                $initials = strtoupper(substr($qi['full_name'] ?? '?', 0, 2));
                $sc = $qi['queue_status'] === 'In Consultation' ? '#2563eb' : ($qi['queue_status'] === 'Completed' ? '#16a34a' : '#d97706');
                $bg = $qi['queue_status'] === 'In Consultation' ? '#eff6ff' : ($qi['queue_status'] === 'Completed' ? '#f0fdf4' : '#fefce8');
                $pc = $priorityColors[$qi['priority']] ?? '#6b7280';
        ?>
        <div class="db-queue-item">
            <div class="db-queue-avatar" style="background:<?php echo $bg; ?>;color:<?php echo $sc; ?>;"><?php echo $initials; ?></div>
            <div style="min-width:0;">
                <div class="db-queue-name"><?php echo e($qi['full_name']); ?></div>
                <div class="db-queue-meta"><?php echo e($qi['visit_type'] ?: '—'); ?> · <span style="color:<?php echo $pc; ?>;font-weight:600;"><?php echo e($qi['priority']); ?></span></div>
            </div>
            <div class="db-queue-status">
                <span class="status-pill status-<?php echo strtolower(str_replace(' ','-',$qi['queue_status'])); ?>" style="font-size:.68rem;"><?php echo e($qi['queue_status']); ?></span>
            </div>
        </div>
        <?php endwhile; }
        if (!$hasQueue): ?>
        <div style="text-align:center;padding:24px;color:var(--text-muted);font-size:.85rem;">No patients in queue today.</div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($showRight): ?>
    <!-- Top drugs + Recent patients -->
    <div style="display:grid;gap:16px;">

<?php if ($showDrugs): ?>
        <!-- Top prescribed drugs -->
        <div class="db-section" style="padding-bottom:14px;">
            <div class="db-section-head">
                <div class="db-section-title">Top Prescribed Drugs</div>
                <a href="<?php echo BASE_URL; ?>/drugs" class="btn btn-outline btn-sm">Drugs</a>
            </div>
            <?php if (!empty($topDrugs)): ?>
            <?php foreach ($topDrugs as $drug): ?>
            <div class="db-drug-row">
                <div class="db-drug-name">
                    <span><?php echo e($drug['name']); ?></span>
                    <span style="font-weight:700;color:var(--primary);"><?php echo $drug['cnt']; ?></span>
                </div>
                <div class="db-drug-bar-bg">
                    <div class="db-drug-bar-fill" style="width:<?php echo $topDrugMax > 0 ? round($drug['cnt']/$topDrugMax*100) : 0; ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?><div style="color:var(--text-muted);font-size:.82rem;text-align:center;padding:12px 0;">No prescriptions recorded yet.</div><?php endif; ?>
        </div>
<?php endif; ?>

<?php if ($showRecPts): ?>
        <!-- Recent patients -->
        <div class="db-section" style="padding-bottom:14px;">
            <div class="db-section-head">
                <div class="db-section-title">Recent Patients</div>
                <a href="<?php echo BASE_URL; ?>/patients" class="btn btn-outline btn-sm">All Patients</a>
            </div>
            <?php
            $hasPatients = false;
            if ($recentPatients) while ($rp = $recentPatients->fetch_assoc()):
                $hasPatients = true;
                $init = strtoupper(substr($rp['full_name'], 0, 2));
            ?>
            <div class="db-patient-row">
                <div class="db-patient-av"><?php echo $init; ?></div>
                <div style="min-width:0;">
                    <div class="db-patient-name"><?php echo e($rp['full_name']); ?></div>
                    <div class="db-patient-meta"><?php echo $rp['age']; ?> yrs · <?php echo e($rp['gender']); ?> · <?php echo date('M d', strtotime($rp['created_at'])); ?></div>
                </div>
                <span class="status-pill status-<?php echo strtolower($rp['status']); ?>" style="margin-left:auto;font-size:.68rem;"><?php echo e($rp['status']); ?></span>
            </div>
            <?php endwhile;
            if (!$hasPatients): ?><div style="color:var(--text-muted);font-size:.82rem;text-align:center;padding:12px 0;">No patients yet.</div><?php endif; ?>
        </div>
<?php endif; ?>

    </div>
<?php endif; ?>

</div>
<?php endif; ?>

<!-- ── Recent lab tests ────────────────────────────────────────── -->
<?php if ($c('recent_tests_table')): ?>
<div class="db-section">
    <div class="db-section-head">
        <div class="db-section-title">Recent Lab Tests</div>
        <a href="<?php echo BASE_URL; ?>/lab-tests" class="btn btn-outline btn-sm">View All</a>
    </div>
    <table class="db-tests-table">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Test</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $hasTests = false;
            if ($recentTests) while ($rt = $recentTests->fetch_assoc()):
                $hasTests = true;
                $init = strtoupper(substr($rt['patient_name'], 0, 2));
                $sc = strtolower(str_replace(' ','-',$rt['result_status']));
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="db-av"><?php echo $init; ?></div>
                        <span style="font-weight:600;"><?php echo e($rt['patient_name']); ?></span>
                    </div>
                </td>
                <td><?php echo e($rt['test_name']); ?></td>
                <td style="color:var(--text-muted);">Dr. <?php echo e($rt['doctor_name']); ?></td>
                <td><span class="status-pill status-<?php echo $sc; ?>" style="font-size:.72rem;"><?php echo e($rt['result_status']); ?></span></td>
                <td style="color:var(--text-muted);white-space:nowrap;font-size:.78rem;"><?php echo date('M d, Y · h:i A', strtotime($rt['created_at'])); ?></td>
            </tr>
            <?php endwhile;
            if (!$hasTests): ?>
            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-muted);">No lab tests recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
// Live clock
(function tick() {
    var el = document.getElementById('db-clock');
    if (el) {
        var now = new Date();
        el.textContent = now.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    setTimeout(tick, 1000);
})();
</script>
