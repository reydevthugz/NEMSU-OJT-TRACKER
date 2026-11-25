<?php

declare(strict_types=1);

final class AdminModel
{
    public function __construct(private PDO $db)
    {
    }

    public function dashboardStats(): array
    {
        $statement = $this->db->query(
            "SELECT
                COUNT(*) AS total_users,
                COALESCE(SUM(role = 'student'), 0) AS total_students,
                COALESCE(SUM(role = 'adviser'), 0) AS total_advisers,
                COALESCE(SUM(role = 'coordinator'), 0) AS total_coordinators,
                COALESCE(SUM(role = 'supervisor'), 0) AS total_supervisors,
                (SELECT COUNT(*) FROM companies) AS total_companies,
                (SELECT COUNT(*) FROM programs) AS total_programs,
                (SELECT COUNT(*) FROM skills) AS total_skills,
                (SELECT COUNT(*) FROM company_positions) AS total_positions,
                (SELECT COUNT(*) FROM requirement_types) AS total_requirement_types
             FROM users"
        );

        return $statement->fetch();
    }

    public function users(array $filters, int $page, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $parameters = [];
        if ($filters['search'] !== '') {
            $where[] = '(u.name LIKE :name_search OR u.email LIKE :email_search OR sp.student_number LIKE :student_search)';
            $pattern = '%' . $filters['search'] . '%';
            $parameters['name_search'] = $pattern;
            $parameters['email_search'] = $pattern;
            $parameters['student_search'] = $pattern;
        }
        if ($filters['role'] !== '') {
            $where[] = 'u.role = :role';
            $parameters['role'] = $filters['role'];
        }
        if ($filters['status'] !== '') {
            $where[] = 'u.status = :status';
            $parameters['status'] = $filters['status'];
        }
        $condition = implode(' AND ', $where);

        return $this->paginate(
            'SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, sp.student_number
             FROM users AS u
             LEFT JOIN student_profiles AS sp ON sp.user_id = u.id
             WHERE ' . $condition . '
             ORDER BY u.created_at DESC, u.id DESC',
            'SELECT COUNT(*) FROM users AS u
             LEFT JOIN student_profiles AS sp ON sp.user_id = u.id
             WHERE ' . $condition,
            $parameters,
            $page,
            $perPage
        );
    }

