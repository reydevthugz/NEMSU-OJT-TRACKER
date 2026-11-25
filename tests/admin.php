<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/models/AdminModel.php';
require_once dirname(__DIR__) . '/app/controllers/AdminController.php';

$checks = 0;

function admin_check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }

    $checks++;
}

function render_admin_view(string $viewPath, array $data, string $page, string $basePath = ''): string
{
    $adminData = $data;
    $pageKey = $page;
    $appBase = $basePath;
    ob_start();
    require $viewPath;

    return (string) ob_get_clean();
}

function render_admin_pagination(array $listing, array $filters, string $page, string $basePath = ''): string
{
    $pageKey = $page;
    $appBase = $basePath;
    ob_start();
    require dirname(__DIR__) . '/app/views/admin/_pagination.php';

    return (string) ob_get_clean();
}

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec('PRAGMA foreign_keys = ON');
$db->exec(
    "CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL, role TEXT NOT NULL, status TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )"
);
$db->exec(
    "CREATE TABLE programs (
        id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT NOT NULL UNIQUE, name TEXT NOT NULL UNIQUE,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )"
);
$db->exec(
    'CREATE TABLE student_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL UNIQUE,
        program_id INTEGER NOT NULL, student_number TEXT NOT NULL UNIQUE,
        year_level INTEGER NOT NULL, preferred_location TEXT NULL
    )'
);
$db->exec(
    "CREATE TABLE companies (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, address TEXT NULL,
        contact_name TEXT NULL, contact_email TEXT NULL, contact_phone TEXT NULL,
        description TEXT NULL, status TEXT NOT NULL DEFAULT 'active',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )"
);
$db->exec(
    'CREATE TABLE company_supervisors (
        id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL,
        supervisor_user_id INTEGER NOT NULL, UNIQUE(company_id, supervisor_user_id)
    )'
);
$db->exec(
    "CREATE TABLE company_positions (
        id INTEGER PRIMARY KEY AUTOINCREMENT, company_id INTEGER NOT NULL, title TEXT NOT NULL,
        description TEXT NULL, available_slots INTEGER NOT NULL DEFAULT 1, status TEXT NOT NULL DEFAULT 'open'
    )"
);
$db->exec('CREATE TABLE placements (id INTEGER PRIMARY KEY AUTOINCREMENT, position_id INTEGER NOT NULL, status TEXT NOT NULL)');
$db->exec('CREATE TABLE skills (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, description TEXT NULL, created_at TEXT, updated_at TEXT)');
$db->exec('CREATE TABLE requirement_types (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, description TEXT NULL, is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT, updated_at TEXT)');

$model = new AdminModel($db);
$programStatement = $db->prepare('INSERT INTO programs (code, name) VALUES (:code, :name)');
$programStatement->execute(['code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science']);
$officialProgramId = (int) $db->lastInsertId();

$studentStatement = $db->prepare(
    'INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)'
);
$studentStatement->execute([
    'name' => 'Test Student',
    'email' => 'student@example.test',
    'password' => password_hash('student-password', PASSWORD_DEFAULT),
    'role' => 'student',
    'status' => 'active',
]);
$studentId = (int) $db->lastInsertId();
$profileStatement = $db->prepare(
    'INSERT INTO student_profiles (user_id, program_id, student_number, year_level) VALUES (:user_id, :program_id, :number, 2)'
);
$profileStatement->execute(['user_id' => $studentId, 'program_id' => $officialProgramId, 'number' => 'T-001']);

admin_check($model->dashboardStats()['total_users'] === 1, 'Dashboard should count actual user rows.');
admin_check($model->dashboardStats()['total_students'] === 1, 'Dashboard should aggregate student roles.');

$userListing = $model->users(['search' => 'T-001', 'role' => '', 'status' => ''], 1, 10);
admin_check($userListing['total'] === 1, 'User search should include student number.');
admin_check(!array_key_exists('password', $userListing['items'][0]), 'User listings must not expose password hashes.');
admin_check(!array_key_exists('password', $model->userDetails($studentId)), 'Account details must not expose password hashes.');

