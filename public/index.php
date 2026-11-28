<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/models/Program.php';
require_once dirname(__DIR__) . '/app/models/DashboardModel.php';
require_once dirname(__DIR__) . '/app/controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/controllers/PlacementController.php';
require_once dirname(__DIR__) . '/app/controllers/AdviserController.php';
require_once dirname(__DIR__) . '/app/controllers/OperationalController.php';
require_once dirname(__DIR__) . '/app/controllers/ReportController.php';
require_once dirname(__DIR__) . '/app/controllers/StudentController.php';
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

    $adviserActions = ['adviser_assignment_create', 'adviser_assignment_deactivate'];
    if (in_array($action, $adviserActions, true)) {
        if ($pageKey !== 'admin/adviser-assignments') {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
        } elseif (!$middleware->requireRole(['admin'])) {
            $pageKey = 'error/403';
            $routeDefinition = $routeDefinitions[$pageKey];
        } else {
            (new AdviserController(new AdviserModel(database_connection())))->handleAdminAction($action);
        }
    }

    $placementActions = [
        'placement_assign',
        'placement_approve',
        'placement_reject',
        'placement_activate',
        'placement_complete',
        'placement_cancel',
    ];
    if (in_array($action, $placementActions, true)) {
        if ($pageKey !== 'coordinator/placements') {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
        } elseif (!$middleware->requireRole(['coordinator'])) {
            $pageKey = 'error/403';
            $routeDefinition = $routeDefinitions[$pageKey];
        } else {
            (new PlacementController(new PlacementModel(database_connection())))
                ->handleAction($action);
        }
    }

    $operationalActions = [
        'attendance_submit', 'attendance_review', 'evaluation_submit',
        'requirement_submit', 'requirement_review',
        'notification_read', 'notification_read_all',
    ];
    if (in_array($action, $operationalActions, true)) {
        $allowedRoles = match ($action) {
            'attendance_submit', 'requirement_submit', 'notification_read', 'notification_read_all' => ['student'],
            'attendance_review', 'evaluation_submit' => ['supervisor'],
            'requirement_review' => ['coordinator'],
            default => [],
        };
        $expectedPage = match ($action) {
            'attendance_submit' => 'student/attendance',
            'requirement_submit' => 'student/requirements',
            'notification_read', 'notification_read_all' => 'student/notifications',
            'attendance_review', 'evaluation_submit' => 'supervisor/workflow',
            'requirement_review' => 'coordinator/requirements',
            default => '',
        };
        if ($pageKey !== $expectedPage) {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
        } elseif (!$middleware->requireRole($allowedRoles)) {
            $pageKey = 'error/403';
            $routeDefinition = $routeDefinitions[$pageKey];
        } else {
            (new OperationalController(new OperationalModel(database_connection())))->handle($action);
        }
    }

    if ($action === 'report_export') {
        if ($pageKey !== 'reports/index') {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
        } elseif (!$middleware->requireRole(['admin', 'coordinator', 'adviser'])) {
            $pageKey = 'error/403';
            $routeDefinition = $routeDefinitions['error/403'];
        } else {
            (new ReportController(new ReportModel(database_connection())))->exportCsv();
        }
    }

    if ($action === 'student_profile_update') {
        if ($pageKey !== 'student/profile') {
            http_response_code(404);
            $pageKey = 'error/404';
            $routeDefinition = $routeDefinitions[$pageKey];
        } elseif (!$middleware->requireRole(['student'])) {
            $pageKey = 'error/403';
            $routeDefinition = $routeDefinitions[$pageKey];
        } else {
            (new StudentController(new User(database_connection())))->updateProfile();
        }
    }
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
$role = role_display_name(authenticated_role() ?? '') ?: ($routeDefinition['role'] ?? null);
$viewPath = dirname(__DIR__) . '/app/views/' . $routeDefinition['view'] . '.php';

$programOptions = [];
$programLoadError = false;
$adminData = [];
$dashboardData = [];
$dashboardLoadError = false;
$studentPageData = [];
$studentPageLoadError = false;
$placementPageData = [];
$placementPageLoadError = false;
$adviserData = [];
$adviserLoadError = false;
$operationalData = [];
$supervisorWorkflow = [];
$reportData = [];

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

if ($pageKey === 'coordinator/placements') {
    try {
        $placementPageData = (new PlacementController(
            new PlacementModel(database_connection())
        ))->pageData();
    } catch (Throwable) {
        error_log('Placement management page could not be loaded.');
        $placementPageLoadError = true;
    }
}

if (in_array($pageKey, ['admin/adviser-assignments', 'adviser/students'], true)) {
    try {
        $controller = new AdviserController(new AdviserModel(database_connection()));
        if ($pageKey === 'admin/adviser-assignments') {
            $adviserData = $controller->adminPage();
        } else {
            $adviserData = $controller->studentsPage((int) (authenticated_user()['user_id'] ?? 0));
        }
    } catch (Throwable) {
        error_log('Adviser page could not be loaded.');
        $adviserLoadError = true;
    }
}

if ($pageKey === 'supervisor/workflow') {
    try {
        $supervisorWorkflow = (new OperationalController(new OperationalModel(database_connection())))
            ->supervisorAttendance((int) (authenticated_user()['user_id'] ?? 0));
    } catch (Throwable) {
        error_log('Supervisor workflow could not be loaded.');
        http_response_code(500);
        $pageKey = 'error/500';
        $routeDefinition = $routeDefinitions[$pageKey];
    }
}

if ($pageKey === 'coordinator/requirements') {
    try {
        $operationalData['submissions'] = (new OperationalModel(database_connection()))->requirementSubmissions();
    } catch (Throwable) {
        error_log('Requirement review page could not be loaded.');
        http_response_code(500);
        $pageKey = 'error/500';
        $routeDefinition = $routeDefinitions[$pageKey];
    }
}

if ($pageKey === 'reports/index') {
    try {
        $reportData = (new ReportController(new ReportModel(database_connection())))->pageData();
    } catch (Throwable) {
        error_log('Report page could not be loaded.');
        http_response_code(500);
        $pageKey = 'error/500';
        $routeDefinition = $routeDefinitions[$pageKey];
    }
}

if ($pageKey === 'student/attendance' || $pageKey === 'student/requirements') {
    try {
        $operationalModel = new OperationalModel(database_connection());
        $operationalData['active_placement'] = $operationalModel->activePlacementForStudent((int) (authenticated_user()['user_id'] ?? 0));
        $operationalData['requirement_types'] = $operationalModel->activeRequirementTypes();
    } catch (Throwable) {
        error_log('Student operational data could not be loaded.');
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$pageTitle = $routeDefinition['title'];
$pageSubtitle = $routeDefinition['subtitle'];
$role = role_display_name(authenticated_role() ?? '') ?: ($routeDefinition['role'] ?? null);
$viewPath = dirname(__DIR__) . '/app/views/' . $routeDefinition['view'] . '.php';

$layoutPath = match ($routeDefinition['layout']) {
    'public' => dirname(__DIR__) . '/app/views/layouts/public.php',
    'app' => dirname(__DIR__) . '/app/views/layouts/app.php',
    'auth' => dirname(__DIR__) . '/app/views/layouts/auth.php',
    default => dirname(__DIR__) . '/app/views/layouts/auth.php',
};

require $layoutPath;
