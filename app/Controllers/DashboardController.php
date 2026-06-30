<?php
class DashboardController extends Controller {

    public function index(): void {
        requireLogin();
        $conn = db();

        $userRole    = $_SESSION['user']['role'] ?? 'admin';
        $allSections = ['stats_cards','weekly_visits_chart','queue_donut','revenue_chart','lab_distribution_chart','queue_list','top_drugs','recent_patients','recent_tests_table'];

        // Ensure table + seed defaults (INSERT IGNORE won't overwrite customised values)
        $conn->query("CREATE TABLE IF NOT EXISTS role_dashboard_config (id INT AUTO_INCREMENT PRIMARY KEY,role_key VARCHAR(50) NOT NULL,section_key VARCHAR(100) NOT NULL,is_enabled TINYINT(1) NOT NULL DEFAULT 1,section_order INT NOT NULL DEFAULT 0,UNIQUE KEY unique_role_section (role_key,section_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $roleDefaults = [
            'admin'        => ['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>1,'revenue_chart'=>1,'lab_distribution_chart'=>1,'queue_list'=>1,'top_drugs'=>1,'recent_patients'=>1,'recent_tests_table'=>1],
            'doctor'       => ['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>1,'revenue_chart'=>0,'lab_distribution_chart'=>1,'queue_list'=>1,'top_drugs'=>1,'recent_patients'=>0,'recent_tests_table'=>1],
            'nurse'        => ['stats_cards'=>1,'weekly_visits_chart'=>0,'queue_donut'=>1,'revenue_chart'=>0,'lab_distribution_chart'=>1,'queue_list'=>1,'top_drugs'=>0,'recent_patients'=>1,'recent_tests_table'=>1],
            'receptionist' => ['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>1,'revenue_chart'=>0,'lab_distribution_chart'=>0,'queue_list'=>1,'top_drugs'=>0,'recent_patients'=>1,'recent_tests_table'=>0],
            'records'      => ['stats_cards'=>1,'weekly_visits_chart'=>1,'queue_donut'=>0,'revenue_chart'=>1,'lab_distribution_chart'=>1,'queue_list'=>0,'top_drugs'=>0,'recent_patients'=>1,'recent_tests_table'=>1],
        ];
        $ss = $conn->prepare("INSERT IGNORE INTO role_dashboard_config (role_key,section_key,is_enabled) VALUES (?,?,?)");
        foreach ($roleDefaults as $role => $secs) { foreach ($secs as $sec => $en) { $ss->bind_param('ssi',$role,$sec,$en); $ss->execute(); } }
        $ss->close();

        // Load config for current user's role
        $dashboardConfig = [];
        $sr = $conn->real_escape_string($userRole);
        $cr = $conn->query("SELECT section_key,is_enabled FROM role_dashboard_config WHERE role_key='$sr'");
        if ($cr) while ($row = $cr->fetch_assoc()) $dashboardConfig[$row['section_key']] = (bool)$row['is_enabled'];
        foreach ($allSections as $s) { if (!isset($dashboardConfig[$s])) $dashboardConfig[$s] = true; }

        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $thisMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        // ── Stat: Today's visits ──────────────────────────────────────
        $r = $conn->query("SELECT COUNT(*) AS c FROM visits WHERE DATE(visit_date)='$today'");
        $visitsToday = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) AS c FROM visits WHERE DATE(visit_date)='$yesterday'");
        $visitsYesterday = (int)$r->fetch_assoc()['c'];

        // ── Stat: Queue today ─────────────────────────────────────────
        $queueToday = ['Waiting'=>0,'In Consultation'=>0,'Completed'=>0];
        $r = $conn->query("SELECT queue_status, COUNT(*) AS c FROM queue WHERE DATE(check_in_time)='$today' GROUP BY queue_status");
        if ($r) while ($row = $r->fetch_assoc()) $queueToday[$row['queue_status']] = (int)$row['c'];
        $queueTotal = array_sum($queueToday);

        // ── Stat: Revenue ─────────────────────────────────────────────
        $r = $conn->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM finances WHERE DATE_FORMAT(created_at,'%Y-%m')='$thisMonth'");
        $revenueThis = (float)$r->fetch_assoc()['t'];
        $r = $conn->query("SELECT COALESCE(SUM(amount_paid),0) AS t FROM finances WHERE DATE_FORMAT(created_at,'%Y-%m')='$lastMonth'");
        $revenueLast = (float)$r->fetch_assoc()['t'];

        // ── Stat: Pending lab tests ───────────────────────────────────
        $r = $conn->query("SELECT COUNT(*) AS c FROM lab_tests WHERE result_status IN ('Pending','In Progress')");
        $pendingLabs = (int)$r->fetch_assoc()['c'];

        // ── Stat: New patients this month vs last ─────────────────────
        $r = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE DATE_FORMAT(created_at,'%Y-%m')='$thisMonth'");
        $newPatientsThis = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE DATE_FORMAT(created_at,'%Y-%m')='$lastMonth'");
        $newPatientsLast = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) AS c FROM patients WHERE status='Active'");
        $totalPatients = (int)$r->fetch_assoc()['c'];

        // ── Chart: Weekly visits (last 7 days) ────────────────────────
        $weeklyVisits = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $weeklyVisits[$d] = ['label' => date('D', strtotime("-$i days")), 'value' => 0, 'date' => $d];
        }
        $r = $conn->query("SELECT DATE(visit_date) AS d, COUNT(*) AS c FROM visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(visit_date)");
        if ($r) while ($row = $r->fetch_assoc()) if (isset($weeklyVisits[$row['d']])) $weeklyVisits[$row['d']]['value'] = (int)$row['c'];
        $weeklyVisits = array_values($weeklyVisits);

        // ── Chart: Revenue last 6 months ──────────────────────────────
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i months"));
            $monthlyRevenue[$key] = ['label' => date('M', strtotime("-$i months")), 'value' => 0];
        }
        $r = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, SUM(amount_paid) AS t FROM finances WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH) GROUP BY m ORDER BY m ASC");
        if ($r) while ($row = $r->fetch_assoc()) if (isset($monthlyRevenue[$row['m']])) $monthlyRevenue[$row['m']]['value'] = (float)$row['t'];
        $monthlyRevenue = array_values($monthlyRevenue);

        // ── Chart: Lab test status distribution ───────────────────────
        $labDistColors = ['Clear'=>'#16a34a','Pending'=>'#d97706','Reactive'=>'#dc2626','In Progress'=>'#2563eb','Cancelled'=>'#6b7280'];
        $labDist = [];
        $r = $conn->query("SELECT result_status, COUNT(*) AS c FROM lab_tests GROUP BY result_status");
        if ($r) while ($row = $r->fetch_assoc()) {
            $labDist[] = [
                'label' => $row['result_status'],
                'value' => (int)$row['c'],
                'color' => $labDistColors[$row['result_status']] ?? '#6b7280',
            ];
        }
        $labTotal = array_sum(array_column($labDist, 'value'));

        // ── Top prescribed drugs ──────────────────────────────────────
        $topDrugs = [];
        $r = $conn->query("SELECT d.name, COUNT(dp.id) AS cnt FROM drug_prescriptions dp JOIN drugs d ON d.id=dp.drug_id GROUP BY d.id, d.name ORDER BY cnt DESC LIMIT 6");
        if ($r) while ($row = $r->fetch_assoc()) $topDrugs[] = $row;

        // ── Recent lab tests ──────────────────────────────────────────
        $recentTests = $conn->query("SELECT lt.test_name, lt.result_status, lt.created_at, p.full_name AS patient_name, u.full_name AS doctor_name FROM lab_tests lt JOIN patients p ON p.id=lt.patient_id JOIN users u ON u.id=lt.doctor_id ORDER BY lt.created_at DESC LIMIT 6");

        // ── Recent patients ───────────────────────────────────────────
        $recentPatients = $conn->query("SELECT id, full_name, age, gender, status, created_at FROM patients ORDER BY created_at DESC LIMIT 5");

        // ── Today's queue list ────────────────────────────────────────
        $queueList = $conn->query("SELECT q.id, q.priority, q.queue_status, q.check_in_time, COALESCE(p.full_name, q.temp_full_name) AS full_name, COALESCE(v.visit_type, q.temp_visit_type) AS visit_type FROM queue q LEFT JOIN patients p ON p.id=q.patient_id LEFT JOIN visits v ON v.id=q.visit_id WHERE DATE(q.check_in_time)='$today' ORDER BY FIELD(q.queue_status,'In Consultation','Waiting','Completed'), q.check_in_time ASC LIMIT 8");

        $this->render('dashboard/index', [
            'pageTitle'       => 'Dashboard',
            'activePage'      => 'Dashboard',
            'dashboardConfig' => $dashboardConfig,
            // Stats
            'visitsToday'     => $visitsToday,
            'visitsYesterday' => $visitsYesterday,
            'queueToday'      => $queueToday,
            'queueTotal'      => $queueTotal,
            'revenueThis'     => $revenueThis,
            'revenueLast'     => $revenueLast,
            'pendingLabs'     => $pendingLabs,
            'newPatientsThis' => $newPatientsThis,
            'newPatientsLast' => $newPatientsLast,
            'totalPatients'   => $totalPatients,
            // Charts
            'weeklyVisits'    => $weeklyVisits,
            'monthlyRevenue'  => $monthlyRevenue,
            'labDist'         => $labDist,
            'labTotal'        => $labTotal,
            // Lists
            'topDrugs'        => $topDrugs,
            'recentTests'     => $recentTests,
            'recentPatients'  => $recentPatients,
            'queueList'       => $queueList,
        ]);
    }
}
