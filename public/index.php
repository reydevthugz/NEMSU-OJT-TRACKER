<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/models/Program.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/middleware/AuthMiddleware.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionValue = $_POST['action'] ?? '';
    $action = is_string($actionValue) ? $actionValue : '';
    $authController = new AuthController();

    if ($action === 'register') {
        $authController->register();
    }
    if ($action === 'login') {
        $authController->login();
    }
    if ($action === 'logout') {
        $authController->logout();
    }

    http_response_code(404);
    $pageKey = 'error/404';
} else {
    $pageKey = $_GET['page'] ?? 'home';
}

$routeDefinitions = require dirname(__DIR__) . '/routes/web.php';

if (!is_string($pageKey)) {
    http_response_code(404);
    $pageKey = 'error/404';
}

$routeDefinition = $routeDefinitions[$pageKey] ?? null;
if ($routeDefinition === null) {
    http_response_code(404);
    $pageKey = 'error/404';
    $routeDefinition = $routeDefinitions[$pageKey];
}

$middleware = new AuthMiddleware();

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
