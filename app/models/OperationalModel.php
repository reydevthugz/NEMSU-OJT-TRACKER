<?php

declare(strict_types=1);

final class OperationalModel
{
    public function __construct(private PDO $db)
    {
    }

    public function activePlacementForStudent(int $studentUserId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT p.id, p.required_hours, cp.title AS position_title, c.name AS company_name,
                    sp.id AS student_profile_id
             FROM placements p
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             WHERE sp.user_id = :user_id AND p.status = :status
             ORDER BY p.id DESC LIMIT 1'
        );
        $statement->execute(['user_id' => $studentUserId, 'status' => 'active']);
        return $statement->fetch() ?: null;
    }

    public function submitAttendance(int $studentUserId, string $date, ?string $timeIn, ?string $timeOut): void
    {
        $placement = $this->activePlacementForStudent($studentUserId);
        if ($placement === null) throw new DomainException('You need an active placement before submitting attendance.');
        $this->validateDate($date);

        $totalHours = 0.0;
        if ($timeIn !== null && $timeOut !== null) {
            $start = DateTimeImmutable::createFromFormat('H:i', $timeIn);
            $end = DateTimeImmutable::createFromFormat('H:i', $timeOut);
            if (!$start || !$end || $end <= $start) throw new InvalidArgumentException('Time out must be later than time in.');
            $totalHours = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            if ($totalHours > 24) throw new InvalidArgumentException('Attendance cannot exceed 24 hours.');
        } elseif (($timeIn === null) !== ($timeOut === null)) {
            throw new InvalidArgumentException('Enter both time in and time out, or leave both blank.');
        }

        $this->db->beginTransaction();
        try {
            $check = $this->db->prepare('SELECT id FROM attendance_records WHERE placement_id = :placement_id AND attendance_date = :date FOR UPDATE');
            $check->execute(['placement_id' => (int) $placement['id'], 'date' => $date]);
            $existing = $check->fetch();

            if ($existing !== false) {
                $update = $this->db->prepare(
                    'UPDATE attendance_records SET time_in = :time_in, time_out = :time_out,
                     total_hours = :total_hours, status = :status, remarks = NULL,
                     verified_by = NULL, verified_at = NULL, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id'
                );
                $update->execute([
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'total_hours' => round($totalHours, 2),
                    'status' => 'pending',
                    'id' => (int) $existing['id'],
                ]);
            } else {
                $insert = $this->db->prepare(
                    'INSERT INTO attendance_records
                     (placement_id, attendance_date, time_in, time_out, total_hours, status)
                     VALUES (:placement_id, :date, :time_in, :time_out, :total_hours, :status)'
                );
                $insert->execute([
                    'placement_id' => (int) $placement['id'],
                    'date' => $date,
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'total_hours' => round($totalHours, 2),
                    'status' => 'pending',
                ]);
            }

            $this->notifyLinkedSupervisor((int) $placement['id'], 'Attendance submitted', 'A trainee submitted attendance for verification.');
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

    public function linkedAttendanceRecords(int $supervisorUserId): array
    {
        $statement = $this->db->prepare(
            'SELECT ar.id, ar.attendance_date, ar.time_in, ar.time_out, ar.total_hours,
                    ar.status, ar.remarks, ar.verified_at,
                    u.name AS student_name, sp.student_number,
                    cp.title AS position_title, c.name AS company_name
             FROM attendance_records ar
             INNER JOIN placements p ON p.id = ar.placement_id
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             INNER JOIN company_supervisors cs ON cs.company_id = c.id
             WHERE cs.supervisor_user_id = :supervisor_user_id
             ORDER BY ar.status = \'pending\' DESC, ar.attendance_date DESC, ar.id DESC'
        );
        $statement->execute(['supervisor_user_id' => $supervisorUserId]);
        return $statement->fetchAll();
    }

    public function reviewAttendance(int $attendanceId, int $supervisorUserId, string $status, string $remarks): void
    {
        if (!in_array($status, ['verified', 'rejected'], true)) throw new DomainException('Invalid attendance review status.');

        $statement = $this->db->prepare(
            'SELECT ar.id, ar.status, ar.placement_id, sp.user_id AS student_user_id
             FROM attendance_records ar
             INNER JOIN placements p ON p.id = ar.placement_id
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             INNER JOIN company_supervisors cs ON cs.company_id = c.id
             WHERE ar.id = :attendance_id AND cs.supervisor_user_id = :supervisor_user_id
             LIMIT 1'
        );
        $statement->execute(['attendance_id' => $attendanceId, 'supervisor_user_id' => $supervisorUserId]);
        $record = $statement->fetch();
        if ($record === false) throw new DomainException('You are not authorized to review this attendance record.');
        if ($record['status'] !== 'pending') throw new DomainException('This attendance record has already been reviewed.');

        $update = $this->db->prepare(
            'UPDATE attendance_records
             SET status = :status, remarks = :remarks, verified_by = :verified_by,
                 verified_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $update->execute([
            'status' => $status,
            'remarks' => $remarks !== '' ? substr($remarks, 0, 255) : null,
            'verified_by' => $supervisorUserId,
            'id' => $attendanceId,
        ]);

        $notification = $this->db->prepare('INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)');
        $notification->execute([
            'user_id' => (int) $record['student_user_id'],
            'title' => 'Attendance reviewed',
            'message' => 'Your attendance record was ' . $status . '.',
        ]);
    }

    public function submitEvaluation(int $supervisorUserId, int $placementId, int $rating, string $comments, string $date): void
    {
        if ($rating < 1 || $rating > 5) throw new InvalidArgumentException('Rating must be between 1 and 5.');
        $this->validateDate($date);

        $statement = $this->db->prepare(
            'SELECT p.id, sp.user_id AS student_user_id
             FROM placements p
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             INNER JOIN company_supervisors cs ON cs.company_id = c.id
             WHERE p.id = :placement_id AND cs.supervisor_user_id = :supervisor_user_id
             LIMIT 1'
        );
        $statement->execute(['placement_id' => $placementId, 'supervisor_user_id' => $supervisorUserId]);
        $placement = $statement->fetch();
        if ($placement === false) throw new DomainException('You are not authorized to evaluate this placement.');

        $insert = $this->db->prepare(
            'INSERT INTO evaluations (placement_id, supervisor_id, rating, comments, evaluation_date)
             VALUES (:placement_id, :supervisor_id, :rating, :comments, :evaluation_date)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comments = VALUES(comments),
             evaluation_date = VALUES(evaluation_date), updated_at = CURRENT_TIMESTAMP'
        );
        $insert->execute([
            'placement_id' => $placementId,
            'supervisor_id' => $supervisorUserId,
            'rating' => $rating,
            'comments' => $comments !== '' ? substr($comments, 0, 5000) : null,
            'evaluation_date' => $date,
        ]);

        $notification = $this->db->prepare('INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)');
        $notification->execute([
            'user_id' => (int) $placement['student_user_id'],
            'title' => 'OJT evaluation submitted',
            'message' => 'Your company supervisor submitted an evaluation for your placement.',
        ]);
    }

    public function linkedPlacementsForSupervisor(int $supervisorUserId): array
    {
        $statement = $this->db->prepare(
            'SELECT p.id, p.status, p.required_hours, u.name AS student_name,
                    sp.student_number, cp.title AS position_title, c.name AS company_name,
                    e.rating, e.comments, e.evaluation_date
             FROM placements p
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             INNER JOIN company_supervisors cs ON cs.company_id = c.id
             LEFT JOIN evaluations e ON e.placement_id = p.id AND e.supervisor_id = cs.supervisor_user_id
             WHERE cs.supervisor_user_id = :supervisor_user_id
             ORDER BY p.created_at DESC, p.id DESC'
        );
        $statement->execute(['supervisor_user_id' => $supervisorUserId]);
        return $statement->fetchAll();
    }

    public function submitRequirement(int $studentUserId, int $requirementTypeId, string $filePath): void
    {
        $statement = $this->db->prepare('SELECT id FROM student_profiles WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $studentUserId]);
        $profileId = $statement->fetchColumn();
        if ($profileId === false) throw new DomainException('Student profile is required.');

        $type = $this->db->prepare('SELECT id FROM requirement_types WHERE id = :id AND is_active = 1');
        $type->execute(['id' => $requirementTypeId]);
        if ($type->fetch() === false) throw new DomainException('Select an active requirement type.');

        $insert = $this->db->prepare(
            'INSERT INTO requirement_submissions
             (student_profile_id, requirement_type_id, file_path, status, submitted_at)
             VALUES (:profile_id, :type_id, :file_path, :status, CURRENT_TIMESTAMP)'
        );
        $insert->execute([
            'profile_id' => (int) $profileId,
            'type_id' => $requirementTypeId,
            'file_path' => substr($filePath, 0, 255),
            'status' => 'submitted',
        ]);
    }

    public function reviewRequirement(int $submissionId, int $reviewerUserId, string $status, string $remarks): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) throw new DomainException('Invalid requirement review status.');
        $statement = $this->db->prepare(
            'SELECT rs.id, rs.status, sp.user_id AS student_user_id
             FROM requirement_submissions rs
             INNER JOIN student_profiles sp ON sp.id = rs.student_profile_id
             WHERE rs.id = :id LIMIT 1 FOR UPDATE'
        );
        $statement->execute(['id' => $submissionId]);
        $submission = $statement->fetch();
        if ($submission === false) throw new DomainException('The requirement submission no longer exists.');

        $update = $this->db->prepare(
            'UPDATE requirement_submissions SET status = :status, reviewed_by = :reviewer,
             remarks = :remarks, reviewed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $update->execute([
            'status' => $status,
            'reviewer' => $reviewerUserId,
            'remarks' => $remarks !== '' ? substr($remarks, 0, 5000) : null,
            'id' => $submissionId,
        ]);

        $notification = $this->db->prepare('INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)');
        $notification->execute([
            'user_id' => (int) $submission['student_user_id'],
            'title' => 'Requirement reviewed',
            'message' => 'Your requirement submission was ' . $status . '.',
        ]);
    }

    public function unreadNotifications(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, title, message, created_at FROM notifications
             WHERE user_id = :user_id AND read_at IS NULL
             ORDER BY created_at DESC, id DESC LIMIT 50'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function markNotificationRead(int $notificationId, int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id AND read_at IS NULL'
        );
        $statement->execute(['id' => $notificationId, 'user_id' => $userId]);
    }

    public function markAllNotificationsRead(int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND read_at IS NULL'
        );
        $statement->execute(['user_id' => $userId]);
    }

    public function requirementSubmissions(): array
    {
        $statement = $this->db->query(
            'SELECT rs.id, rs.file_path, rs.status, rs.submitted_at, rs.remarks,
                    rt.name AS requirement_name, u.name AS student_name,
                    sp.student_number, reviewer.name AS reviewed_by_name, rs.reviewed_at
             FROM requirement_submissions rs
             INNER JOIN requirement_types rt ON rt.id = rs.requirement_type_id
             INNER JOIN student_profiles sp ON sp.id = rs.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             LEFT JOIN users reviewer ON reviewer.id = rs.reviewed_by
             ORDER BY rs.status = \'submitted\' DESC, rs.submitted_at DESC, rs.id DESC'
        );
        return $statement->fetchAll();
    }

    public function activeRequirementTypes(): array
    {
        return $this->db->query(
            'SELECT id, name, description FROM requirement_types WHERE is_active = 1 ORDER BY name'
        )->fetchAll();
    }

    private function notifyLinkedSupervisor(int $placementId, string $title, string $message): void
    {
        $statement = $this->db->prepare(
            'SELECT DISTINCT cs.supervisor_user_id
             FROM placements p
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN company_supervisors cs ON cs.company_id = cp.company_id
             WHERE p.id = :placement_id'
        );
        $statement->execute(['placement_id' => $placementId]);
        $notification = $this->db->prepare('INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)');
        foreach ($statement->fetchAll() as $supervisor) {
            $notification->execute(['user_id' => (int) $supervisor['supervisor_user_id'], 'title' => $title, 'message' => $message]);
        }
    }

    private function validateDate(string $date): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new InvalidArgumentException('Enter a valid date.');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new InvalidArgumentException('Enter a valid date.');
    }
}
