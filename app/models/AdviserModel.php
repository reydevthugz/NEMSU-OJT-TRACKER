<?php

declare(strict_types=1);

final class AdviserModel
{
    public function __construct(private PDO $db)
    {
    }

    public function assignmentOptions(): array
    {
        $advisers = $this->db->query(
            "SELECT id, name, email FROM users
             WHERE role = 'adviser' AND status = 'active'
             ORDER BY name"
        )->fetchAll();

        $students = $this->db->query(
            "SELECT sp.id, u.name, u.email, sp.student_number, programs.code AS program_code,
                    asa.adviser_user_id AS assigned_adviser_id
             FROM student_profiles sp
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN programs ON programs.id = sp.program_id
             LEFT JOIN adviser_student_assignments asa
               ON asa.student_profile_id = sp.id AND asa.status = 'active'
             WHERE u.status = 'active'
             ORDER BY u.name"
        )->fetchAll();

        return ['advisers' => $advisers, 'students' => $students];
    }

    public function assignments(): array
    {
        return $this->db->query(
            'SELECT asa.id, asa.status, asa.assigned_at, asa.ended_at,
                    adviser.name AS adviser_name, adviser.email AS adviser_email,
                    student.name AS student_name, student.email AS student_email,
                    sp.student_number, assigned.name AS assigned_by_name
             FROM adviser_student_assignments asa
             INNER JOIN users adviser ON adviser.id = asa.adviser_user_id
             INNER JOIN student_profiles sp ON sp.id = asa.student_profile_id
             INNER JOIN users student ON student.id = sp.user_id
             INNER JOIN users assigned ON assigned.id = asa.assigned_by
             ORDER BY asa.status DESC, adviser.name, student.name'
        )->fetchAll();
    }

    public function assign(int $adviserUserId, int $studentProfileId, int $assignedBy): int
    {
        $this->db->beginTransaction();
        try {
            $lockSuffix = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $check = $this->db->prepare(
                "SELECT u.role, u.status FROM users u WHERE u.id = :id" . $lockSuffix
            );
            $check->execute(['id' => $adviserUserId]);
            $adviser = $check->fetch();
            if ($adviser === false || $adviser['role'] !== 'adviser' || $adviser['status'] !== 'active') {
                throw new DomainException('Choose an active adviser account.');
            }

            $student = $this->db->prepare(
                'SELECT sp.id FROM student_profiles sp INNER JOIN users u ON u.id = sp.user_id
                 WHERE sp.id = :id AND u.status = :status' . $lockSuffix
            );
            $student->execute(['id' => $studentProfileId, 'status' => 'active']);
            if ($student->fetch() === false) {
                throw new DomainException('Choose an active student profile.');
            }

            $existing = $this->db->prepare(
                'SELECT id FROM adviser_student_assignments
                 WHERE student_profile_id = :student_profile_id AND status = :status' . $lockSuffix
            );
            $existing->execute(['student_profile_id' => $studentProfileId, 'status' => 'active']);
            $current = $existing->fetch();

            if ($current !== false) {
                $update = $this->db->prepare(
                    'UPDATE adviser_student_assignments
                     SET adviser_user_id = :adviser_user_id, assigned_by = :assigned_by,
                         assigned_at = CURRENT_TIMESTAMP, ended_at = NULL
                     WHERE id = :id'
                );
                $update->execute([
                    'adviser_user_id' => $adviserUserId,
                    'assigned_by' => $assignedBy,
                    'id' => (int) $current['id'],
                ]);
                $assignmentId = (int) $current['id'];
            } else {
                $insert = $this->db->prepare(
                    'INSERT INTO adviser_student_assignments
                     (adviser_user_id, student_profile_id, assigned_by, status)
                     VALUES (:adviser_user_id, :student_profile_id, :assigned_by, :status)'
                );
                $insert->execute([
                    'adviser_user_id' => $adviserUserId,
                    'student_profile_id' => $studentProfileId,
                    'assigned_by' => $assignedBy,
                    'status' => 'active',
                ]);
                $assignmentId = (int) $this->db->lastInsertId();
            }

            $this->notifyStudent($studentProfileId, 'Adviser assignment', 'An adviser has been assigned to monitor your OJT progress.');
            $this->db->commit();
            return $assignmentId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

    public function deactivate(int $assignmentId): void
    {
        $statement = $this->db->prepare(
            "UPDATE adviser_student_assignments
             SET status = 'inactive', ended_at = CURRENT_TIMESTAMP
             WHERE id = :id AND status = 'active'"
        );
        $statement->execute(['id' => $assignmentId]);
        if ($statement->rowCount() !== 1) {
            throw new DomainException('The assignment is already inactive or no longer exists.');
        }
    }

    public function assignedStudents(int $adviserUserId): array
    {
        $statement = $this->db->prepare(
            'SELECT sp.id AS student_profile_id, sp.student_number, sp.year_level,
                    u.id AS student_user_id, u.name, u.email,
                    programs.code AS program_code, programs.name AS program_name,
                    cp.title AS position_title, c.name AS company_name,
                    p.status AS placement_status, p.required_hours, p.start_date, p.end_date,
                    COALESCE(SUM(ar.total_hours), 0) AS recorded_hours
             FROM adviser_student_assignments asa
             INNER JOIN student_profiles sp ON sp.id = asa.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN programs ON programs.id = sp.program_id
             LEFT JOIN placements p ON p.student_profile_id = sp.id
               AND p.status IN (\'approved\',\'active\',\'completed\')
             LEFT JOIN company_positions cp ON cp.id = p.position_id
             LEFT JOIN companies c ON c.id = cp.company_id
             LEFT JOIN attendance_records ar ON ar.placement_id = p.id AND ar.status = \'verified\'
             WHERE asa.adviser_user_id = :adviser_user_id AND asa.status = \'active\'
             GROUP BY sp.id, sp.student_number, sp.year_level, u.id, u.name, u.email,
                      programs.code, programs.name, cp.title, c.name, p.status,
                      p.required_hours, p.start_date, p.end_date
             ORDER BY u.name'
        );
        $statement->execute(['adviser_user_id' => $adviserUserId]);

        return $statement->fetchAll();
    }

    private function notifyStudent(int $studentProfileId, string $title, string $message): void
    {
        $statement = $this->db->prepare(
            'SELECT user_id FROM student_profiles WHERE id = :id'
        );
        $statement->execute(['id' => $studentProfileId]);
        $userId = $statement->fetchColumn();
        if ($userId === false) return;

        $notification = $this->db->prepare(
            'INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)'
        );
        $notification->execute(['user_id' => (int) $userId, 'title' => $title, 'message' => $message]);
    }
}