$staffId = $model->createStaffUser('Test Adviser', 'adviser@example.test', 'adviser', 'safe-test-password');
$model->createStaffUser('Second Adviser', 'second-adviser@example.test', 'adviser', 'safe-test-password');
$pagedUsers = $model->users(['search' => '', 'role' => '', 'status' => ''], 1, 1);
$secondUserPage = $model->users(['search' => '', 'role' => '', 'status' => ''], 2, 1);
admin_check($pagedUsers['pages'] === 3 && count($pagedUsers['items']) === 1, 'User accounts should paginate using the requested page size.');
admin_check($secondUserPage['page'] === 2 && count($secondUserPage['items']) === 1, 'Pagination should return later account pages.');
$paginationHtml = html_entity_decode(
    render_admin_pagination(
        ['page' => 5, 'pages' => 100],
        ['search' => 'sample query', 'role' => 'student', 'status' => 'active'],
        'admin/users'
    ),
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
);
admin_check(str_contains($paginationHtml, 'q=sample+query') && str_contains($paginationHtml, 'role=student'), 'Pagination links should preserve search and role filters.');
admin_check(substr_count($paginationHtml, 'class="page-link"') < 15, 'Pagination should avoid rendering an unbounded page-link list.');
$staffPassword = $db->query('SELECT password FROM users WHERE id = ' . $staffId)->fetchColumn();
admin_check(password_verify('safe-test-password', $staffPassword), 'Staff passwords must be stored as hashes.');
$studentRoleRejected = false;
try {
    $model->createStaffUser('Invalid Student', 'invalid@example.test', 'student', 'safe-test-password');
} catch (InvalidArgumentException) {
    $studentRoleRejected = true;
}
admin_check($studentRoleRejected, 'The staff account method must not create public student accounts.');

$officialProgramProtected = false;
try {
    $model->updateProgram($officialProgramId, 'BSCS', 'Changed name');
} catch (DomainException) {
    $officialProgramProtected = true;
}
admin_check($officialProgramProtected, 'The existing BSCS program must be protected from edits.');
$duplicateProgramRejected = false;
try {
    $model->createProgram('BSCS', 'A duplicate program');
} catch (PDOException) {
    $duplicateProgramRejected = true;
}
admin_check($duplicateProgramRejected, 'Unique program codes must reject duplicate records.');
admin_check($model->programDetails($officialProgramId)['name'] === 'Bachelor of Science in Computer Science', 'BSCS must remain unchanged.');

$companyId = $model->createCompany([
    'name' => 'Verified Test Company',
    'address' => null,
    'contact_name' => null,
    'contact_email' => null,
    'contact_phone' => null,
    'description' => null,
    'status' => 'active',
]);
$duplicateCompanyRejected = false;
try {
    $model->createCompany([
        'name' => 'Verified Test Company',
        'address' => null,
        'contact_name' => null,
        'contact_email' => null,
        'contact_phone' => null,
        'description' => null,
        'status' => 'active',
    ]);
} catch (PDOException) {
    $duplicateCompanyRejected = true;
}
admin_check($duplicateCompanyRejected, 'Unique company names must reject duplicate records.');

$supervisorId = $model->createStaffUser('Test Supervisor', 'supervisor@example.test', 'supervisor', 'safe-test-password');
$model->updateUserAccount(
    $supervisorId,
    'Updated Supervisor',
    'updated-supervisor@example.test',
    'supervisor',
    'active'
);
$supervisorDetails = $model->userDetails($supervisorId);
admin_check($supervisorDetails['name'] === 'Updated Supervisor', 'Administrators should be able to update supervisor details.');
$duplicateEmailRejected = false;
try {
    $model->updateUserAccount($supervisorId, 'Updated Supervisor', 'student@example.test', 'supervisor', 'active');
} catch (PDOException) {
    $duplicateEmailRejected = true;
}
admin_check($duplicateEmailRejected, 'User email uniqueness must be enforced during detail updates.');
$associationId = $model->createSupervisorAssociation($companyId, $supervisorId);
$associationDuplicateRejected = false;
try {
    $model->createSupervisorAssociation($companyId, $supervisorId);
} catch (PDOException) {
    $associationDuplicateRejected = true;
}
admin_check($associationId > 0 && $associationDuplicateRejected, 'Supervisor-company associations must be unique.');
$invalidSupervisorRejected = false;
try {
    $model->createSupervisorAssociation($companyId, $studentId);
} catch (InvalidArgumentException) {
    $invalidSupervisorRejected = true;
}
admin_check($invalidSupervisorRejected, 'Only active supervisor accounts may be associated.');

$positionId = $model->createPosition([
    'company_id' => $companyId,
    'title' => 'Test Position',
    'description' => null,
    'available_slots' => 2,
    'status' => 'open',
]);
$placementInsert = $db->prepare('INSERT INTO placements (position_id, status) VALUES (:id, :status)');
$placementInsert->execute(['id' => $positionId, 'status' => 'active']);
$placementInsert->execute(['id' => $positionId, 'status' => 'active']);
$capacityRejected = false;
try {
    $model->updatePosition($positionId, [
        'company_id' => $companyId,
        'title' => 'Test Position',
        'description' => null,
        'available_slots' => 1,
        'status' => 'open',
    ]);
} catch (DomainException) {
    $capacityRejected = true;
}
admin_check($capacityRejected, 'Position capacity must not be reduced below active placements.');
$moveRejected = false;
try {
    $model->updatePosition($positionId, [
        'company_id' => $companyId + 1,
        'title' => 'Moved Position',
        'description' => null,
        'available_slots' => 2,
        'status' => 'open',
    ]);
} catch (DomainException) {
    $moveRejected = true;
}
admin_check($moveRejected, 'A position with placement history must not be moved to another company.');
$positionListing = $model->positions(['search' => '', 'status' => '', 'company_id' => $companyId], 1);
admin_check($positionListing['total'] === 1 && (int) $positionListing['items'][0]['remaining_slots'] === 0, 'Position filtering and remaining-slot counts should be correct.');