    public function userDetails(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
                u.id, u.name, u.email, u.role, u.status, u.created_at, u.updated_at,
                sp.student_number, sp.year_level, sp.preferred_location,
                p.id AS program_id, p.code AS program_code, p.name AS program_name
             FROM users AS u
             LEFT JOIN student_profiles AS sp ON sp.user_id = u.id
             LEFT JOIN programs AS p ON p.id = sp.program_id
             WHERE u.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function userRoleAndStatus(int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT id, role, status FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function createStaffUser(string $name, string $email, string $role, string $password): int
    {
        if (!in_array($role, ['adviser', 'coordinator', 'supervisor', 'admin'], true)) {
            throw new InvalidArgumentException('Choose a staff role.');
        }

        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, password, role, status)
             VALUES (:name, :email, :password, :role, :status)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateUserAccount(int $userId, string $name, string $email, string $role, string $status): void
    {
        $existing = $this->userRoleAndStatus($userId);
        if ($existing === null) {
            throw new DomainException('The selected user account no longer exists.');
        }

        $statement = $this->db->prepare(
            'UPDATE users SET name = :name, email = :email, role = :role, status = :status WHERE id = :id'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'id' => $userId,
        ]);
    }

    public function programs(string $search, int $page, int $perPage = 10): array
    {
        $condition = '';
        $parameters = [];
        if ($search !== '') {
            $condition = ' WHERE code LIKE :code_search OR name LIKE :name_search';
            $pattern = '%' . $search . '%';
            $parameters = ['code_search' => $pattern, 'name_search' => $pattern];
        }

        return $this->paginate(
            'SELECT id, code, name, created_at, updated_at FROM programs' . $condition . ' ORDER BY name, id',
            'SELECT COUNT(*) FROM programs' . $condition,
            $parameters,
            $page,
            $perPage
        );
    }

    public function programDetails(int $programId): ?array
    {
        return $this->findById('programs', $programId, 'id, code, name, created_at, updated_at');
    }

    public function createProgram(string $code, string $name): int
    {
        $statement = $this->db->prepare('INSERT INTO programs (code, name) VALUES (:code, :name)');
        $statement->execute(['code' => $code, 'name' => $name]);

        return (int) $this->db->lastInsertId();
    }

    public function updateProgram(int $programId, string $code, string $name): void
    {
        $existing = $this->programDetails($programId);
        if ($existing === null) {
            throw new DomainException('The selected academic program no longer exists.');
        }
        if (strcasecmp((string) $existing['code'], 'BSCS') === 0) {
            throw new DomainException('The official BSCS program is protected and cannot be changed.');
        }
        if (strcasecmp($code, 'BSCS') === 0) {
            throw new DomainException('The BSCS program code is reserved for the official program.');
        }

        $statement = $this->db->prepare('UPDATE programs SET code = :code, name = :name WHERE id = :id');
        $statement->execute(['code' => $code, 'name' => $name, 'id' => $programId]);
    }

    public function companies(array $filters, int $page, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $parameters = [];
        if ($filters['search'] !== '') {
            $where[] = 'name LIKE :name_search';
            $parameters['name_search'] = '%' . $filters['search'] . '%';
        }
        if ($filters['status'] !== '') {
            $where[] = 'status = :status';
            $parameters['status'] = $filters['status'];
        }
        $condition = implode(' AND ', $where);

        return $this->paginate(
            'SELECT id, name, address, contact_name, contact_email, contact_phone, description, status, created_at
             FROM companies WHERE ' . $condition . ' ORDER BY name, id',
            'SELECT COUNT(*) FROM companies WHERE ' . $condition,
            $parameters,
            $page,
            $perPage
        );
    }

    public function companyDetails(int $companyId): ?array
    {
        return $this->findById(
            'companies',
            $companyId,
            'id, name, address, contact_name, contact_email, contact_phone, description, status, created_at, updated_at'
        );
    }

    public function companyOptions(): array
    {
        return $this->db->query('SELECT id, name, status FROM companies ORDER BY name, id')->fetchAll();
    }

    public function activeCompanyExists(int $companyId): bool
    {
        $statement = $this->db->prepare("SELECT 1 FROM companies WHERE id = :id AND status = 'active' LIMIT 1");
        $statement->execute(['id' => $companyId]);

        return $statement->fetchColumn() !== false;
    }

    public function createCompany(array $company): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO companies
                (name, address, contact_name, contact_email, contact_phone, description, status)
             VALUES
                (:name, :address, :contact_name, :contact_email, :contact_phone, :description, :status)'
        );
        $statement->execute($company);

