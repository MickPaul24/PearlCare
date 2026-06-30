<?php
class PermissionController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('permissions', 'view');
        $conn = db();

        $roles = $conn->query('SELECT role_key, role_name FROM roles ORDER BY role_name');
        $pages = $conn->query('SELECT id, page_key, label FROM pages ORDER BY label');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permissions'])) {
            requirePermission('permissions', 'update');
            $role = trim($_POST['role'] ?? '');
            if ($role && $roles) {
                $conn->query("DELETE FROM role_permissions WHERE role = '" . $conn->real_escape_string($role) . "'");
                foreach ($_POST['permissions'] as $pageId => $actions) {
                    $canView   = isset($actions['view'])   ? 1 : 0;
                    $canInsert = isset($actions['insert']) ? 1 : 0;
                    $canUpdate = isset($actions['update']) ? 1 : 0;
                    $canDelete = isset($actions['delete']) ? 1 : 0;
                    $stmt = $conn->prepare('INSERT INTO role_permissions (role,page_id,can_view,can_insert,can_update,can_delete) VALUES (?,?,?,?,?,?)');
                    $stmt->bind_param('siiiii', $role,$pageId,$canView,$canInsert,$canUpdate,$canDelete);
                    $stmt->execute();
                }
                setFlash('success', 'Permissions saved.');
                header('Location: ' . BASE_URL . '/permissions?role=' . urlencode($role));
                exit;
            }
        }

        $currentRole  = $_GET['role'] ?? 'admin';
        $selectedRole = $conn->real_escape_string($currentRole);
        $rolePerms    = [];
        $result = $conn->query("SELECT rp.page_id,rp.can_view,rp.can_insert,rp.can_update,rp.can_delete FROM role_permissions rp WHERE rp.role = '$selectedRole'");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rolePerms[$row['page_id']] = $row;
            }
        }

        // Re-query pages (cursor may be exhausted if POST happened)
        $pages = $conn->query('SELECT id, page_key, label FROM pages ORDER BY label');

        $this->render('permissions/index', [
            'pageTitle'   => 'Permissions',
            'pageSubtitle'=> 'Control access to modules and CRUD operations for each role.',
            'activePage'  => 'Permissions',
            'roles'       => $roles,
            'pages'       => $pages,
            'currentRole' => $currentRole,
            'rolePerms'   => $rolePerms,
        ]);
    }
}
