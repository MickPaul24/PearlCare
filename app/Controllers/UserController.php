<?php
class UserController extends Controller {

    public function index(): void {
        requireLogin();
        requirePermission('users', 'view');
        $conn = db();

        $getValidRoles = function() use ($conn): array {
            $validRoles = [];
            $q = $conn->query('SELECT role_key FROM roles');
            if ($q) while ($r = $q->fetch_assoc()) $validRoles[] = $r['role_key'];
            return $validRoles;
        };

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create_user') {
                requirePermission('users', 'insert');
                $fullName = trim($_POST['full_name'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role     = trim($_POST['role'] ?? 'receptionist');
                if (!in_array($role, $getValidRoles())) { setFlash('error', 'Invalid role.'); header('Location: ' . BASE_URL . '/users'); exit; }
                if ($fullName && $email && $password) {
                    try {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('INSERT INTO users (full_name,email,password_hash,role,is_active) VALUES (?,?,?,?,1)');
                        $stmt->bind_param('ssss', $fullName,$email,$hash,$role);
                        $stmt->execute();
                        setFlash('success', 'User account created.');
                    } catch (mysqli_sql_exception $e) {
                        setFlash('error', str_contains($e->getMessage(),'Duplicate') ? 'Email already registered.' : 'Error: '.$e->getMessage());
                    }
                }
                header('Location: ' . BASE_URL . '/users'); exit;
            }

            if ($action === 'update_user') {
                requirePermission('users', 'update');
                $userId   = (int)($_POST['user_id'] ?? 0);
                $fullName = trim($_POST['full_name'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $role     = trim($_POST['role'] ?? '');
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $password = $_POST['password'] ?? '';
                if (!in_array($role, $getValidRoles())) { setFlash('error', 'Invalid role.'); header('Location: ' . BASE_URL . '/users'); exit; }
                if ($userId && $fullName && $email && $role) {
                    try {
                        if ($password) {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare('UPDATE users SET full_name=?,email=?,password_hash=?,role=?,is_active=? WHERE id=?');
                            $stmt->bind_param('ssssii', $fullName,$email,$hash,$role,$isActive,$userId);
                        } else {
                            $stmt = $conn->prepare('UPDATE users SET full_name=?,email=?,role=?,is_active=? WHERE id=?');
                            $stmt->bind_param('sssii', $fullName,$email,$role,$isActive,$userId);
                        }
                        $stmt->execute();
                        setFlash('success', 'User account updated.');
                    } catch (mysqli_sql_exception $e) {
                        setFlash('error', str_contains($e->getMessage(),'Duplicate') ? 'Email already in use.' : 'Error: '.$e->getMessage());
                    }
                }
                header('Location: ' . BASE_URL . '/users'); exit;
            }

            if ($action === 'delete_user') {
                requirePermission('users', 'delete');
                $userId = (int)($_POST['user_id'] ?? 0);
                if ($userId) {
                    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    setFlash('success', 'User account deleted.');
                }
                header('Location: ' . BASE_URL . '/users'); exit;
            }
        }

        $search  = trim($_GET['q'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;
        $where   = '';
        if ($search !== '') {
            $like  = '%' . $conn->real_escape_string($search) . '%';
            $where = "WHERE full_name LIKE '$like' OR email LIKE '$like'";
        }
        $total      = $conn->query("SELECT COUNT(*) AS c FROM users $where")->fetch_assoc()['c'] ?? 0;
        $rows       = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
        $totalPages = max(1, ceil($total / $perPage));

        $rolesQuery  = $conn->query('SELECT role_key, role_name FROM roles ORDER BY role_name');
        $roleOptions = [];
        if ($rolesQuery) while ($r = $rolesQuery->fetch_assoc()) $roleOptions[] = $r;

        $this->render('users/index', [
            'pageTitle'   => 'Users',
            'pageSubtitle'=> 'Create clinic users and manage staff accounts.',
            'activePage'  => 'Users',
            'rows'        => $rows,
            'total'       => $total,
            'totalPages'  => $totalPages,
            'page'        => $page,
            'search'      => $search,
            'roleOptions' => $roleOptions,
            'conn'        => $conn,
        ]);
    }
}
