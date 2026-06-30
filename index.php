<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/Controller.php';

// Auto-load all controllers
foreach (glob(__DIR__ . '/app/Controllers/*.php') as $file) {
    require_once $file;
}

$app = new App();

// ---- Routes -----------------------------------------------
$app->any('/',                  [AuthController::class,       'redirect']);
$app->any('/login',             [AuthController::class,       'login']);
$app->get('/logout',            [AuthController::class,       'logout']);
$app->any('/dashboard',         [DashboardController::class,  'index']);
$app->any('/queue',             [QueueController::class,      'index']);
$app->any('/patients',          [PatientController::class,    'index']);
$app->get('/patients/profile',  [PatientController::class,    'profile']);
$app->get('/patients/print',    [PatientController::class,    'print']);
$app->any('/lab-tests',         [LabTestController::class,    'index']);
$app->any('/drugs',             [DrugController::class,       'index']);
$app->any('/finances',          [FinanceController::class,    'index']);
$app->get('/analytics',         [AnalyticsController::class,  'index']);
$app->any('/permissions',       [PermissionController::class, 'index']);
$app->any('/users',             [UserController::class,       'index']);
$app->any('/settings',          [SettingsController::class,   'index']);
$app->any('/ajax',              [AjaxController::class,       'handle']);

$app->run();
