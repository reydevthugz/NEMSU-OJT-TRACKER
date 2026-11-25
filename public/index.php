<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/models/Program.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/middleware/AuthMiddleware.php';

start_secure_session();

$routeDefinitions = require dirname(__DIR__) . '/routes/web.php';
$pageValue = $_GET['page'] ?? 'home';
$pageKey = is_string($pageValue) ? $pageValue : 'error/404';
$routeDefinition = $routeDefinitions[$pageKey] ?? null;
if ($routeDefinition === null) {
    http_response_code(404);
    $pageKey = 'error/404';
    $routeDefinition = $routeDefinitions[$pageKey];
}

$middleware = new AuthMiddleware();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionValue = $_POST['action'] ?? '';
    $action = is_string($actionValue) ? $actionValue : '';

    if ($action === 'register') {
        (new AuthController())->register();
    }
    if ($action === 'login') {
        (new AuthController())->login();
    }
    if ($action === 'logout') {
        (new AuthController())->logout();
    }

    $adminActionPages = [
        'admin_user_create' => 'admin/users',
        'admin_user_update' => 'admin/user',
        'admin_program_create' => 'admin/programs',
        'admin_program_update' => 'admin/programs',
        'admin_company_create' => 'admin/companies',
        'admin_company_update' => 'admin/companies',
        'admin_supervisor_create' => 'admin/supervisors',
        'admin_supervisor_update' => 'admin/supervisors',
        'admin_position_create' => 'admin/positions',
        'admin_position_update' => 'admin/positions',
        'admin_skill_create' => 'admin/skills',
        'admin_skill_update' => 'admin/skills',
        'admin_requirement_create' => 'admin/requirements',
        'admin_requirement_update' => 'admin/requirements',
    ];
    if (isset($adminActionPages[$action])) {
        if ($pageKey !== $adminActionPages[$action]) {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
        } elseif (!$middleware->requireRole(['admin'])) {
            $pageKey = 'error/403';
            $routeDefinition = $routeDefinitions[$pageKey];
        } else {
            require_once dirname(__DIR__) . '/app/controllers/AdminController.php';
            try {
                (new AdminController(database_connection()))->handleAction($action, $pageKey);
            } catch (Throwable) {
                error_log('Administrative database operation could not be started.');
                set_flash('error', 'The requested change could not be completed.');
                header('Location: ' . app_route_url($pageKey, app_base_path()), true, 303);
                exit;
            }
        }
    } else {
        http_response_code(404);
        $pageKey = 'error/404';
        $routeDefinition = $routeDefinitions[$pageKey];
    }
}

if (isset($routeDefinition['guest_only']) && $routeDefinition['guest_only'] && is_authenticated()) {
    redirect_to_dashboard(authenticated_role() ?? 'student');
}
if (isset($routeDefinition['allowed_roles']) && !$middleware->requireRole((array) $routeDefinition['allowed_roles'])) {
    $pageKey = 'error/403';
    $routeDefinition = $routeDefinitions[$pageKey];
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

if (str_starts_with($pageKey, 'admin/') || $pageKey === 'dashboard/admin') {
    require_once dirname(__DIR__) . '/app/controllers/AdminController.php';
    try {
        $adminData = (new AdminController(database_connection()))->pageData($pageKey);
        if (!empty($adminData['not_found'])) {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
            $pageTitle = $routeDefinition['title'];
            $pageSubtitle = $routeDefinition['subtitle'];
            $viewPath = dirname(__DIR__) . '/app/views/' . $routeDefinition['view'] . '.php';
        }
    } catch (Throwable) {
        error_log('Administrative page data could not be loaded.');
        http_response_code(500);
        $pageKey = 'error/500';
        $routeDefinition = $routeDefinitions[$pageKey];
        $pageTitle = $routeDefinition['title'];
        $pageSubtitle = $routeDefinition['subtitle'];
        $viewPath = dirname(__DIR__) . '/app/views/' . $routeDefinition['view'] . '.php';
        $role = null;
    }
}

if ($pageKey === 'register') {
    try {
        $programOptions = (new Program(database_connection()))->all();
    } catch (Throwable) {
        error_log('Registration program list could not be loaded.');
        $programLoadError = true;
    }
}

if ($routeDefinition['layout'] === 'home') {
    require $viewPath;
    exit;
}

$layoutPath = match ($routeDefinition['layout']) {
    'app' => dirname(__DIR__) . '/app/views/layouts/app.php',
    'auth' => dirname(__DIR__) . '/app/views/layouts/auth.php',
    default => dirname(__DIR__) . '/app/views/layouts/auth.php',
};

require $layoutPath;
