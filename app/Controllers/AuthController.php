<?php
class AuthController extends Controller {

    public function redirect(): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
        } else {
            header('Location: ' . BASE_URL . '/login');
        }
        exit;
    }

    public function login(): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email']    ?? '');
            $password =      $_POST['password'] ?? '';
            $role     = trim($_POST['role']     ?? '');

            error_log(date('Y-m-d H:i:s') . " - POST received: email=$email, role=$role\n", 3, LOG_FILE);

            if ($email === '' || $password === '' || $role === '') {
                $error = 'Please enter your email, password, and role.';
            } else {
                $conn = db();
                $user = null;
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND is_active = 1 LIMIT 1");
                if ($stmt === false) {
                    $error = 'Unable to process login right now. Please try again later.';
                } else {
                    $stmt->bind_param("ss", $email, $role);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user']    = $user;
                    error_log(date('Y-m-d H:i:s') . " - Login successful: $email\n", 3, LOG_FILE);
                    session_write_close();
                    header('Location: ' . BASE_URL . '/dashboard');
                    exit;
                } else {
                    error_log(date('Y-m-d H:i:s') . " - Login failed: $email\n", 3, LOG_FILE);
                    $error = 'Incorrect email, role, or password. Please try again.';
                }
            }
        }

        $theme = getTheme();
        require __DIR__ . '/../../app/Views/auth/login.php';
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
