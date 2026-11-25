<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/models/User.php';
require_once dirname(__DIR__) . '/app/helpers/auth.php';
require_once dirname(__DIR__) . '/app/middleware/AuthMiddleware.php';

$checks = 0;

function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }

    $checks++;
}

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec('PRAGMA foreign_keys = ON');
$db->exec(
    'CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL,
        status TEXT NOT NULL
    )'
);
$db->exec(
    'CREATE TABLE programs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL UNIQUE
    )'
);
$db->exec(
    'CREATE TABLE student_profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE REFERENCES users(id),
        program_id INTEGER NOT NULL REFERENCES programs(id),
        student_number TEXT NOT NULL UNIQUE,
        year_level INTEGER NOT NULL,
        preferred_location TEXT NULL
    )'
);
$db->exec("INSERT INTO programs (code, name) VALUES ('TEST', 'Test Program')");

$userModel = new User($db);
$testPassword = 'Correct-Horse-42!';
$userId = $userModel->createStudent([
    'name' => 'Test Student',
    'email' => 'student@example.test',
    'student_number' => 'TEST-001',
    'program_id' => 1,
    'year_level' => 2,
    'preferred_location' => null,
    'password' => $testPassword,
    'role' => 'admin',
]);

$user = $userModel->authenticate('student@example.test', $testPassword);
check($user !== null && $user['id'] === $userId, 'Valid active credentials should authenticate.');
check($user !== null && $user['role'] === 'student', 'Registration must always assign the student role.');
check($userModel->authenticate('student@example.test', 'wrong-password') === null, 'Invalid passwords must be rejected.');
check($userModel->authenticate("' OR 1=1 --", $testPassword) === null, 'Email input must remain a prepared-statement value.');
check($userModel->emailExists('student@example.test'), 'Duplicate email lookup should find the existing account.');
check($userModel->studentNumberExists('TEST-001'), 'Duplicate student-number lookup should find the existing profile.');

$storedPasswordStatement = $db->prepare('SELECT password FROM users WHERE id = :id');
$storedPasswordStatement->execute(['id' => $userId]);
$storedPassword = (string) $storedPasswordStatement->fetchColumn();
check($storedPassword !== $testPassword && password_verify($testPassword, $storedPassword), 'Passwords must be stored as hashes.');

$statusStatement = $db->prepare('UPDATE users SET status = :status WHERE id = :id');
$statusStatement->execute(['status' => 'disabled', 'id' => $userId]);
check($userModel->authenticate('student@example.test', $testPassword) === null, 'Disabled accounts must not authenticate.');
$statusStatement->execute(['status' => 'active', 'id' => $userId]);

$duplicateStudentRejected = false;
try {
    $userModel->createStudent([
        'name' => 'Second Test Student',
        'email' => 'second@example.test',
        'student_number' => 'TEST-001',
        'program_id' => 1,
        'year_level' => 1,
        'preferred_location' => null,
        'password' => $testPassword,
    ]);
} catch (PDOException) {
    $duplicateStudentRejected = true;
}
check($duplicateStudentRejected, 'The unique student-number constraint must reject duplicate registration.');
check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1, 'Failed profile creation must roll back its user insert.');

$duplicateEmailRejected = false;
try {
    $userModel->createStudent([
        'name' => 'Duplicate Email Student',
        'email' => 'student@example.test',
        'student_number' => 'TEST-002',
        'program_id' => 1,
        'year_level' => 1,
        'preferred_location' => null,
        'password' => $testPassword,
    ]);
} catch (PDOException) {
    $duplicateEmailRejected = true;
}
check($duplicateEmailRejected, 'The unique email constraint must reject duplicate registration.');
check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1, 'Failed duplicate-email registration must roll back.');

