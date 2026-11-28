<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/models/DashboardModel.php';
require_once dirname(__DIR__) . '/app/helpers/view.php';

$checks = 0;

function dashboard_check(bool $condition, string $message): void
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
$db->exec(
    "CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        email TEXT NOT NULL,
        role TEXT NOT NULL,
        status TEXT NOT NULL
    );
    CREATE TABLE programs (id INTEGER PRIMARY KEY, code TEXT NOT NULL, name TEXT NOT NULL);
    CREATE TABLE student_profiles (
        id INTEGER PRIMARY KEY,
        user_id INTEGER NOT NULL,
        program_id INTEGER NOT NULL,
        student_number TEXT NOT NULL,
        year_level INTEGER NOT NULL,
        preferred_location TEXT
    );
    CREATE TABLE companies (id INTEGER PRIMARY KEY, name TEXT NOT NULL);
    CREATE TABLE company_supervisors (
        id INTEGER PRIMARY KEY,
        company_id INTEGER NOT NULL,
        supervisor_user_id INTEGER NOT NULL
    );
    CREATE TABLE company_positions (
        id INTEGER PRIMARY KEY,
        company_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        status TEXT NOT NULL
    );
    CREATE TABLE skills (id INTEGER PRIMARY KEY, name TEXT NOT NULL);
    CREATE TABLE student_skills (
        id INTEGER PRIMARY KEY,
        student_profile_id INTEGER NOT NULL,
        skill_id INTEGER NOT NULL,
        proficiency INTEGER
    );
    CREATE TABLE student_preferences (
        id INTEGER PRIMARY KEY,
        student_profile_id INTEGER NOT NULL,
        company_id INTEGER,
        position_id INTEGER,
        priority INTEGER NOT NULL,
        status TEXT NOT NULL
    );
    CREATE TABLE placements (
        id INTEGER PRIMARY KEY,
        student_profile_id INTEGER NOT NULL,
        position_id INTEGER NOT NULL,
        status TEXT NOT NULL,
        required_hours INTEGER NOT NULL,
        start_date TEXT,
        end_date TEXT,
        created_at TEXT NOT NULL
    );
    CREATE TABLE requirement_types (id INTEGER PRIMARY KEY, name TEXT NOT NULL);
    CREATE TABLE requirement_submissions (
        id INTEGER PRIMARY KEY,
        student_profile_id INTEGER NOT NULL,
        requirement_type_id INTEGER NOT NULL,
        submitted_at TEXT NOT NULL,
        reviewed_at TEXT,
        status TEXT NOT NULL
    );
    CREATE TABLE attendance_records (
        id INTEGER PRIMARY KEY,
        placement_id INTEGER NOT NULL,
        attendance_date TEXT NOT NULL,
        time_in TEXT,
        time_out TEXT,
        total_hours REAL NOT NULL,
        status TEXT NOT NULL
    );
    CREATE TABLE adviser_student_assignments (
        id INTEGER PRIMARY KEY,
        adviser_user_id INTEGER NOT NULL,
        student_profile_id INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT 'active'
    );
    CREATE TABLE notifications (
        id INTEGER PRIMARY KEY,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        read_at TEXT,
        created_at TEXT NOT NULL
    )"
);
$db->exec(
    "INSERT INTO users (id, name, email, role, status) VALUES
        (1, 'Student One', 'one@example.test', 'student', 'active'),
        (2, 'Student Two', 'two@example.test', 'student', 'active'),
        (3, 'Supervisor', 'supervisor@example.test', 'supervisor', 'active'),
        (4, 'Administrator', 'admin@example.test', 'admin', 'active');
     INSERT INTO programs (id, code, name) VALUES (1, 'TEST', 'Test Program');
     INSERT INTO student_profiles (id, user_id, program_id, student_number, year_level) VALUES
        (1, 1, 1, 'ST-001', 3),
        (2, 2, 1, 'ST-002', 4);
     INSERT INTO companies (id, name) VALUES (1, 'Company One'), (2, 'Company Two');
     INSERT INTO company_supervisors (id, company_id, supervisor_user_id) VALUES (1, 1, 3);
     INSERT INTO company_positions (id, company_id, title, status) VALUES
        (1, 1, 'Intern One', 'open'),
        (2, 2, 'Intern Two', 'open');
     INSERT INTO skills (id, name) VALUES (1, 'Communication'), (2, 'Research');
     INSERT INTO student_skills (id, student_profile_id, skill_id, proficiency) VALUES
        (1, 1, 1, 4),
        (2, 2, 2, 3);
     INSERT INTO student_preferences (id, student_profile_id, company_id, position_id, priority, status) VALUES
        (1, 1, 1, 1, 1, 'active');
     INSERT INTO placements (id, student_profile_id, position_id, status, required_hours, created_at) VALUES
        (1, 1, 2, 'active', 300, '2026-01-02'),
        (2, 2, 1, 'pending', 300, '2026-01-03');
     INSERT INTO requirement_types (id, name) VALUES (1, 'Agreement');
     INSERT INTO requirement_submissions (id, student_profile_id, requirement_type_id, submitted_at, status) VALUES
        (1, 1, 1, '2026-01-04', 'submitted'),
        (2, 2, 1, '2026-01-05', 'approved');
     INSERT INTO attendance_records (id, placement_id, attendance_date, time_in, time_out, total_hours, status)
        VALUES (1, 1, '2026-01-06', '08:00', '16:30', 8.5, 'verified');
     INSERT INTO notifications (id, user_id, title, message, read_at, created_at) VALUES
        (1, 1, 'Profile update', 'Your profile is ready.', NULL, '2026-01-07'),
        (2, 2, 'Other notice', 'This notice belongs to another account.', NULL, '2026-01-08'),
        (3, 3, 'Supervisor notice', 'This notice belongs to the supervisor.', NULL, '2026-01-09')"
);

