<?php

declare(strict_types=1);

final class PlacementModel
{
    public function __construct(private PDO $db)
    {
    }

    public function placements(string $search = '', string $status = '', int $limit = 100): array
    {
        $where = ['1 = 1'];
        $parameters = [];

        if ($search !== '') {
            $where[] = '(u.name LIKE :student_search OR u.email LIKE :email_search OR sp.student_number LIKE :number_search OR cp.title LIKE :position_search OR c.name LIKE :company_search)';
            $pattern = '%' . $search . '%';
            $parameters['student_search'] = $pattern;
            $parameters['email_search'] = $pattern;
            $parameters['number_search'] = $pattern;
            $parameters['position_search'] = $pattern;
            $parameters['company_search'] = $pattern;
        }
        if ($status !== '') {
            $where[] = 'p.status = :status';
            $parameters['status'] = $status;
        }

        $statement = $this->db->prepare(
            'SELECT p.id, p.status, p.required_hours, p.start_date, p.end_date,
                    sp.id AS student_profile_id, sp.student_number, sp.preferred_location,
                    u.name AS student_name, u.email AS student_email,
                    cp.id AS position_id, cp.title AS position_title,
                    c.id AS company_id, c.name AS company_name,
                    assigned.name AS assigned_by_name, approved.name AS approved_by_name
             FROM placements p
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             INNER JOIN users assigned ON assigned.id = p.assigned_by
             LEFT JOIN users approved ON approved.id = p.approved_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT :limit'
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function students(): array
    {
        $statement = $this->db->query(
            "SELECT sp.id AS student_profile_id, sp.student_number, sp.preferred_location,
                    u.id AS user_id, u.name, u.email, prog.code AS program_code,
                    COUNT(DISTINCT CASE WHEN p.status IN ('pending','approved','active') THEN p.id END) AS active_placements,
                    COUNT(DISTINCT student_skills.skill_id) AS skill_count
             FROM student_profiles sp
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN programs prog ON prog.id = sp.program_id
             LEFT JOIN placements p ON p.student_profile_id = sp.id
             LEFT JOIN student_skills ON student_skills.student_profile_id = sp.id
             WHERE u.status = 'active'
             GROUP BY sp.id, sp.student_number, sp.preferred_location, u.id, u.name, u.email, prog.code
             HAVING active_placements = 0
             ORDER BY u.name"
        );

        return $statement->fetchAll();
    }

    public function positions(): array
    {
        $statement = $this->db->query(
            "SELECT cp.id, cp.title, cp.description, cp.available_slots, c.id AS company_id,
                    c.name AS company_name, c.address,
                    COUNT(CASE WHEN p.status IN ('pending','approved','active') THEN 1 END) AS occupied_slots,
                    (cp.available_slots - COUNT(CASE WHEN p.status IN ('pending','approved','active') THEN 1 END)) AS remaining_slots
             FROM company_positions cp
             INNER JOIN companies c ON c.id = cp.company_id
             LEFT JOIN placements p ON p.position_id = cp.id
             WHERE cp.status = 'open' AND c.status = 'active'
             GROUP BY cp.id, cp.title, cp.description, cp.available_slots,
                      c.id, c.name, c.address
             HAVING remaining_slots > 0
             ORDER BY c.name, cp.title"
        );

        return $statement->fetchAll();
    }

    public function matches(int $studentProfileId): array
    {
        $sql = "SELECT cp.id, cp.title, cp.description, cp.available_slots,
                    c.id AS company_id, c.name AS company_name, c.address,
                    sp.preferred_location,
                    spref.priority, spref.status AS preference_status,
                    (SELECT COUNT(*) FROM position_skills ps
                     INNER JOIN student_skills ss ON ss.skill_id = ps.skill_id
                     WHERE ps.position_id = cp.id AND ss.student_profile_id = sp.id
                       AND ps.is_required = 1) AS matched_skills,
                    (SELECT COUNT(*) FROM position_skills ps
                     WHERE ps.position_id = cp.id AND ps.is_required = 1) AS required_skills,
                    (sp.preferred_location IS NOT NULL AND sp.preferred_location <> ''
                     AND c.address IS NOT NULL
                     AND c.address LIKE CONCAT('%', sp.preferred_location, '%')) AS location_match,
                    COUNT(CASE WHEN p.status IN ('pending','approved','active') THEN 1 END) AS occupied_slots,
                    (cp.available_slots - COUNT(CASE WHEN p.status IN ('pending','approved','active') THEN 1 END)) AS remaining_slots
             FROM company_positions cp
             INNER JOIN companies c ON c.id = cp.company_id
             INNER JOIN student_profiles sp ON sp.id = :student_profile_id
             LEFT JOIN student_preferences spref
               ON spref.student_profile_id = sp.id
              AND (spref.position_id = cp.id OR spref.company_id = c.id)
              AND spref.status = 'active'
             LEFT JOIN placements p ON p.position_id = cp.id
             WHERE cp.status = 'open' AND c.status = 'active'
             GROUP BY cp.id, cp.title, cp.description, cp.available_slots,
                      c.id, c.name, c.address, sp.preferred_location,
                      spref.priority, spref.status
             HAVING remaining_slots > 0
             ORDER BY (spref.position_id = cp.id) DESC,
                      spref.priority ASC,
                      location_match DESC,
                      (matched_skills / GREATEST(required_skills, 1)) DESC,
                      remaining_slots DESC,
                      c.name, cp.title";
        $statement = $this->db->prepare($sql);
        $statement->execute(['student_profile_id' => $studentProfileId]);

        return $statement->fetchAll();
    }

    public function assign(int $studentProfileId, int $positionId, int $requiredHours, ?string $startDate, ?string $endDate, int $assignedBy): int
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        } else {
            $this->db->exec('SAVEPOINT placement_assign');
        }

        try {
            $lockSuffix = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';

            $studentStatement = $this->db->prepare(
                "SELECT sp.id, sp.user_id, u.status
                 FROM student_profiles sp INNER JOIN users u ON u.id = sp.user_id
                 WHERE sp.id = :id" . $lockSuffix
            );
            $studentStatement->execute(['id' => $studentProfileId]);
            $student = $studentStatement->fetch();
            if ($student === false || $student['status'] !== 'active') {
                throw new DomainException('Choose an active student profile.');
            }

            $existingStatement = $this->db->prepare(
                "SELECT id FROM placements
                 WHERE student_profile_id = :student_profile_id
                   AND status IN ('pending','approved','active')
                 LIMIT 1" . $lockSuffix
            );
            $existingStatement->execute(['student_profile_id' => $studentProfileId]);
            if ($existingStatement->fetch() !== false) {
                throw new DomainException('This student already has a pending, approved, or active placement.');
            }

            $positionStatement = $this->db->prepare(
                'SELECT cp.id, cp.available_slots, cp.status AS position_status, c.status AS company_status
                 FROM company_positions cp INNER JOIN companies c ON c.id = cp.company_id
                 WHERE cp.id = :id' . $lockSuffix
            );
            $positionStatement->execute(['id' => $positionId]);
            $position = $positionStatement->fetch();
            if ($position === false || $position['position_status'] !== 'open' || $position['company_status'] !== 'active') {
                throw new DomainException('Choose an open position from an active company.');
            }

            $countStatement = $this->db->prepare(
                "SELECT COUNT(*) FROM placements
                 WHERE position_id = :position_id
                   AND status IN ('pending','approved','active')"
            );
            $countStatement->execute(['position_id' => $positionId]);
            if ((int) $countStatement->fetchColumn() >= (int) $position['available_slots']) {
                throw new DomainException('The selected position has no remaining capacity.');
            }

            $insert = $this->db->prepare(
                'INSERT INTO placements
                 (student_profile_id, position_id, assigned_by, required_hours, start_date, end_date, status)
                 VALUES (:student_profile_id, :position_id, :assigned_by, :required_hours, :start_date, :end_date, \'pending\')'
            );
            $insert->execute([
                'student_profile_id' => $studentProfileId,
                'position_id' => $positionId,
                'assigned_by' => $assignedBy,
                'required_hours' => $requiredHours,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
            $placementId = (int) $this->db->lastInsertId();

            $this->notify((int) $student['user_id'], 'OJT placement assigned',
                'A pending placement assignment has been created for you. A coordinator must review and approve it.');

            if ($ownsTransaction) {
                $this->db->commit();
            } else {
                $this->db->exec('RELEASE SAVEPOINT placement_assign');
            }

            return $placementId;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            } elseif ($this->db->inTransaction()) {
                $this->db->exec('ROLLBACK TO SAVEPOINT placement_assign');
                $this->db->exec('RELEASE SAVEPOINT placement_assign');
            }
            throw $exception;
        }
    }

    public function transition(int $placementId, string $newStatus, int $actorUserId): void
    {
        $allowed = [
            'approved' => ['pending'],
            'rejected' => ['pending', 'approved'],
            'active' => ['approved'],
            'completed' => ['active'],
            'cancelled' => ['pending', 'approved', 'active'],
        ];
        if (!isset($allowed[$newStatus])) {
            throw new DomainException('The requested placement status change is not available.');
        }

        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        } else {
            $this->db->exec('SAVEPOINT placement_transition');
        }

        try {
            $lockSuffix = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $this->db->prepare(
                'SELECT p.id, p.status, p.student_profile_id, sp.user_id,
                        cp.title AS position_title, c.name AS company_name
                 FROM placements p
                 INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
                 INNER JOIN company_positions cp ON cp.id = p.position_id
                 INNER JOIN companies c ON c.id = cp.company_id
                 WHERE p.id = :id' . $lockSuffix
            );
            $statement->execute(['id' => $placementId]);
            $placement = $statement->fetch();
            if ($placement === false) {
                throw new DomainException('The selected placement no longer exists.');
            }
            if (!in_array($placement['status'], $allowed[$newStatus], true)) {
                throw new DomainException("A {$placement['status']} placement cannot be changed to {$newStatus}.");
            }

            if ($newStatus === 'approved') {
                $update = $this->db->prepare('UPDATE placements SET status = :status, approved_by = :approved_by, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $update->execute(['status' => $newStatus, 'approved_by' => $actorUserId, 'id' => $placementId]);
            } else {
                $update = $this->db->prepare('UPDATE placements SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $update->execute(['status' => $newStatus, 'id' => $placementId]);
            }

            $history = $this->db->prepare(
                'INSERT INTO placement_status_history
                 (placement_id, previous_status, new_status, actor_user_id)
                 VALUES (:placement_id, :previous_status, :new_status, :actor_user_id)'
            );
            $history->execute([
                'placement_id' => $placementId,
                'previous_status' => $placement['status'],
                'new_status' => $newStatus,
                'actor_user_id' => $actorUserId,
            ]);

            $this->notify(
                (int) $placement['user_id'],
                'OJT placement updated',
                "Your placement at {$placement['company_name']} for {$placement['position_title']} is now {$newStatus}."
            );

            if ($ownsTransaction) {
                $this->db->commit();
            } else {
                $this->db->exec('RELEASE SAVEPOINT placement_transition');
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            } elseif ($this->db->inTransaction()) {
                $this->db->exec('ROLLBACK TO SAVEPOINT placement_transition');
                $this->db->exec('RELEASE SAVEPOINT placement_transition');
            }
            throw $exception;
        }
    }

    public function placementHistory(int $limit = 100): array
    {
        $statement = $this->db->prepare(
            'SELECT h.id, h.previous_status, h.new_status, h.remarks, h.created_at,
                    u.name AS actor_name, p.id AS placement_id,
                    student.name AS student_name, cp.title AS position_title, c.name AS company_name
             FROM placement_status_history h
             INNER JOIN users u ON u.id = h.actor_user_id
             INNER JOIN placements p ON p.id = h.placement_id
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN users student ON student.id = sp.user_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             ORDER BY h.created_at DESC, h.id DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    private function notify(int $userId, string $title, string $message): void
    {
        $statement = $this->db->prepare('INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)');
        $statement->execute(['user_id' => $userId, 'title' => $title, 'message' => $message]);
    }
}
