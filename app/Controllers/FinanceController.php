<?php
class FinanceController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('finances', 'view');
        $conn = db();

        $search  = trim($_GET['q'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;
        $where   = '';
        if ($search !== '') {
            $like  = '%' . $conn->real_escape_string($search) . '%';
            $where = "WHERE invoice_number LIKE '$like' OR patient_name LIKE '$like'";
        }
        $total      = $conn->query("SELECT COUNT(*) AS c FROM finances $where")->fetch_assoc()['c'] ?? 0;
        $rows       = $conn->query("SELECT * FROM finances $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
        $totalPages = max(1, ceil($total / $perPage));

        $revenue  = $conn->query('SELECT COALESCE(SUM(amount_paid),0) AS total FROM finances')->fetch_assoc()['total'] ?? 0;
        $due      = $conn->query('SELECT COALESCE(SUM(amount_due),0) AS total FROM finances WHERE paid=0')->fetch_assoc()['total'] ?? 0;
        $paidCount= $conn->query('SELECT COUNT(*) AS c FROM finances WHERE paid=1')->fetch_assoc()['c'] ?? 0;
        $financeAmountPriority = getSetting('finance_amount_priority', '150000');
        $financeAmountUrgent   = getSetting('finance_amount_urgent', '75000');
        $financeAmountRoutine  = getSetting('finance_amount_routine', '75000');

        $this->render('finances/index', [
            'pageTitle'             => 'Finances',
            'pageSubtitle'          => 'Track invoices, receipts, and clinic revenue in one view.',
            'activePage'            => 'Finances',
            'rows'                  => $rows,
            'total'                 => $total,
            'totalPages'            => $totalPages,
            'page'                  => $page,
            'search'                => $search,
            'revenue'               => $revenue,
            'due'                   => $due,
            'paidCount'             => $paidCount,
            'financeAmountPriority' => $financeAmountPriority,
            'financeAmountUrgent'   => $financeAmountUrgent,
            'financeAmountRoutine'  => $financeAmountRoutine,
        ]);
    }
}
