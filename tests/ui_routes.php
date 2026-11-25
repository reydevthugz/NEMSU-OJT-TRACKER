<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/helpers/view.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';

$checks = 0;

function ui_route_check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }

    $checks++;
}

$routes = require dirname(__DIR__) . '/routes/web.php';
$root = dirname(__DIR__);
$layoutPaths = [
    'public' => $root . '/app/views/layouts/public.php',
    'auth' => $root . '/app/views/layouts/auth.php',
    'app' => $root . '/app/views/layouts/app.php',
];

foreach ($routes as $page => $route) {
    $viewPath = $root . '/app/views/' . $route['view'] . '.php';
    ui_route_check(is_file($viewPath), 'Every route must reference an existing view: ' . $page);
    ui_route_check(isset($layoutPaths[$route['layout']]) && is_file($layoutPaths[$route['layout']]), 'Every route must reference an existing canonical layout: ' . $page);
}

ui_route_check($routes['home']['layout'] === 'public', 'The public landing page must use its dedicated public layout.');
ui_route_check($routes['login']['layout'] === 'auth' && $routes['register']['layout'] === 'auth', 'Authentication routes must share one auth layout.');

foreach (['admin/users', 'admin/user', 'admin/programs', 'admin/companies', 'admin/supervisors', 'admin/positions', 'admin/skills', 'admin/requirements'] as $page) {
    ui_route_check(($routes[$page]['allowed_roles'] ?? []) === ['admin'], 'Administrator management routes must be role-protected: ' . $page);
}

foreach (['student/profile', 'student/skills', 'student/preferences', 'student/placement', 'student/requirements', 'student/attendance', 'student/progress', 'student/notifications'] as $page) {
    ui_route_check(($routes[$page]['allowed_roles'] ?? []) === ['student'], 'Student record routes must be account-role protected: ' . $page);
    ui_route_check(isset($routes[$page]['student_section']), 'Student record routes must declare their read-only section: ' . $page);
}

ob_start();
app_render_component('notification-list', [
    'notifications' => [],
    'emptyMessage' => 'Notifications addressed to your account will appear here.',
]);
$notificationMarkup = (string) ob_get_clean();
ui_route_check(str_contains($notificationMarkup, 'No recent notifications'), 'The shared notification component must be available to dashboard views.');
ui_route_check(str_contains($notificationMarkup, 'Notifications addressed to your account'), 'The notification empty state must be customizable.');

$adminNavigation = '';
ob_start();
app_render_component('sidebar', [
    'role' => 'System Administrator',
    'activePage' => 'admin/users',
    'appBase' => '/tracker',
]);
$adminNavigation = (string) ob_get_clean();
ui_route_check(str_contains($adminNavigation, 'href="/tracker/?page=admin/users"'), 'Administrator navigation must link to the restored user management route.');
ui_route_check(str_contains($adminNavigation, 'aria-current="page"'), 'The current management page must be marked active.');
ui_route_check(!str_contains($adminNavigation, 'is-placeholder'), 'Navigation must not expose inert placeholder items.');

start_secure_session();
$appBase = '/tracker';
$programOptions = [['id' => 1, 'code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science']];
$programLoadError = false;
ob_start();
require dirname(__DIR__) . '/app/views/auth/register.php';
$registrationMarkup = (string) ob_get_clean();
ui_route_check(str_contains($registrationMarkup, 'value="6">Sixth year'), 'Registration must expose the sixth-year level supported by server validation.');
ui_route_check(str_contains($registrationMarkup, 'value="7">Seventh year'), 'Registration must expose the seventh-year level supported by server validation.');
ui_route_check(str_contains($registrationMarkup, 'value="8">Eighth year'), 'Registration must expose the eighth-year level supported by server validation.');
ui_route_check(substr_count($registrationMarkup, 'data-password-toggle') === 2, 'Registration must provide visibility controls for both password fields.');

echo 'UI route and component checks passed: ' . $checks . PHP_EOL;
