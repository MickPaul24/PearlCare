<?php
if (!isset($pageTitle) || $pageTitle === null)       $pageTitle    = 'Kampala Skin Clinic';
if (!isset($activePage) || $activePage === null)   $activePage   = 'Dashboard';
if (!isset($pageSubtitle) || $pageSubtitle === null) $pageSubtitle = '';

$GLOBALS['activePage'] = $activePage;

loadUserPermissions();
$user  = currentUser();
$theme = getTheme();

// ── Preview / confirm / cancel UI v2 ─────────────────────────────────
if (isset($_GET['preview_ui'])) {
    $_SESSION['preview_ui'] = true;
    $qs = $_GET; unset($qs['preview_ui']);
    $loc = strtok($_SERVER['REQUEST_URI'], '?');
    if ($qs) $loc .= '?' . http_build_query($qs);
    header('Location: ' . $loc); exit;
}
if (isset($_GET['confirm_ui'])) {
    setcookie('ui_v2', '1', time() + 86400 * 730, '/');
    unset($_SESSION['preview_ui']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); exit;
}
if (isset($_GET['cancel_ui'])) {
    unset($_SESSION['preview_ui']);
    setcookie('ui_v2', '', time() - 3600, '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); exit;
}
$useV2     = !empty($_SESSION['preview_ui']) || !empty($_COOKIE['ui_v2']);
$previewMode = !empty($_SESSION['preview_ui']);

if (empty($user)) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

if (!function_exists('navItem')) {
    function navItem(string $key, string $label, string $href, string $icon, string $pageKey): void {
        global $activePage;
        if (!canDo($pageKey, 'view')) return;
        $activeClass = routeActive($key, $activePage) ? 'active' : '';
        echo '<a href="' . e($href) . '" class="nav-link ' . $activeClass . '">'
            . '<span class="nav-icon"><i data-lucide="' . e($icon) . '"></i></span>'
            . '<span>' . e($label) . '</span>'
            . '</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo e($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | Kampala Skin Clinic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/public/assets/images/logo.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/<?php echo $useV2 ? 'style-v2.css' : 'style.css'; ?>">
    <!-- Expose PHP BASE_URL to JavaScript before any scripts run -->
    <script>window.BASE_URL = '<?php echo BASE_URL; ?>';</script>
    <!-- Lucide loaded locally — works fully offline -->
    <script src="<?php echo BASE_URL; ?>/public/assets/lucide.min.js"></script>
    <script defer src="<?php echo BASE_URL; ?>/public/scripts.js"></script>
</head>
<body>
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-icon">PC</div>
                <div>
                    <div class="brand-title">PearlCare</div>
                    <div class="brand-subtitle">Clinical Excellence</div>
                </div>
            </div>

            <nav class="nav-menu">
                <?php navItem('Dashboard',   'Dashboard',   BASE_URL . '/dashboard',   'house',             'dashboard'); ?>
                <?php navItem('Queue',       'Queue',       BASE_URL . '/queue',        'list-ordered',      'queue'); ?>
                <?php navItem('Patients',    'Patients',    BASE_URL . '/patients',     'user-round',        'patients'); ?>
                <?php navItem('Lab Tests',   'Lab Tests',   BASE_URL . '/lab-tests',    'test-tube-2',       'lab_tests'); ?>
                <?php navItem('Drugs',       'Drugs',       BASE_URL . '/drugs',        'tablets',           'drugs'); ?>
                <?php navItem('Finances',    'Finances',    BASE_URL . '/finances',     'circle-dollar-sign','finances'); ?>
                <?php navItem('Analytics',   'Analytics',   BASE_URL . '/analytics',    'chart-no-axes-combined','analytics'); ?>
                <?php navItem('Permissions', 'Permissions', BASE_URL . '/permissions',  'shield-half',       'permissions'); ?>
                <?php navItem('Users',       'Users',       BASE_URL . '/users',        'users-round',       'users'); ?>
                <?php navItem('Settings',    'Settings',    BASE_URL . '/settings',     'sliders-horizontal','settings'); ?>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo BASE_URL; ?>/logout" class="sidebar-logout">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-shell">
            <header class="topbar">
                <button class="mobile-nav-toggle" id="mobileNavToggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
                    <i data-lucide="menu"></i>
                    <span>Menu</span>
                </button>
                <div class="search-bar">
                    <input type="search" id="globalSearch" placeholder="Search patients, records, or drugs..." autocomplete="off">
                </div>
                <div class="topbar-actions">
                    <?php if (!$useV2): ?>
                    <a href="?preview_ui=1" style="display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:50px;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;font-size:.78rem;font-weight:700;text-decoration:none;box-shadow:0 2px 10px rgba(124,58,237,.3);transition:all .15s;white-space:nowrap;">
                        <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Preview New UI
                    </a>
                    <?php endif; ?>
                    <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme">
                        <?php echo $theme === 'dark' ? '☀️' : '🌙'; ?>
                    </button>
                    <div class="user-chip">
                        <span class="user-avatar"><?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?></span>
                        <div>
                            <div class="user-name"><?php echo e($user['full_name'] ?? 'User'); ?></div>
                            <div class="user-role"><?php echo e(ucfirst($user['role'] ?? 'Staff')); ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="page-wrapper">
                <div class="page-header">
                    <div>
                        <h1><?php echo e($pageTitle); ?></h1>
                        <?php if ($pageSubtitle): ?>
                            <p class="page-subtitle"><?php echo e($pageSubtitle); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($flash = getFlash()): ?>
                    <div class="flash flash-<?php echo e($flash['type']); ?>">
                        <?php echo e($flash['msg']); ?>
                    </div>
                <?php endif; ?>

                <div class="page-content">