$db->beginTransaction();
$nestedUserId = $userModel->createStudent([
    'name' => 'Nested Transaction Student',
    'email' => 'nested@example.test',
    'student_number' => 'TEST-NESTED',
    'program_id' => 1,
    'year_level' => 1,
    'preferred_location' => null,
    'password' => $testPassword,
]);
check($db->inTransaction(), 'Registration should preserve an outer transaction.');
$nestedAssociation = $db->prepare('SELECT program_id FROM student_profiles WHERE user_id = :user_id');
$nestedAssociation->execute(['user_id' => $nestedUserId]);
check((int) $nestedAssociation->fetchColumn() === 1, 'Registration should assign the selected program to the student profile.');
$db->rollBack();
check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1, 'Rolling back the outer transaction should remove nested registration.');

foreach (['student', 'adviser', 'coordinator', 'supervisor', 'admin'] as $role) {
    check(role_is_authorized($role, [$role]), 'Each role should access its assigned dashboard.');
}
check(!role_is_authorized('student', ['adviser']), 'Students must not access adviser-only routes.');
check(!role_is_authorized('adviser', ['admin']), 'Advisers must not access administrator-only routes.');
check(!role_is_authorized('coordinator', ['admin']), 'Coordinators must not access administrator-only routes.');
check(!role_is_authorized('supervisor', ['student']), 'Supervisors must not access student-only routes.');
check(role_is_authorized('admin', ['student']), 'Administrator override should follow the defined policy.');
foreach ([
    'student' => 'dashboard/student',
    'adviser' => 'dashboard/adviser',
    'coordinator' => 'dashboard/coordinator',
    'supervisor' => 'dashboard/supervisor',
    'admin' => 'dashboard/admin',
] as $role => $dashboard) {
    check(dashboard_page_for_role($role) === $dashboard, 'Each role should redirect to its assigned dashboard.');
}

$_SERVER['HTTPS'] = 'on';
start_secure_session();
$oldSessionId = session_id();
$oldCsrfToken = csrf_token();
establish_authenticated_session($user);
check(session_id() !== $oldSessionId, 'Successful authentication must regenerate the session ID.');
check(authenticated_role() === 'student', 'Authenticated session should store the database role.');
check(csrf_token() !== $oldCsrfToken, 'Successful authentication must rotate the CSRF token.');
$middleware = new AuthMiddleware();
check(!$middleware->requireRole(['adviser']), 'Middleware must deny a student an adviser-only route.');
check(http_response_code() === 403, 'Denied role access should return HTTP 403.');

$csrfToken = csrf_token();
$_POST['csrf_token'] = $csrfToken;
check(verify_csrf_post(), 'A matching CSRF token should validate.');
$_POST['csrf_token'] = 'invalid-token';
check(!verify_csrf_post(), 'A mismatched CSRF token should fail validation.');
$_POST['csrf_token'] = ['invalid-token'];
check(!verify_csrf_post(), 'A non-string CSRF token should fail without a type warning.');
$_POST['csrf_token'] = $csrfToken;
check(ini_get('session.use_strict_mode') === '1', 'Strict session ID mode should be enabled.');
check(ini_get('session.use_only_cookies') === '1', 'Sessions should be restricted to cookies.');
check(ini_get('session.use_trans_sid') === '0', 'Session IDs must not be propagated in URLs.');
$cookieParameters = session_get_cookie_params();
$httpsValue = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
check($cookieParameters['secure'] === true && $httpsValue === 'on', 'HTTPS sessions should use Secure cookies.');
check($cookieParameters['httponly'] === true, 'Session cookies should be HttpOnly.');
check(($cookieParameters['samesite'] ?? '') === 'Lax', 'Session cookies should use SameSite=Lax.');

unset($_SERVER['HTTPS']);
$_SESSION['auth'] = [
    'user_id' => $userId,
    'role' => 'student',
    'login_at' => time() - 1900,
    'last_activity' => time() - 1801,
];
$oldSessionId = session_id();
start_secure_session();
check(!is_authenticated(), 'An idle session should expire after 30 minutes.');
check(session_id() !== $oldSessionId, 'An expired session should receive a new session ID.');

destroy_secure_session();
check(session_status() !== PHP_SESSION_ACTIVE, 'Logout should destroy the active PHP session.');

echo 'Authentication checks passed: ' . $checks . PHP_EOL;
