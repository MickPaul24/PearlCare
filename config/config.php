<?php
// ---- DATABASE CREDENTIALS --------------------------------
define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', 'animation2026?');
define('DB_NAME', 'kampala_skin_clinic');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('LOG_FILE',   __DIR__ . '/../login.log');

// ---- BASE URL (auto-detected, works under any sub-folder) ----
if (!defined('BASE_URL')) {
    function detectBaseUrl(): string {
        $appRoot = str_replace('\\', '/', dirname(__DIR__));
        $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

        if ($documentRoot !== '' && strpos($appRoot, $documentRoot) === 0) {
            $relativePath = substr($appRoot, strlen($documentRoot));
            $relativeDir = trim(str_replace('\\', '/', $relativePath), '/');
            if ($relativeDir !== '') {
                return '/' . $relativeDir;
            }
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '/index.php';
        $scriptName = str_replace('\\', '/', $scriptName);
        $scriptDir = rtrim(dirname($scriptName), '/');
        $scriptDir = $scriptDir === '.' ? '' : $scriptDir;

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestUri = str_replace('\\', '/', $requestUri);
        $requestUriPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';

        if ($scriptDir !== '' && strpos($requestUriPath, $scriptDir) === 0) {
            return $scriptDir;
        }

        $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
        if ($documentRoot !== '' && $scriptFile !== '' && strpos($scriptFile, $documentRoot) === 0) {
            $relativePath = substr($scriptFile, strlen($documentRoot));
            $relativeDir = str_replace('\\', '/', dirname($relativePath));
            $relativeDir = $relativeDir === '.' ? '' : rtrim($relativeDir, '/');
            if ($relativeDir !== '') {
                return '/' . ltrim($relativeDir, '/');
            }
        }

        return '';
    }

    define('BASE_URL', detectBaseUrl());
}

// ---- MYSQLI CONNECTION -----------------------------------
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="font-family:sans-serif;padding:40px;color:#b91c1c;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;max-width:500px;margin:60px auto;"><h2>Database Connection Failed</h2><p>' . $conn->connect_error . '</p></div>');
        }
        $conn->set_charset('utf8mb4');
        ensureOptionalSchema($conn);
    }
    return $conn;
}

function ensureOptionalSchema(mysqli $conn): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $queueColumns = [
        'temp_file_number VARCHAR(120) NULL',
        'temp_full_name VARCHAR(180) NULL',
        'temp_age INT NULL',
        'temp_gender VARCHAR(60) NULL',
        'temp_residence VARCHAR(180) NULL',
        'temp_phone VARCHAR(80) NULL',
        'temp_email VARCHAR(180) NULL',
        'temp_blood_type VARCHAR(60) NULL',
        'temp_sulfa_reactive TINYINT(1) NULL',
        'temp_penicillin_allergy TINYINT(1) NULL',
        'temp_latex_allergy TINYINT(1) NULL',
        'temp_other_allergies TEXT NULL',
        'temp_visit_type VARCHAR(120) NULL',
        'temp_chief_complaint TEXT NULL',
    ];
    foreach ($queueColumns as $colDef) {
        $name = explode(' ', $colDef, 2)[0];
        $exists = $conn->query("SHOW COLUMNS FROM queue LIKE '$name'");
        if ($exists && $exists->num_rows === 0) {
            $conn->query("ALTER TABLE queue ADD COLUMN $colDef");
        }
    }
    $conn->query('ALTER TABLE queue MODIFY patient_id INT NULL');
    $conn->query('ALTER TABLE queue MODIFY visit_id INT NULL');

    // Expand patient_photos.photo_type from ENUM to VARCHAR so multiple types are allowed
    $ptCol = $conn->query("SHOW COLUMNS FROM patient_photos LIKE 'photo_type'");
    if ($ptCol && ($ptDef = $ptCol->fetch_assoc()) && strpos($ptDef['Type'], 'enum') !== false) {
        $conn->query("ALTER TABLE patient_photos MODIFY COLUMN photo_type VARCHAR(60) NOT NULL DEFAULT 'other'");
    }

    // Ensure uploads directory exists
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!is_dir(UPLOAD_DIR . 'clinical/')) mkdir(UPLOAD_DIR . 'clinical/', 0755, true);

    foreach (['created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'] as $colDef) {
        $name = explode(' ', $colDef, 2)[0];
        $exists = $conn->query("SHOW COLUMNS FROM queue LIKE '$name'");
        if ($exists && $exists->num_rows === 0) {
            $conn->query("ALTER TABLE queue ADD COLUMN $colDef");
        }
    }

    $bt = $conn->query("SHOW COLUMNS FROM patients LIKE 'blood_type'");
    if ($bt && $bt->num_rows > 0) {
        $conn->query('ALTER TABLE patients MODIFY blood_type VARCHAR(20) NULL');
    } else {
        $conn->query('ALTER TABLE patients ADD COLUMN blood_type VARCHAR(20) NULL AFTER email');
    }

    $vt = $conn->query("SHOW COLUMNS FROM visits LIKE 'visit_type'");
    if ($vt && $vt->num_rows > 0) {
        $conn->query('ALTER TABLE visits MODIFY visit_type VARCHAR(120) NULL');
    } else {
        $conn->query('ALTER TABLE visits ADD COLUMN visit_type VARCHAR(120) NULL AFTER doctor_id');
    }

    $cat = $conn->query("SHOW COLUMNS FROM finances LIKE 'category'");
    if ($cat && $cat->num_rows === 0) {
        $conn->query("ALTER TABLE finances ADD COLUMN category VARCHAR(60) NULL AFTER patient_name");
    }
}

