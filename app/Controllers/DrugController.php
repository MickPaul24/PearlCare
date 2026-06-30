<?php
class DrugController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('drugs', 'view');
        $conn = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'save_drug') {
                requirePermission('drugs', 'insert');
                $name         = trim($_POST['drug_name'] ?? '');
                $category     = trim($_POST['category'] ?? '');
                $description  = trim($_POST['description'] ?? '');
                $unit_price   = (float)($_POST['unit_price'] ?? 0);
                $stock_qty    = (int)($_POST['stock_qty'] ?? 0);
                $reorder_level= (int)($_POST['reorder_level'] ?? 10);
                $is_active    = isset($_POST['is_active']) ? 1 : 0;
                $stmt = $conn->prepare('INSERT INTO drugs (name,category,description,unit_price,stock_qty,reorder_level,is_active) VALUES (?,?,?,?,?,?,?)');
                $stmt->bind_param('sssdiii', $name,$category,$description,$unit_price,$stock_qty,$reorder_level,$is_active);
                $stmt->execute();
                setFlash('success', 'Drug added successfully.');
                header('Location: ' . BASE_URL . '/drugs'); exit;
            }

            if ($action === 'update_drug') {
                requirePermission('drugs', 'update');
                $drug_id      = (int)($_POST['drug_id'] ?? 0);
                $name         = trim($_POST['drug_name'] ?? '');
                $category     = trim($_POST['category'] ?? '');
                $description  = trim($_POST['description'] ?? '');
                $unit_price   = (float)($_POST['unit_price'] ?? 0);
                $stock_qty    = (int)($_POST['stock_qty'] ?? 0);
                $reorder_level= (int)($_POST['reorder_level'] ?? 10);
                $is_active    = isset($_POST['is_active']) ? 1 : 0;
                if ($drug_id) {
                    $stmt = $conn->prepare('UPDATE drugs SET name=?,category=?,description=?,unit_price=?,stock_qty=?,reorder_level=?,is_active=? WHERE id=?');
                    $stmt->bind_param('sssdiiis', $name,$category,$description,$unit_price,$stock_qty,$reorder_level,$is_active,$drug_id);
                    $stmt->execute();
                    setFlash('success', 'Drug updated successfully.');
                }
                header('Location: ' . BASE_URL . '/drugs'); exit;
            }

            if ($action === 'delete_drug') {
                requirePermission('drugs', 'delete');
                $drug_id = (int)($_POST['drug_id'] ?? 0);
                if ($drug_id) {
                    $stmt = $conn->prepare('DELETE FROM drugs WHERE id = ?');
                    $stmt->bind_param('i', $drug_id);
                    $stmt->execute();
                    setFlash('success', 'Drug deleted.');
                }
                header('Location: ' . BASE_URL . '/drugs'); exit;
            }
        }

        $search  = trim($_GET['q'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;
        $where   = '';
        if ($search !== '') {
            $like  = '%' . $conn->real_escape_string($search) . '%';
            $where = "WHERE name LIKE '$like' OR category LIKE '$like'";
        }
        $total      = $conn->query("SELECT COUNT(*) AS c FROM drugs $where")->fetch_assoc()['c'] ?? 0;
        $rows       = $conn->query("SELECT * FROM drugs $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
        $totalPages = max(1, ceil($total / $perPage));

        $this->render('drugs/index', [
            'pageTitle'   => 'Drugs',
            'pageSubtitle'=> 'Inventory, prescriptions, and drug effectiveness tracking.',
            'activePage'  => 'Drugs',
            'rows'        => $rows,
            'total'       => $total,
            'totalPages'  => $totalPages,
            'page'        => $page,
            'search'      => $search,
            'conn'        => $conn,
        ]);
    }
}