        return (int) $this->db->lastInsertId();
    }

    public function updateCompany(int $companyId, array $company): void
    {
        if ($this->companyDetails($companyId) === null) {
            throw new DomainException('The selected company no longer exists.');
        }

        $company['id'] = $companyId;
        $statement = $this->db->prepare(
            'UPDATE companies
             SET name = :name, address = :address, contact_name = :contact_name,
                 contact_email = :contact_email, contact_phone = :contact_phone,
                 description = :description, status = :status
             WHERE id = :id'
        );
        $statement->execute($company);
    }

    public function supervisors(array $filters, int $page, int $perPage = 10): array
    {
        $where = ['u.role = :supervisor_role'];
        $parameters = ['supervisor_role' => 'supervisor'];
        if ($filters['search'] !== '') {
            $where[] = '(u.name LIKE :name_search OR u.email LIKE :email_search OR c.name LIKE :company_search)';
            $pattern = '%' . $filters['search'] . '%';
            $parameters['name_search'] = $pattern;
            $parameters['email_search'] = $pattern;
            $parameters['company_search'] = $pattern;
        }
        if ($filters['status'] !== '') {
            $where[] = 'u.status = :status';
            $parameters['status'] = $filters['status'];
        }
        $condition = implode(' AND ', $where);

        return $this->paginate(
            'SELECT cs.id, cs.company_id, cs.supervisor_user_id, c.name AS company_name,
                    u.name AS supervisor_name, u.email AS supervisor_email, u.status AS supervisor_status
             FROM company_supervisors AS cs
             INNER JOIN companies AS c ON c.id = cs.company_id
             INNER JOIN users AS u ON u.id = cs.supervisor_user_id
             WHERE ' . $condition . '
             ORDER BY c.name, u.name, cs.id',
            'SELECT COUNT(*)
             FROM company_supervisors AS cs
             INNER JOIN companies AS c ON c.id = cs.company_id
             INNER JOIN users AS u ON u.id = cs.supervisor_user_id
             WHERE ' . $condition,
            $parameters,
            $page,
            $perPage
        );
    }

    public function activeSupervisorUsers(): array
    {
        return $this->db->query(
            "SELECT id, name, email FROM users
             WHERE role = 'supervisor' AND status = 'active'
             ORDER BY name, id"
        )->fetchAll();
    }

    public function createSupervisorAssociation(int $companyId, int $supervisorUserId): int
    {
        $this->validateSupervisorAssociation($companyId, $supervisorUserId);
        $statement = $this->db->prepare(
            'INSERT INTO company_supervisors (company_id, supervisor_user_id)
             VALUES (:company_id, :supervisor_user_id)'
        );
        $statement->execute([
            'company_id' => $companyId,
            'supervisor_user_id' => $supervisorUserId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateSupervisorAssociation(int $associationId, int $companyId, int $supervisorUserId): void
    {
        $statement = $this->db->prepare('SELECT id FROM company_supervisors WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $associationId]);
        if ($statement->fetchColumn() === false) {
            throw new DomainException('The selected supervisor association no longer exists.');
        }

        $this->validateSupervisorAssociation($companyId, $supervisorUserId);
        $updateStatement = $this->db->prepare(
            'UPDATE company_supervisors
             SET company_id = :company_id, supervisor_user_id = :supervisor_user_id
             WHERE id = :id'
        );
        $updateStatement->execute([
            'company_id' => $companyId,
            'supervisor_user_id' => $supervisorUserId,
            'id' => $associationId,
        ]);
    }

    public function positions(array $filters, int $page, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $parameters = [];
        if ($filters['search'] !== '') {
            $where[] = '(cp.title LIKE :title_search OR c.name LIKE :company_search)';
            $pattern = '%' . $filters['search'] . '%';
            $parameters['title_search'] = $pattern;
            $parameters['company_search'] = $pattern;
        }
        if ($filters['status'] !== '') {
            $where[] = 'cp.status = :status';
            $parameters['status'] = $filters['status'];
        }
        if ($filters['company_id'] > 0) {
            $where[] = 'cp.company_id = :company_id';
            $parameters['company_id'] = (string) $filters['company_id'];
        }
        $condition = implode(' AND ', $where);
        $from = ' FROM company_positions AS cp
                  INNER JOIN companies AS c ON c.id = cp.company_id
                  LEFT JOIN (
                      SELECT position_id, COUNT(*) AS active_placements
                      FROM placements WHERE status = :active_placement_status
                      GROUP BY position_id
                  ) AS ap ON ap.position_id = cp.id';
        $parameters['active_placement_status'] = 'active';

        return $this->paginate(
            'SELECT cp.id, cp.company_id, c.name AS company_name, cp.title, cp.description,
                    cp.available_slots, COALESCE(ap.active_placements, 0) AS active_placements,
                    CASE WHEN cp.available_slots > COALESCE(ap.active_placements, 0)
                         THEN cp.available_slots - COALESCE(ap.active_placements, 0) ELSE 0 END AS remaining_slots,
                    cp.status
             ' . $from . ' WHERE ' . $condition . ' ORDER BY c.name, cp.title, cp.id',
            'SELECT COUNT(*) ' . $from . ' WHERE ' . $condition,
            $parameters,
            $page,
            $perPage
        );
    }

    public function positionDetails(int $positionId): ?array
    {
        return $this->findById(
            'company_positions',
            $positionId,
            'id, company_id, title, description, available_slots, status'
        );
    }

    public function createPosition(array $position): int
    {
        if (!$this->activeCompanyExists((int) $position['company_id'])) {
            throw new InvalidArgumentException('Choose an active registered company.');
        }

        $statement = $this->db->prepare(
            'INSERT INTO company_positions (company_id, title, description, available_slots, status)
             VALUES (:company_id, :title, :description, :available_slots, :status)'
        );
        $statement->execute($position);

        return (int) $this->db->lastInsertId();
    }

    public function updatePosition(int $positionId, array $position): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        } else {
            $this->db->exec('SAVEPOINT admin_position_update');
        }

        try {
            $lockSuffix = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $currentStatement = $this->db->prepare(
                'SELECT company_id FROM company_positions WHERE id = :id' . $lockSuffix
            );
            $currentStatement->execute(['id' => $positionId]);
            $current = $currentStatement->fetch();
            if ($current === false) {
                throw new DomainException('The selected position no longer exists.');
            }

            $placementStatement = $this->db->prepare(
                "SELECT COUNT(*) AS total_placements,
                        COALESCE(SUM(status = 'active'), 0) AS active_placements
                 FROM placements WHERE position_id = :position_id"
            );
            $placementStatement->execute(['position_id' => $positionId]);
            $placementCounts = $placementStatement->fetch();

            if (
                (int) $position['company_id'] !== (int) $current['company_id']
                && (int) $placementCounts['total_placements'] > 0
            ) {
                throw new DomainException('A position with placement history cannot be moved to another company.');
            }
            if ((int) $position['company_id'] !== (int) $current['company_id']
                && !$this->activeCompanyExists((int) $position['company_id'])) {
                throw new InvalidArgumentException('Choose an active registered company.');
            }
            if ((int) $position['available_slots'] < (int) $placementCounts['active_placements']) {
                throw new DomainException('Available slots cannot be lower than the number of active placements.');
            }

            $position['id'] = $positionId;
            $updateStatement = $this->db->prepare(
                'UPDATE company_positions
                 SET company_id = :company_id, title = :title, description = :description,
                     available_slots = :available_slots, status = :status
                 WHERE id = :id'
            );
            $updateStatement->execute($position);

            if ($ownsTransaction) {
                $this->db->commit();
            } else {
                $this->db->exec('RELEASE SAVEPOINT admin_position_update');
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            } elseif ($this->db->inTransaction()) {
                $this->db->exec('ROLLBACK TO SAVEPOINT admin_position_update');
                $this->db->exec('RELEASE SAVEPOINT admin_position_update');
            }

            throw $exception;
        }
    }

    public function skills(string $search, int $page, int $perPage = 10): array
    {
        $condition = '';
        $parameters = [];
        if ($search !== '') {
            $condition = ' WHERE name LIKE :name_search';
            $parameters['name_search'] = '%' . $search . '%';
        }

        return $this->paginate(
            'SELECT id, name, description, created_at, updated_at FROM skills' . $condition . ' ORDER BY name, id',
            'SELECT COUNT(*) FROM skills' . $condition,
            $parameters,
            $page,
            $perPage
        );
    }

    public function skillDetails(int $skillId): ?array
    {
        return $this->findById('skills', $skillId, 'id, name, description, created_at, updated_at');
    }

    public function createSkill(string $name, ?string $description): int
    {
        $statement = $this->db->prepare('INSERT INTO skills (name, description) VALUES (:name, :description)');
        $statement->execute(['name' => $name, 'description' => $description]);

        return (int) $this->db->lastInsertId();
    }

    public function updateSkill(int $skillId, string $name, ?string $description): void
    {
        if ($this->skillDetails($skillId) === null) {
            throw new DomainException('The selected skill no longer exists.');
        }

        $statement = $this->db->prepare('UPDATE skills SET name = :name, description = :description WHERE id = :id');
        $statement->execute(['name' => $name, 'description' => $description, 'id' => $skillId]);
    }

    public function requirementTypes(array $filters, int $page, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $parameters = [];
        if ($filters['search'] !== '') {
            $where[] = 'name LIKE :name_search';
            $parameters['name_search'] = '%' . $filters['search'] . '%';
        }
        if ($filters['status'] !== '') {
            $where[] = 'is_active = :is_active';
            $parameters['is_active'] = $filters['status'] === 'active' ? '1' : '0';
        }
        $condition = implode(' AND ', $where);

        return $this->paginate(
            'SELECT id, name, description, is_active, created_at, updated_at
             FROM requirement_types WHERE ' . $condition . ' ORDER BY name, id',
            'SELECT COUNT(*) FROM requirement_types WHERE ' . $condition,
            $parameters,
            $page,
            $perPage
        );
    }

    public function requirementTypeDetails(int $requirementTypeId): ?array
    {
        return $this->findById(
            'requirement_types',
            $requirementTypeId,
            'id, name, description, is_active, created_at, updated_at'
        );
    }

    public function createRequirementType(string $name, ?string $description, bool $isActive): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO requirement_types (name, description, is_active)
             VALUES (:name, :description, :is_active)'
        );
        $statement->execute([
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateRequirementType(int $requirementTypeId, string $name, ?string $description, bool $isActive): void
    {
        if ($this->requirementTypeDetails($requirementTypeId) === null) {
            throw new DomainException('The selected requirement type no longer exists.');
        }

        $statement = $this->db->prepare(
            'UPDATE requirement_types SET name = :name, description = :description, is_active = :is_active
             WHERE id = :id'
        );
        $statement->execute([
            'name' => $name,
            'description' => $description,
            'is_active' => $isActive ? 1 : 0,
            'id' => $requirementTypeId,
        ]);
    }

    private function validateSupervisorAssociation(int $companyId, int $supervisorUserId): void
    {
        $companyStatement = $this->db->prepare('SELECT 1 FROM companies WHERE id = :id LIMIT 1');
        $companyStatement->execute(['id' => $companyId]);
        if ($companyStatement->fetchColumn() === false) {
            throw new InvalidArgumentException('Choose a registered company.');
        }

        $supervisorStatement = $this->db->prepare(
            "SELECT 1 FROM users
             WHERE id = :id AND role = 'supervisor' AND status = 'active'
             LIMIT 1"
        );
        $supervisorStatement->execute(['id' => $supervisorUserId]);
        if ($supervisorStatement->fetchColumn() === false) {
            throw new InvalidArgumentException('Choose an active supervisor account.');
        }
    }

    private function findById(string $table, int $id, string $columns): ?array
    {
        $allowedTables = [
            'programs',
            'companies',
            'company_positions',
            'skills',
            'requirement_types',
        ];
        if (!in_array($table, $allowedTables, true)) {
            throw new InvalidArgumentException('Unsupported record type.');
        }

        $statement = $this->db->prepare("SELECT {$columns} FROM {$table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);
        $record = $statement->fetch();

        return $record === false ? null : $record;
    }

    private function paginate(
        string $selectSql,
        string $countSql,
        array $parameters,
        int $page,
        int $perPage
    ): array {
        $perPage = max(1, min(100, $perPage));
        $countStatement = $this->db->prepare($countSql);
        foreach ($parameters as $name => $value) {
            $countStatement->bindValue(':' . $name, $value);
        }
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($pages, $page));

        $statement = $this->db->prepare($selectSql . ' LIMIT :limit OFFSET :offset');
        foreach ($parameters as $name => $value) {
            $statement->bindValue(':' . $name, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }
}
