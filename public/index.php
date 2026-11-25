<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/models/Program.php';
require_once dirname(__DIR__) . '/app/models/DashboardModel.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/middleware/AuthMiddleware.php';

start_secure_session();

$routeDefinitions = require dirname(__DIR__) . '/routes/web.php';
$pageValue = $_GET['page'] ?? 'home';
$pageKey = is_string($pageValue) ? $pageValue : 'error/404';
$pendingAdminAction = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionValue = $_POST['action'] ?? '';
    $action = is_string($actionValue) ? $actionValue : '';
    $authController = new AuthController();

    if ($action === 'register') {
        $authController->register();
    } elseif ($action === 'login') {
        $authController->login();
    } elseif ($action === 'logout') {
        $authController->logout();
    } elseif (str_starts_with($action, 'admin_')) {
        $pendingAdminAction = $action;
    } else {
        http_response_code(404);
        $pageKey = 'error/404';
    }
}

$routeDefinition = $routeDefinitions[$pageKey] ?? null;
if ($routeDefinition === null) {
    http_response_code(404);
    $pageKey = 'error/404';
    $routeDefinition = $routeDefinitions[$pageKey];
}

$middleware = new AuthMiddleware();

if (!empty($routeDefinition['guest_only']) && is_authenticated()) {
    redirect_to_dashboard(authenticated_role() ?? 'student');
}
if (isset($routeDefinition['allowed_roles']) && !$middleware->requireRole((array) $routeDefinition['allowed_roles'])) {
    $pageKey = 'error/403';
    $routeDefinition = $routeDefinitions[$pageKey];
}
if ($pendingAdminAction !== null && !$middleware->requireRole(['admin'])) {
    $pendingAdminAction = null;
    $pageKey = 'error/403';
    $routeDefinition = $routeDefinitions[$pageKey];
}

if ($pendingAdminAction !== null) {
    try {
        (new AdminController(database_connection()))->handleAction($pendingAdminAction, $pageKey);
    } catch (Throwable) {
        error_log('Administrative action could not be initialized.');
        http_response_code(500);
        $pageKey = 'error/500';
        $routeDefinition = $routeDefinitions[$pageKey];
    }
}

$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$assetBase = rtrim($scriptDirectory, '/');
$appBase = preg_replace('~/public$~', '', $assetBase) ?? $assetBase;
$assetBase = $appBase;
$pageTitle = $routeDefinition['title'];
$pageSubtitle = $routeDefinition['subtitle'];
$role = $routeDefinition['role'] ?? null;
$viewPath = dirname(__DIR__) . '/app/views/' . $routeDefinition['view'] . '.php';

$programOptions = [];
$programLoadError = false;
$adminData = [];
$dashboardData = [];
$dashboardLoadError = false;
$studentPageData = [];
$studentPageLoadError = false;

if ($pageKey === 'register') {
    try {
        $programOptions = (new Program(database_connection()))->all();
    } catch (Throwable) {
        error_log('Registration program list could not be loaded.');
        $programLoadError = true;
    }
}

if ($pageKey === 'dashboard/admin' || str_starts_with($pageKey, 'admin/')) {
    try {
        $adminData = (new AdminController(database_connection()))->pageData($pageKey);
        if (!empty($adminData['not_found'])) {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
        }
    } catch (Throwable) {
        error_log('Administrative page could not be loaded.');
        http_response_code(500);
        $pageKey = 'error/500';
        $routeDefinition = $routeDefinitions[$pageKey];
    }
}

if (in_array($pageKey, [
    'dashboard/student',
    'dashboard/adviser',
    'dashboard/coordinator',
    'dashboard/supervisor',
], true)) {
    try {
        $userId = (int) (authenticated_user()['user_id'] ?? 0);
        $dashboardData = (new DashboardModel(database_connection()))
            ->forRole(authenticated_role() ?? '', $userId);
    } catch (Throwable) {
        error_log('Role dashboard summary could not be loaded.');
        $dashboardLoadError = true;
    }
}

if (isset($routeDefinition['student_section'])) {
    try {
        $userId = (int) (authenticated_user()['user_id'] ?? 0);
        $studentPageData = (new DashboardModel(database_connection()))
            ->studentPage($routeDefinition['student_section'], $userId);
    } catch (Throwable) {
        error_log('Student page data could not be loaded.');
        $studentPageLoadError = true;
    }
}

$pageTitle = $routeDefinition['title'];
$pageSubtitle = $routeDefinition['subtitle'];
$role = $routeDefinition['role'] ?? null;
$viewPath = dirname(__DIR__) . '/app/views/' . $routeDefinition['view'] . '.php';

$layoutPath = match ($routeDefinition['layout']) {
    'public' => dirname(__DIR__) . '/app/views/layouts/public.php',
    'app' => dirname(__DIR__) . '/app/views/layouts/app.php',
    'auth' => dirname(__DIR__) . '/app/views/layouts/auth.php',
    default => dirname(__DIR__) . '/app/views/layouts/auth.php',
};

require $layoutPath;
