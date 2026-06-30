<?php
class SettingsController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('settings', 'view');
        $conn = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requirePermission('settings', 'update');
            $currency     = trim($_POST['currency'] ?? 'UGX');
            $records      = (int)($_POST['records_per_page'] ?? 25);
            $priorityFee  = trim($_POST['finance_amount_priority'] ?? '150000');
            $urgentFee    = trim($_POST['finance_amount_urgent'] ?? '75000');
            $routineFee   = trim($_POST['finance_amount_routine'] ?? '75000');
            $columns      = json_encode($_POST['visible_columns'] ?? []);
            $stmt = $conn->prepare('REPLACE INTO settings (`key`,`value`) VALUES (?,?),(?,?),(?,?),(?,?),(?,?),(?,?)');
            $k1='currency'; $v1=$currency;
            $k2='records_per_page'; $v2=(string)$records;
            $k3='visible_columns'; $v3=$columns;
            $k4='finance_amount_priority'; $v4=$priorityFee;
            $k5='finance_amount_urgent'; $v5=$urgentFee;
            $k6='finance_amount_routine'; $v6=$routineFee;
            $stmt->bind_param('ssssssssssss', $k1,$v1,$k2,$v2,$k3,$v3,$k4,$v4,$k5,$v5,$k6,$v6);
            $stmt->execute();
            setFlash('success', 'Settings updated successfully.');
            header('Location: ' . BASE_URL . '/settings');
            exit;
        }

        $currency             = getSetting('currency', 'UGX');
        $recordsPerPage       = getSetting('records_per_page', '25');
        $visibleColumns       = json_decode(getSetting('visible_columns', '[]'), true) ?: [];
        $financeAmountPriority= getSetting('finance_amount_priority', '150000');
        $financeAmountUrgent  = getSetting('finance_amount_urgent', '75000');
        $financeAmountRoutine = getSetting('finance_amount_routine', '75000');

        $this->render('settings/index', [
            'pageTitle'             => 'Settings',
            'pageSubtitle'          => 'Configure clinic currency, display columns, and record limits.',
            'activePage'            => 'Settings',
            'currency'              => $currency,
            'recordsPerPage'        => $recordsPerPage,
            'visibleColumns'        => $visibleColumns,
            'financeAmountPriority' => $financeAmountPriority,
            'financeAmountUrgent'   => $financeAmountUrgent,
            'financeAmountRoutine'  => $financeAmountRoutine,
        ]);
    }
}