// ---- SESSION -----------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- AUTH --------------------------------------------------
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

// ---- THEME -------------------------------------------------
function getTheme(): string {
    return !empty($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
}

// ---- SETTINGS ----------------------------------------------
function getSetting(string $key, string $fallback = ''): string {
    $conn = db();
    $stmt = $conn->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['value'] ?? $fallback;
}

function setSetting(string $key, string $value): bool {
    $conn = db();
    $stmt = $conn->prepare('REPLACE INTO settings (`key`, `value`) VALUES (?, ?)');
    $stmt->bind_param('ss', $key, $value);
    return $stmt->execute();
}

// ---- FLASH MESSAGES ----------------------------------------
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ---- SAFE HTML OUTPUT --------------------------------------
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ---- LAYOUT HELPERS ----------------------------------------
function routeActive(string $name, ?string $activePage): bool {
    if ($activePage === null) return false;
    return strtolower($name) === strtolower($activePage);
}

function formatDateTime(string $value): string {
    return date('M j, Y H:i', strtotime($value));
}

// ---- PERMISSIONS -------------------------------------------
function loadUserPermissions(): void {
    $user = currentUser();
    if (empty($user['role'])) return;

    if ($user['role'] === 'admin') {
        $_SESSION['permissions'] = ['__is_admin__' => true];
        return;
    }
    if (!empty($_SESSION['permissions'])) return;

    $conn = db();
    $role = $conn->real_escape_string($user['role']);
    $result = $conn->query("
        SELECT p.page_key, rp.can_view, rp.can_insert, rp.can_update, rp.can_delete
        FROM role_permissions rp
        JOIN pages p ON p.id = rp.page_id
        WHERE rp.role = '$role'
    ");
    $perms = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $perms[$row['page_key']] = [
                'view'   => (bool)$row['can_view'],
                'insert' => (bool)$row['can_insert'],
                'update' => (bool)$row['can_update'],
                'delete' => (bool)$row['can_delete'],
            ];
        }
    }
    $_SESSION['permissions'] = $perms;
}

function canDo(string $pageKey, string $action): bool {
    if (!empty($_SESSION['permissions']['__is_admin__'])) return true;
    $perms = $_SESSION['permissions'] ?? null;
    if ($perms === null) return true;
    return !empty($perms[$pageKey][$action]);
}

function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function requirePermission(string $pageKey, string $action): void {
    loadUserPermissions();
    if (!canDo($pageKey, $action)) {
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        setFlash('error', 'Access denied. You do not have permission to view that page.');
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
}