$skillId = $model->createSkill('Approved Test Skill', 'Test description');
$skillDuplicateRejected = false;
try {
    $model->createSkill('Approved Test Skill', null);
} catch (PDOException) {
    $skillDuplicateRejected = true;
}
admin_check($skillId > 0 && $skillDuplicateRejected, 'Skill names must be unique.');

$requirementId = $model->createRequirementType('Test Requirement', null, false);
$activeRequirements = $model->requirementTypes(['search' => '', 'status' => 'active'], 1);
$inactiveRequirements = $model->requirementTypes(['search' => 'Test', 'status' => 'inactive'], 1);
admin_check($activeRequirements['total'] === 0, 'Requirement status filtering should exclude inactive records.');
admin_check($inactiveRequirements['total'] === 1 && (int) $inactiveRequirements['items'][0]['id'] === $requirementId, 'Requirement search should return the matching inactive record.');

$adminId = $model->createStaffUser('Test Administrator', 'admin@example.test', 'admin', 'safe-test-password');
start_secure_session();
$_SESSION['auth'] = [
    'user_id' => $adminId,
    'name' => 'Test Administrator',
    'email' => 'admin@example.test',
    'role' => 'admin',
    'login_at' => time(),
    'last_activity' => time(),
];
$controller = new AdminController($db);
$updateUserMethod = new ReflectionMethod(AdminController::class, 'updateUser');
$updateUserMethod->setAccessible(true);
$studentPromotionRejected = false;
$_POST = [
    'user_id' => (string) $staffId,
    'name' => 'Test Adviser',
    'email' => 'adviser@example.test',
    'role' => 'student',
    'status' => 'active',
];
$redirectParameters = [];
try {
    $updateUserMethod->invokeArgs($controller, [&$redirectParameters]);
} catch (DomainException) {
    $studentPromotionRejected = true;
}
admin_check($studentPromotionRejected, 'A staff account without a student profile must not be assigned the student role.');
$selfDemotionRejected = false;
$_POST = [
    'user_id' => (string) $adminId,
    'name' => 'Test Administrator',
    'email' => 'admin@example.test',
    'role' => 'adviser',
    'status' => 'active',
];
try {
    $updateUserMethod->invokeArgs($controller, [&$redirectParameters]);
} catch (DomainException) {
    $selfDemotionRejected = true;
}
admin_check($selfDemotionRejected, 'The authenticated administrator must not be able to demote their own account.');
$_POST = [];
$_GET['company_id'] = (string) $companyId;
$adminPage = $controller->pageData('admin/positions');
admin_check((int) $adminPage['filters']['company_id'] === $companyId, 'The positions page should preserve and apply its company filter.');
admin_check($adminPage['listing']['total'] === 1, 'The positions page should return filtered position records.');
$viewCases = [
    ['dashboard/admin', $controller->pageData('dashboard/admin'), dirname(__DIR__) . '/app/views/dashboards/admin.php'],
    ['admin/users', $controller->pageData('admin/users'), dirname(__DIR__) . '/app/views/admin/users.php'],
    ['admin/user', (function () use ($controller, $studentId): array {
        $_GET['id'] = (string) $studentId;

        return $controller->pageData('admin/user');
    })(), dirname(__DIR__) . '/app/views/admin/user.php'],
    ['admin/programs', $controller->pageData('admin/programs'), dirname(__DIR__) . '/app/views/admin/programs.php'],
    ['admin/companies', $controller->pageData('admin/companies'), dirname(__DIR__) . '/app/views/admin/companies.php'],
    ['admin/supervisors', $controller->pageData('admin/supervisors'), dirname(__DIR__) . '/app/views/admin/supervisors.php'],
    ['admin/positions', $adminPage, dirname(__DIR__) . '/app/views/admin/positions.php'],
    ['admin/skills', $controller->pageData('admin/skills'), dirname(__DIR__) . '/app/views/admin/skills.php'],
    ['admin/requirements', $controller->pageData('admin/requirements'), dirname(__DIR__) . '/app/views/admin/requirements.php'],
];
foreach ($viewCases as [$page, $data, $viewPath]) {
    $rendered = render_admin_view($viewPath, $data, $page);
    if ($page !== 'dashboard/admin') {
        admin_check(str_contains($rendered, 'csrf_token'), 'Administrative views must render CSRF tokens in forms.');
    }
    if (!in_array($page, ['dashboard/admin', 'admin/user'], true)) {
        admin_check(str_contains($rendered, 'table-responsive'), 'Management table view must use a responsive Bootstrap wrapper: ' . $page);
    }
}
destroy_secure_session();

echo 'Administrative model checks passed: ' . $checks . PHP_EOL;