$model = new DashboardModel($db);
$student = $model->forRole('student', 1);
dashboard_check($student['profile']['student_number'] === 'ST-001', 'Student profile data must be scoped to the current user.');
dashboard_check((int) $student['stats'][1]['value'] === 1, 'Student skill totals must use only the current profile.');
dashboard_check((int) $student['stats'][3]['value'] === 1, 'Requirement totals must use only the current profile.');
dashboard_check($student['stats'][4]['value'] === '8.50', 'Attendance totals should use stored hours for the current profile only.');
dashboard_check((int) $student['stats'][5]['value'] === 1, 'Unread notifications must be scoped to the current user.');
dashboard_check($student['recent_notifications'][0]['title'] === 'Profile update', 'Recent dashboard notifications must be scoped to the current user.');
dashboard_check($student['placement']['company_name'] === 'Company Two', 'Placement details must belong to the current student.');
dashboard_check(count($model->studentPage('skills', 1)['rows']) === 1, 'Student skill pages must be scoped to the current user.');
dashboard_check(count($model->studentPage('preferences', 1)['rows']) === 1, 'Student preference pages must be scoped to the current user.');
dashboard_check(count($model->studentPage('placement', 1)['rows']) === 1, 'Student placement pages must be scoped to the current user.');
dashboard_check(count($model->studentPage('requirements', 1)['rows']) === 1, 'Student requirement pages must be scoped to the current user.');
dashboard_check(count($model->studentPage('attendance', 1)['rows']) === 1, 'Student attendance pages must be scoped through the student placement.');
dashboard_check($model->studentPage('progress', 1)['rows'][0]['recorded_hours'] == 8.5, 'Progress must use stored attendance totals without deriving hours.');
dashboard_check($model->studentPage('notifications', 1)['rows'][0]['title'] === 'Profile update', 'Student notifications must be scoped to the current user.');
dashboard_check($model->studentPage('profile', 1)['profile']['student_number'] === 'ST-001', 'Student profile pages must be scoped to the current user.');

$supervisor = $model->forRole('supervisor', 3);
dashboard_check((int) $supervisor['stats'][0]['value'] === 1, 'Supervisor company totals must use the current supervisor link.');
dashboard_check((int) $supervisor['stats'][1]['value'] === 1, 'Supervisor trainee totals must be limited to linked companies.');
dashboard_check(count($supervisor['trainees']) === 1, 'Supervisor trainee rows must not include other companies.');
dashboard_check($supervisor['trainees'][0]['company_name'] === 'Company One', 'Supervisor trainee data must be company-scoped.');
dashboard_check($supervisor['recent_notifications'][0]['title'] === 'Supervisor notice', 'Supervisor notifications must be scoped to the current account.');

$admin = $model->forRole('admin', 4);
dashboard_check((int) $admin['stats'][0]['value'] === 4, 'Administrator totals should reflect actual user counts.');
dashboard_check($model->forRole('adviser', 2)['assignment_relationship_available'] === true, 'Adviser view must expose the assignment relationship when it is available.');

$status = 'inactive';
ob_start();
require dirname(__DIR__) . '/app/views/components/status-badge.php';
$inactiveBadge = ob_get_clean();
dashboard_check(str_contains($inactiveBadge, 'status-danger'), 'Inactive records must not receive the active/success badge style.');

echo 'Dashboard read-model checks passed: ' . $checks . PHP_EOL;
