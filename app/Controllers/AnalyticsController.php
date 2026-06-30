<?php
class AnalyticsController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('analytics', 'view');
        $conn = db();

        $totalPatients  = $conn->query('SELECT COUNT(*) AS c FROM patients')->fetch_assoc()['c'] ?? 0;
        $totalRevenue   = $conn->query('SELECT COALESCE(SUM(amount_paid),0) AS c FROM finances')->fetch_assoc()['c'] ?? 0;
        $pendingLabTests= $conn->query("SELECT COUNT(*) AS c FROM lab_tests WHERE result_status IN ('Pending','In Progress')")->fetch_assoc()['c'] ?? 0;
        $activeQueue    = $conn->query("SELECT COUNT(*) AS c FROM queue WHERE queue_status='Waiting'")->fetch_assoc()['c'] ?? 0;
        $topDrugs       = $conn->query("SELECT d.name AS drug_name, COUNT(dp.id) AS uses FROM drug_prescriptions dp JOIN drugs d ON d.id=dp.drug_id GROUP BY dp.drug_id ORDER BY uses DESC LIMIT 5");
        $recentLab      = $conn->query("SELECT lt.id AS lab_test_id, lt.test_name, lt.result_status, p.full_name, lt.created_at FROM lab_tests lt JOIN patients p ON p.id=lt.patient_id ORDER BY lt.created_at DESC LIMIT 5");

        $this->render('analytics/index', [
            'pageTitle'       => 'Analytics',
            'pageSubtitle'    => 'Operational metrics and prescription insights for PearlCare.',
            'activePage'      => 'Analytics',
            'totalPatients'   => $totalPatients,
            'totalRevenue'    => $totalRevenue,
            'pendingLabTests' => $pendingLabTests,
            'activeQueue'     => $activeQueue,
            'topDrugs'        => $topDrugs,
            'recentLab'       => $recentLab,
        ]);
    }
}
