<?php

declare(strict_types=1);

final class DashboardModel
{
    public function __construct(private PDO $db)
    {
    }

    public function forRole(string $role, int $userId): array
    {
        return match ($role) {
            'admin' => $this->administratorSummary(),
            'student' => $this->studentSummary($userId),
            'coordinator' => $this->coordinatorSummary($userId),
            'supervisor' => $this->supervisorSummary($userId),
            'adviser' => $this->adviserSummary($userId),
            default => throw new InvalidArgumentException('Unsupported dashboard role.'),
        };
    }

    public function studentPage(string $section, int $userId): array
    {
        if ($section === 'profile') {
            $statement = $this->db->prepare(
                'SELECT student_profiles.student_number, student_profiles.year_level,
                        student_profiles.preferred_location, programs.code AS program_code,
                        programs.name AS program_name
                 FROM student_profiles
                 INNER JOIN programs ON programs.id = student_profiles.program_id
                 WHERE student_profiles.user_id = :user_id
                 LIMIT 1'
            );
            $statement->execute(['user_id' => $userId]);

            return ['kind' => 'profile', 'profile' => $statement->fetch() ?: null];
        }

        $pageDefinitions = [
            'skills' => [
                'sql' => 'SELECT skills.name AS skill, student_skills.proficiency
                          FROM student_skills
                          INNER JOIN student_profiles ON student_profiles.id = student_skills.student_profile_id
                          INNER JOIN skills ON skills.id = student_skills.skill_id
                          WHERE student_profiles.user_id = :user_id
                          ORDER BY skills.name',
                'columns' => [
                    ['key' => 'skill', 'label' => 'Skill'],
                    ['key' => 'proficiency', 'label' => 'Proficiency (1-5)'],
                ],
                'empty_title' => 'No skills added',
                'empty_message' => 'There are no skills recorded for your profile.',
            ],
            'preferences' => [
                'sql' => "SELECT student_preferences.priority, companies.name AS company_name,
                                 company_positions.title AS position_title, student_preferences.status
                          FROM student_preferences
                          INNER JOIN student_profiles ON student_profiles.id = student_preferences.student_profile_id
                          LEFT JOIN companies ON companies.id = student_preferences.company_id
                          LEFT JOIN company_positions ON company_positions.id = student_preferences.position_id
                          WHERE student_profiles.user_id = :user_id
                          ORDER BY student_preferences.priority",
                'columns' => [
                    ['key' => 'priority', 'label' => 'Priority'],
                    ['key' => 'company_name', 'label' => 'Company'],
                    ['key' => 'position_title', 'label' => 'Position'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ],
                'empty_title' => 'No company preferences',
                'empty_message' => 'No company or position preferences are recorded for your profile.',
            ],
            'placement' => [
                'sql' => 'SELECT companies.name AS company_name, company_positions.title AS position_title,
                                 placements.status, placements.required_hours, placements.start_date, placements.end_date
                          FROM placements
                          INNER JOIN student_profiles ON student_profiles.id = placements.student_profile_id
                          INNER JOIN company_positions ON company_positions.id = placements.position_id
                          INNER JOIN companies ON companies.id = company_positions.company_id
                          WHERE student_profiles.user_id = :user_id
                          ORDER BY placements.created_at DESC, placements.id DESC',
                'columns' => [
                    ['key' => 'company_name', 'label' => 'Company'],
                    ['key' => 'position_title', 'label' => 'Position'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ['key' => 'required_hours', 'label' => 'Required hours'],
                    ['key' => 'start_date', 'label' => 'Start date'],
                    ['key' => 'end_date', 'label' => 'End date'],
                ],
                'empty_title' => 'No placement recorded',
                'empty_message' => 'Placement records linked to your profile will appear here.',
            ],
            'requirements' => [
                'sql' => 'SELECT requirement_types.name AS requirement, requirement_submissions.status,
                                 requirement_submissions.submitted_at, requirement_submissions.reviewed_at
                          FROM requirement_submissions
                          INNER JOIN student_profiles ON student_profiles.id = requirement_submissions.student_profile_id
                          INNER JOIN requirement_types ON requirement_types.id = requirement_submissions.requirement_type_id
                          WHERE student_profiles.user_id = :user_id
                          ORDER BY requirement_submissions.submitted_at DESC',
                'columns' => [
                    ['key' => 'requirement', 'label' => 'Requirement'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ['key' => 'submitted_at', 'label' => 'Submitted'],
                    ['key' => 'reviewed_at', 'label' => 'Reviewed'],
                ],
                'empty_title' => 'No requirement submissions',
                'empty_message' => 'Requirement submissions linked to your profile will appear here.',
            ],
            'attendance' => [
                'sql' => 'SELECT attendance_records.attendance_date, attendance_records.time_in,
                                 attendance_records.time_out, attendance_records.total_hours,
                                 attendance_records.status
                          FROM attendance_records
                          INNER JOIN placements ON placements.id = attendance_records.placement_id
                          INNER JOIN student_profiles ON student_profiles.id = placements.student_profile_id
                          WHERE student_profiles.user_id = :user_id
                          ORDER BY attendance_records.attendance_date DESC',
                'columns' => [
                    ['key' => 'attendance_date', 'label' => 'Date'],
                    ['key' => 'time_in', 'label' => 'Time in'],
                    ['key' => 'time_out', 'label' => 'Time out'],
                    ['key' => 'total_hours', 'label' => 'Recorded hours'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ],
                'empty_title' => 'No attendance records',
                'empty_message' => 'Attendance entries linked to your placements will appear here.',
            ],
            'progress' => [
                'sql' => 'SELECT companies.name AS company_name, company_positions.title AS position_title,
                                 placements.status, placements.required_hours,
                                 COALESCE(SUM(attendance_records.total_hours), 0) AS recorded_hours
                          FROM placements
                          INNER JOIN student_profiles ON student_profiles.id = placements.student_profile_id
                          INNER JOIN company_positions ON company_positions.id = placements.position_id
                          INNER JOIN companies ON companies.id = company_positions.company_id
                          LEFT JOIN attendance_records ON attendance_records.placement_id = placements.id
                           AND attendance_records.status = \'verified\'
                          WHERE student_profiles.user_id = :user_id
                          GROUP BY placements.id, companies.name, company_positions.title,
                                   placements.status, placements.required_hours, placements.created_at
                          ORDER BY placements.created_at DESC, placements.id DESC',
                'columns' => [
                    ['key' => 'company_name', 'label' => 'Company'],
                    ['key' => 'position_title', 'label' => 'Position'],
                    ['key' => 'status', 'label' => 'Placement status', 'type' => 'status'],
                    ['key' => 'recorded_hours', 'label' => 'Hours recorded'],
                    ['key' => 'required_hours', 'label' => 'Required hours'],
                ],
                'empty_title' => 'No progress records',
                'empty_message' => 'Recorded attendance totals will appear after a placement and attendance records exist.',
            ],
            'notifications' => [
                'sql' => 'SELECT id, title, message, read_at, created_at
                          FROM notifications
                          WHERE user_id = :user_id
                          ORDER BY created_at DESC, id DESC
                          LIMIT 50',
                'columns' => [
                    ['key' => 'title', 'label' => 'Notification'],
                    ['key' => 'message', 'label' => 'Message'],
                    ['key' => 'created_at', 'label' => 'Received'],
                    ['key' => 'read_at', 'label' => 'Read at'],
                ],
                'empty_title' => 'No notifications',
                'empty_message' => 'Notifications addressed to your account will appear here.',
            ],
        ];

        if (!isset($pageDefinitions[$section])) {
            throw new InvalidArgumentException('Unsupported student page.');
        }

        $definition = $pageDefinitions[$section];
        $statement = $this->db->prepare($definition['sql']);
        $statement->execute(['user_id' => $userId]);

        return [
            'kind' => 'table',
            'columns' => $definition['columns'],
            'rows' => $statement->fetchAll(),
            'empty_title' => $definition['empty_title'],
            'empty_message' => $definition['empty_message'],
        ];
    }

    private function administratorSummary(): array
    {
        $roleCounts = $this->db->query(
            'SELECT role, COUNT(*) AS total FROM users GROUP BY role'
        )->fetchAll();
        $usersByRole = [];
        foreach ($roleCounts as $row) {
            $usersByRole[(string) $row['role']] = (int) $row['total'];
        }

        return [
            'stats' => [
                $this->stat('Total users', $this->count('SELECT COUNT(*) FROM users'), 'Registered accounts', 'bi-people'),
                $this->stat('Students', $this->count('SELECT COUNT(*) FROM student_profiles'), 'Student profiles', 'bi-mortarboard'),
                $this->stat('Advisers', $usersByRole['adviser'] ?? 0, 'Accounts by role', 'bi-person-workspace'),
                $this->stat('Coordinators', $usersByRole['coordinator'] ?? 0, 'Accounts by role', 'bi-diagram-3'),
                $this->stat('Supervisors', $usersByRole['supervisor'] ?? 0, 'Accounts by role', 'bi-building'),
                $this->stat('Companies', $this->count('SELECT COUNT(*) FROM companies'), 'Company records', 'bi-buildings'),
                $this->stat('Academic programs', $this->count('SELECT COUNT(*) FROM programs'), 'Configured programs', 'bi-journal-bookmark'),
                $this->stat('Skills', $this->count('SELECT COUNT(*) FROM skills'), 'Skill records', 'bi-lightning'),
                $this->stat('Positions', $this->count('SELECT COUNT(*) FROM company_positions'), 'Company positions', 'bi-briefcase'),
                $this->stat('Requirement types', $this->count('SELECT COUNT(*) FROM requirement_types'), 'Configured types', 'bi-files'),
            ],
        ];
    }

    private function studentSummary(int $userId): array
    {
        $profileStatement = $this->db->prepare(
            'SELECT sp.id, sp.student_number, sp.year_level, sp.preferred_location,
                    programs.code AS program_code, programs.name AS program_name
             FROM student_profiles sp
             INNER JOIN programs ON programs.id = sp.program_id
             WHERE sp.user_id = :user_id
             LIMIT 1'
        );
        $profileStatement->execute(['user_id' => $userId]);
        $profile = $profileStatement->fetch();
        $profileId = $profile === false ? null : (int) $profile['id'];

        $placement = null;
        $skillCount = 0;
        $preferenceCount = 0;
        $requirementCount = 0;
        $attendanceHours = 0.0;

        if ($profileId !== null) {
            $placementStatement = $this->db->prepare(
                'SELECT placements.status, placements.required_hours, placements.start_date,
                        placements.end_date, company_positions.title AS position_title,
                        companies.name AS company_name
                 FROM placements
                 INNER JOIN company_positions ON company_positions.id = placements.position_id
                 INNER JOIN companies ON companies.id = company_positions.company_id
                 WHERE placements.student_profile_id = :profile_id
                 ORDER BY placements.created_at DESC, placements.id DESC
                 LIMIT 1'
            );
            $placementStatement->execute(['profile_id' => $profileId]);
            $placement = $placementStatement->fetch() ?: null;

            $skillCount = $this->count(
                'SELECT COUNT(*) FROM student_skills WHERE student_profile_id = :profile_id',
                ['profile_id' => $profileId]
            );
            $preferenceCount = $this->count(
                "SELECT COUNT(*) FROM student_preferences
                 WHERE student_profile_id = :profile_id AND status = 'active'",
                ['profile_id' => $profileId]
            );
            $requirementCount = $this->count(
                'SELECT COUNT(*) FROM requirement_submissions WHERE student_profile_id = :profile_id',
                ['profile_id' => $profileId]
            );
            $attendanceStatement = $this->db->prepare(
                'SELECT COALESCE(SUM(attendance_records.total_hours), 0)
                 FROM attendance_records
                 INNER JOIN placements ON placements.id = attendance_records.placement_id
                 WHERE placements.student_profile_id = :profile_id
                   AND attendance_records.status = \'verified\''
            );
            $attendanceStatement->execute(['profile_id' => $profileId]);
            $attendanceHours = (float) $attendanceStatement->fetchColumn();
        }

        return [
            'profile' => $profile === false ? null : $profile,
            'placement' => $placement,
            'recent_notifications' => $this->recentNotifications($userId),
            'stats' => [
                $this->stat('Placement status', $placement['status'] ?? 'Not assigned', 'Latest placement record', 'bi-briefcase'),
                $this->stat('Skills', $skillCount, 'Skills in your profile', 'bi-lightning'),
                $this->stat('Preferences', $preferenceCount, 'Active company preferences', 'bi-heart'),
                $this->stat('Requirements submitted', $requirementCount, 'Your submitted records', 'bi-files'),
                $this->stat('Hours recorded', number_format($attendanceHours, 2), 'From attendance records', 'bi-clock-history'),
                $this->stat(
                    'Unread notifications',
                    $this->count('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL', ['user_id' => $userId]),
                    'For your account',
                    'bi-bell'
                ),
            ],
        ];
    }

    private function coordinatorSummary(int $userId): array
    {
        return [
            'stats' => [
                $this->stat('Students', $this->count('SELECT COUNT(*) FROM student_profiles'), 'Registered profiles', 'bi-mortarboard'),
                $this->stat('Companies', $this->count('SELECT COUNT(*) FROM companies'), 'Company records', 'bi-buildings'),
                $this->stat(
                    'Open positions',
                    $this->count("SELECT COUNT(*) FROM company_positions WHERE status = 'open'"),
                    'Currently marked open',
                    'bi-briefcase'
                ),
                $this->stat('Placements', $this->count('SELECT COUNT(*) FROM placements'), 'All placement records', 'bi-diagram-3'),
                $this->stat(
                    'Pending placements',
                    $this->count("SELECT COUNT(*) FROM placements WHERE status = 'pending'"),
                    'Awaiting a decision',
                    'bi-hourglass-split'
                ),
                $this->stat(
                    'Submissions awaiting review',
                    $this->count("SELECT COUNT(*) FROM requirement_submissions WHERE status IN ('submitted', 'pending')"),
                    'Requirement submissions',
                    'bi-files'
                ),
            ],
            'placement_statuses' => $this->db->query(
                'SELECT status, COUNT(*) AS total FROM placements GROUP BY status ORDER BY status'
            )->fetchAll(),
            'recent_notifications' => $this->recentNotifications($userId),
        ];
    }

    private function supervisorSummary(int $userId): array
    {
        $parameters = ['user_id' => $userId];
        $companyCount = $this->count(
            'SELECT COUNT(*) FROM company_supervisors WHERE supervisor_user_id = :user_id',
            $parameters
        );
        $traineeCount = $this->count(
            'SELECT COUNT(DISTINCT placements.student_profile_id)
             FROM company_supervisors
             INNER JOIN company_positions ON company_positions.company_id = company_supervisors.company_id
             INNER JOIN placements ON placements.position_id = company_positions.id
             WHERE company_supervisors.supervisor_user_id = :user_id',
            $parameters
        );
        $activePlacementCount = $this->count(
            "SELECT COUNT(DISTINCT placements.id)
             FROM company_supervisors
             INNER JOIN company_positions ON company_positions.company_id = company_supervisors.company_id
             INNER JOIN placements ON placements.position_id = company_positions.id
             WHERE company_supervisors.supervisor_user_id = :user_id
               AND placements.status = 'active'",
            $parameters
        );
        $traineesStatement = $this->db->prepare(
            'SELECT users.name AS student_name, student_profiles.student_number,
                    companies.name AS company_name, company_positions.title AS position_title,
                    placements.status
             FROM company_supervisors
             INNER JOIN company_positions ON company_positions.company_id = company_supervisors.company_id
             INNER JOIN companies ON companies.id = company_positions.company_id
             INNER JOIN placements ON placements.position_id = company_positions.id
             INNER JOIN student_profiles ON student_profiles.id = placements.student_profile_id
             INNER JOIN users ON users.id = student_profiles.user_id
             WHERE company_supervisors.supervisor_user_id = :user_id
             ORDER BY placements.created_at DESC, placements.id DESC
             LIMIT 8'
        );
        $traineesStatement->execute($parameters);

        return [
            'stats' => [
                $this->stat('My companies', $companyCount, 'Companies linked to your account', 'bi-buildings'),
                $this->stat('Trainees', $traineeCount, 'Placements at your linked companies', 'bi-mortarboard'),
                $this->stat('Active placements', $activePlacementCount, 'At your linked companies', 'bi-briefcase'),
            ],
            'trainees' => $traineesStatement->fetchAll(),
            'recent_notifications' => $this->recentNotifications($userId),
        ];
    }

    private function adviserSummary(int $userId): array
    {
        $countStatement = $this->db->prepare(
            "SELECT COUNT(*) FROM adviser_student_assignments
             WHERE adviser_user_id = :user_id AND status = 'active'"
        );
        $countStatement->execute(['user_id' => $userId]);

        $studentsStatement = $this->db->prepare(
            "SELECT u.name, u.email, sp.student_number,
                    programs.code AS program_code,
                    p.status AS placement_status,
                    c.name AS company_name,
                    cp.title AS position_title
             FROM adviser_student_assignments asa
             INNER JOIN student_profiles sp ON sp.id = asa.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN programs ON programs.id = sp.program_id
             LEFT JOIN placements p ON p.student_profile_id = sp.id
               AND p.status IN ('approved','active','completed')
             LEFT JOIN company_positions cp ON cp.id = p.position_id
             LEFT JOIN companies c ON c.id = cp.company_id
             WHERE asa.adviser_user_id = :user_id AND asa.status = 'active'
             ORDER BY u.name
             LIMIT 10"
        );
        $studentsStatement->execute(['user_id' => $userId]);

        return [
            'assignment_relationship_available' => true,
            'assigned_student_count' => (int) $countStatement->fetchColumn(),
            'assigned_students' => $studentsStatement->fetchAll(),
            'recent_notifications' => $this->recentNotifications($userId),
        ];
    }

    private function recentNotifications(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT title, message, read_at, created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT 3'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    private function count(string $sql, array $parameters = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }

    private function stat(string $label, string|int $value, string $caption, string $icon): array
    {
        return compact('label', 'value', 'caption', 'icon');
    }
}
