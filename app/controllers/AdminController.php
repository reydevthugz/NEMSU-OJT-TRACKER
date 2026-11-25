<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/AdminModel.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

final class AdminController
{
    private AdminModel $model;

    public function __construct(PDO $db)
    {
        $this->model = new AdminModel($db);
    }

    public function pageData(string $pageKey): array
    {
        if (!$this->requireCurrentAdministrator()) {
            return [];
        }

        $page = $this->queryInteger('p', 1);
        $search = $this->queryString('q', 100);

        return match ($pageKey) {
            'dashboard/admin' => ['stats' => $this->model->dashboardStats()],
            'admin/users' => $this->usersPage($page, $search),
            'admin/user' => $this->userPage(),
            'admin/programs' => $this->programsPage($page, $search),
            'admin/companies' => $this->companiesPage($page, $search),
            'admin/supervisors' => $this->supervisorsPage($page, $search),
            'admin/positions' => $this->positionsPage($page, $search),
            'admin/skills' => $this->skillsPage($page, $search),
            'admin/requirements' => $this->requirementsPage($page, $search),
            default => ['not_found' => true],
        };
    }

    public function handleAction(string $action, string $pageKey): void
    {
        if (!$this->requireCurrentAdministrator()) {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!verify_csrf_post()) {
            set_flash('error', 'Your form session expired. Please try again.');
            $this->redirectTo($pageKey);
        }

        $actionPages = [
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
        if (!isset($actionPages[$action]) || $actionPages[$action] !== $pageKey) {
            http_response_code(404);
            $this->redirectTo('error/404');
        }

        try {
            $redirectPage = $pageKey;
            $redirectParameters = [];
            $message = match ($action) {
                'admin_user_create' => $this->createStaffUser(),
                'admin_user_update' => $this->updateUser($redirectParameters),
                'admin_program_create' => $this->createProgram(),
                'admin_program_update' => $this->updateProgram(),
                'admin_company_create' => $this->createCompany(),
                'admin_company_update' => $this->updateCompany(),
                'admin_supervisor_create' => $this->createSupervisorAssociation(),
                'admin_supervisor_update' => $this->updateSupervisorAssociation(),
                'admin_position_create' => $this->createPosition(),
                'admin_position_update' => $this->updatePosition(),
                'admin_skill_create' => $this->createSkill(),
                'admin_skill_update' => $this->updateSkill(),
                'admin_requirement_create' => $this->createRequirementType(),
                'admin_requirement_update' => $this->updateRequirementType(),
                default => throw new DomainException('The requested administrative action is not available.'),
            };

            set_flash('success', $message);
            $this->redirectTo($redirectPage, $redirectParameters);
        } catch (DomainException | InvalidArgumentException $exception) {
            set_flash('error', $exception->getMessage());
            $this->redirectTo($pageKey, $this->editRedirectParameters($action));
        } catch (PDOException $exception) {
            $driverCode = $exception->errorInfo[1] ?? null;
            if ($driverCode === 1062) {
                set_flash('error', 'A record with the same unique value already exists.');
            } elseif ($driverCode === 1452) {
                set_flash('error', 'Select a valid related record before saving.');
            } else {
                error_log('Administrative database operation failed.');
                set_flash('error', 'The requested change could not be saved.');
            }
            $this->redirectTo($pageKey, $this->editRedirectParameters($action));
        } catch (Throwable) {
            error_log('Administrative operation failed unexpectedly.');
            set_flash('error', 'The requested change could not be completed.');
            $this->redirectTo($pageKey, $this->editRedirectParameters($action));
        }
    }

    private function usersPage(int $page, string $search): array
    {
        $role = $this->queryChoice('role', ['student', 'adviser', 'coordinator', 'supervisor', 'admin']);
        $status = $this->queryChoice('status', ['active', 'inactive', 'disabled']);

        return [
            'listing' => $this->model->users(compact('search', 'role', 'status'), $page),
            'filters' => compact('search', 'role', 'status'),
            'roles' => ['student', 'adviser', 'coordinator', 'supervisor', 'admin'],
            'statuses' => ['active', 'inactive', 'disabled'],
        ];
    }

    private function userPage(): array
    {
        $id = $this->queryInteger('id', 0);
        $user = $id > 0 ? $this->model->userDetails($id) : null;

        return $user === null
            ? ['not_found' => true]
            : [
                'user' => $user,
                'is_current_user' => (int) $user['id'] === (int) (authenticated_user()['user_id'] ?? 0),
                'roles' => ['student', 'adviser', 'coordinator', 'supervisor', 'admin'],
                'statuses' => ['active', 'inactive', 'disabled'],
            ];
    }

    private function programsPage(int $page, string $search): array
    {
        $editId = $this->queryInteger('edit_id', 0);
        $record = $editId > 0 ? $this->model->programDetails($editId) : null;

        return [
            'listing' => $this->model->programs($search, $page),
            'record' => $record,
            'edit_id' => $editId,
            'filters' => ['search' => $search],
            'not_found' => $editId > 0 && $record === null,
        ];
    }

    private function companiesPage(int $page, string $search): array
    {
        $status = $this->queryChoice('status', ['active', 'inactive']);
        $editId = $this->queryInteger('edit_id', 0);
        $viewId = $this->queryInteger('view_id', 0);
        $record = $editId > 0 ? $this->model->companyDetails($editId) : null;
        $viewRecord = $viewId > 0 ? $this->model->companyDetails($viewId) : null;

        return [
            'listing' => $this->model->companies(compact('search', 'status'), $page),
            'record' => $record,
            'view_record' => $viewRecord,
            'edit_id' => $editId,
            'view_id' => $viewId,
            'filters' => compact('search', 'status'),
            'statuses' => ['active', 'inactive'],
            'not_found' => ($editId > 0 && $record === null) || ($viewId > 0 && $viewRecord === null),
        ];
    }

    private function supervisorsPage(int $page, string $search): array
    {
        $status = $this->queryChoice('status', ['active', 'inactive', 'disabled']);

        return [
            'listing' => $this->model->supervisors(compact('search', 'status'), $page),
            'filters' => compact('search', 'status'),
            'statuses' => ['active', 'inactive', 'disabled'],
            'companies' => $this->model->companyOptions(),
            'supervisor_users' => $this->model->activeSupervisorUsers(),
        ];
    }

    private function positionsPage(int $page, string $search): array
    {
        $status = $this->queryChoice('status', ['open', 'closed', 'inactive']);
        $companyId = $this->queryInteger('company_id', 0);
        $editId = $this->queryInteger('edit_id', 0);
        $record = $editId > 0 ? $this->model->positionDetails($editId) : null;
        $filters = [
            'search' => $search,
            'status' => $status,
            'company_id' => $companyId,
        ];

        return [
            'listing' => $this->model->positions($filters, $page),
            'filters' => $filters,
            'record' => $record,
            'edit_id' => $editId,
            'companies' => $this->model->companyOptions(),
            'statuses' => ['open', 'closed', 'inactive'],
            'not_found' => $editId > 0 && $record === null,
        ];
    }

    private function skillsPage(int $page, string $search): array
    {
        $editId = $this->queryInteger('edit_id', 0);
        $record = $editId > 0 ? $this->model->skillDetails($editId) : null;

        return [
            'listing' => $this->model->skills($search, $page),
            'record' => $record,
            'edit_id' => $editId,
            'filters' => ['search' => $search],
            'not_found' => $editId > 0 && $record === null,
        ];
    }

    private function requirementsPage(int $page, string $search): array
    {
        $status = $this->queryChoice('status', ['active', 'inactive']);
        $editId = $this->queryInteger('edit_id', 0);
        $record = $editId > 0 ? $this->model->requirementTypeDetails($editId) : null;

        return [
            'listing' => $this->model->requirementTypes(compact('search', 'status'), $page),
            'record' => $record,
            'edit_id' => $editId,
            'filters' => compact('search', 'status'),
            'not_found' => $editId > 0 && $record === null,
        ];
    }

    private function createStaffUser(): string
    {
        $name = trim($this->postString('name'));
        $email = strtolower(trim($this->postString('email')));
        $role = $this->postString('role');
        $password = $this->postString('password');
        $confirmation = $this->postString('password_confirmation');
        if ($name === '' || $this->textLength($name) > 120) {
            throw new InvalidArgumentException('Enter a name of 1 to 120 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $this->textLength($email) > 190) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (!in_array($role, ['adviser', 'coordinator', 'supervisor', 'admin'], true)) {
            throw new InvalidArgumentException('Choose an authorized staff role.');
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('Set an initial password of 8 to 72 bytes.');
        }
        if (!hash_equals($password, $confirmation)) {
            throw new InvalidArgumentException('Password confirmation does not match.');
        }

        $this->model->createStaffUser($name, $email, $role, $password);

        return 'Staff account created. The password was stored as a hash.';
    }

    private function updateUser(array &$redirectParameters): string
    {
        $userId = $this->postInteger('user_id');
        $name = trim($this->postString('name'));
        $email = strtolower(trim($this->postString('email')));
        $role = $this->postString('role');
        $status = $this->postString('status');
        if ($userId < 1) {
            throw new InvalidArgumentException('Select a valid user account.');
        }
        if ($name === '' || $this->textLength($name) > 120) {
            throw new InvalidArgumentException('Enter a name of 1 to 120 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $this->textLength($email) > 190) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (!in_array($role, ['student', 'adviser', 'coordinator', 'supervisor', 'admin'], true)) {
            throw new InvalidArgumentException('Choose a valid system role.');
        }
        if (!in_array($status, ['active', 'inactive', 'disabled'], true)) {
            throw new InvalidArgumentException('Choose a valid account status.');
        }

        $currentUserId = (int) (authenticated_user()['user_id'] ?? 0);
        $currentAccount = $this->model->userRoleAndStatus($userId);
        if ($currentAccount === null) {
            throw new DomainException('The selected user account no longer exists.');
        }
        if (
            $userId === $currentUserId
            && ($role !== 'admin' || $status !== 'active')
        ) {
            throw new DomainException('You cannot demote or deactivate your own administrator account.');
        }
        if ($role === 'student' && $currentAccount['role'] !== 'student') {
            $details = $this->model->userDetails($userId);
            if ($details === null || $details['student_number'] === null) {
                throw new DomainException('An account without a student profile cannot be assigned the student role.');
            }
        }

        $this->model->updateUserAccount($userId, $name, $email, $role, $status);
        $redirectParameters['id'] = $userId;

        return 'User account updated.';
    }

    private function createProgram(): string
    {
        [$code, $name] = $this->validatedProgram();
        if (strcasecmp($code, 'BSCS') === 0) {
            throw new InvalidArgumentException('BSCS is reserved for the existing official program.');
        }
        $this->model->createProgram($code, $name);

        return 'Academic program added.';
    }

    private function updateProgram(): string
    {
        $id = $this->postInteger('record_id');
        if ($id < 1) {
            throw new InvalidArgumentException('Select a valid academic program.');
        }
        [$code, $name] = $this->validatedProgram();
        $this->model->updateProgram($id, $code, $name);

        return 'Academic program updated.';
    }

    private function createCompany(): string
    {
        $this->model->createCompany($this->validatedCompany());

        return 'Company added.';
    }

    private function updateCompany(): string
    {
        $id = $this->postInteger('record_id');
        if ($id < 1) {
            throw new InvalidArgumentException('Select a valid company.');
        }
        $this->model->updateCompany($id, $this->validatedCompany());

        return 'Company details updated.';
    }

    private function createSupervisorAssociation(): string
    {
        $companyId = $this->postInteger('company_id');
        $supervisorId = $this->postInteger('supervisor_user_id');
        if ($companyId < 1 || $supervisorId < 1) {
            throw new InvalidArgumentException('Select a company and active supervisor.');
        }
        $this->model->createSupervisorAssociation($companyId, $supervisorId);

        return 'Supervisor associated with the company.';
    }

    private function updateSupervisorAssociation(): string
    {
        $associationId = $this->postInteger('association_id');
        $companyId = $this->postInteger('company_id');
        $supervisorId = $this->postInteger('supervisor_user_id');
        if ($associationId < 1 || $companyId < 1 || $supervisorId < 1) {
            throw new InvalidArgumentException('Select a valid supervisor association.');
        }
        $this->model->updateSupervisorAssociation($associationId, $companyId, $supervisorId);

        return 'Supervisor association updated.';
    }

    private function createPosition(): string
    {
        $this->model->createPosition($this->validatedPosition());

        return 'Company position added.';
    }

    private function updatePosition(): string
    {
        $id = $this->postInteger('record_id');
        if ($id < 1) {
            throw new InvalidArgumentException('Select a valid company position.');
        }
        $this->model->updatePosition($id, $this->validatedPosition());

        return 'Company position updated.';
    }

    private function createSkill(): string
    {
        [$name, $description] = $this->validatedSkill();
        $this->model->createSkill($name, $description);

        return 'Skill added.';
    }

    private function updateSkill(): string
    {
        $id = $this->postInteger('record_id');
        if ($id < 1) {
            throw new InvalidArgumentException('Select a valid skill.');
        }
        [$name, $description] = $this->validatedSkill();
        $this->model->updateSkill($id, $name, $description);

        return 'Skill updated.';
    }

    private function createRequirementType(): string
    {
        [$name, $description, $isActive] = $this->validatedRequirementType();
        $this->model->createRequirementType($name, $description, $isActive);

        return 'Requirement type added.';
    }

    private function updateRequirementType(): string
    {
        $id = $this->postInteger('record_id');
        if ($id < 1) {
            throw new InvalidArgumentException('Select a valid requirement type.');
        }
        [$name, $description, $isActive] = $this->validatedRequirementType();
        $this->model->updateRequirementType($id, $name, $description, $isActive);

        return 'Requirement type updated.';
    }

    private function validatedProgram(): array
    {
        $code = strtoupper(trim($this->postString('code')));
        $name = trim($this->postString('name'));
        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{0,29}$/D', $code)) {
            throw new InvalidArgumentException('Use a program code of up to 30 letters, numbers, underscores, or hyphens.');
        }
        if ($name === '' || $this->textLength($name) > 150) {
            throw new InvalidArgumentException('Enter a program name of 1 to 150 characters.');
        }

        return [$code, $name];
    }

    private function validatedCompany(): array
    {
        $company = [
            'name' => trim($this->postString('name')),
            'address' => $this->nullablePostString('address'),
            'contact_name' => $this->nullablePostString('contact_name'),
            'contact_email' => strtolower(trim($this->postString('contact_email'))),
            'contact_phone' => $this->nullablePostString('contact_phone'),
            'description' => $this->nullablePostString('description'),
            'status' => $this->postString('status'),
        ];
        if ($company['name'] === '' || $this->textLength($company['name']) > 180) {
            throw new InvalidArgumentException('Enter a company name of 1 to 180 characters.');
        }
        if ($company['address'] !== null && $this->textLength($company['address']) > 255) {
            throw new InvalidArgumentException('Company address must be 255 characters or fewer.');
        }
        if ($company['contact_name'] !== null && $this->textLength($company['contact_name']) > 120) {
            throw new InvalidArgumentException('Contact name must be 120 characters or fewer.');
        }
        if (
            $company['contact_email'] !== ''
            && (!filter_var($company['contact_email'], FILTER_VALIDATE_EMAIL) || $this->textLength($company['contact_email']) > 190)
        ) {
            throw new InvalidArgumentException('Enter a valid contact email address.');
        }
        if ($company['contact_phone'] !== null && $this->textLength($company['contact_phone']) > 50) {
            throw new InvalidArgumentException('Contact phone must be 50 characters or fewer.');
        }
        if ($company['description'] !== null && $this->textLength($company['description']) > 10000) {
            throw new InvalidArgumentException('Company description is too long.');
        }
        if (!in_array($company['status'], ['active', 'inactive'], true)) {
            throw new InvalidArgumentException('Choose a valid company status.');
        }
        if ($company['contact_email'] === '') {
            $company['contact_email'] = null;
        }

        return $company;
    }

    private function validatedPosition(): array
    {
        $companyId = $this->postInteger('company_id');
        $slots = $this->postInteger('available_slots');
        $position = [
            'company_id' => $companyId,
            'title' => trim($this->postString('title')),
            'description' => $this->nullablePostString('description'),
            'available_slots' => $slots,
            'status' => $this->postString('status'),
        ];
        if ($companyId < 1) {
            throw new InvalidArgumentException('Choose a registered company.');
        }
        if ($position['title'] === '' || $this->textLength($position['title']) > 180) {
            throw new InvalidArgumentException('Enter a position title of 1 to 180 characters.');
        }
        if ($position['description'] !== null && $this->textLength($position['description']) > 10000) {
            throw new InvalidArgumentException('Position description is too long.');
        }
        if ($slots < 0 || $slots > 65535) {
            throw new InvalidArgumentException('Available slots must be between 0 and 65535.');
        }
        if (!in_array($position['status'], ['open', 'closed', 'inactive'], true)) {
            throw new InvalidArgumentException('Choose a valid position status.');
        }

        return $position;
    }

    private function validatedSkill(): array
    {
        $name = trim($this->postString('name'));
        $description = $this->nullablePostString('description');
        if ($name === '' || $this->textLength($name) > 120) {
            throw new InvalidArgumentException('Enter a skill name of 1 to 120 characters.');
        }
        if ($description !== null && $this->textLength($description) > 255) {
            throw new InvalidArgumentException('Skill description must be 255 characters or fewer.');
        }

        return [$name, $description];
    }

    private function validatedRequirementType(): array
    {
        $name = trim($this->postString('name'));
        $description = $this->nullablePostString('description');
        $activeValue = $this->postString('is_active');
        if ($name === '' || $this->textLength($name) > 150) {
            throw new InvalidArgumentException('Enter a requirement type name of 1 to 150 characters.');
        }
        if ($description !== null && $this->textLength($description) > 10000) {
            throw new InvalidArgumentException('Requirement description is too long.');
        }
        if (!in_array($activeValue, ['0', '1'], true)) {
            throw new InvalidArgumentException('Choose whether this requirement type is active.');
        }

        return [$name, $description, $activeValue === '1'];
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private function nullablePostString(string $key): ?string
    {
        $value = trim($this->postString($key));

        return $value === '' ? null : $value;
    }

    private function postInteger(string $key): int
    {
        $value = $_POST[$key] ?? null;
        if (!is_string($value) && !is_int($value)) {
            return -1;
        }
        $validated = filter_var($value, FILTER_VALIDATE_INT);

        return $validated === false ? -1 : $validated;
    }

    private function queryString(string $key, int $maxLength): string
    {
        $value = $_GET[$key] ?? '';
        if (!is_string($value)) {
            return '';
        }

        return substr(trim($value), 0, $maxLength);
    }

    private function queryInteger(string $key, int $default): int
    {
        $value = $_GET[$key] ?? null;
        if (!is_string($value) && !is_int($value)) {
            return $default;
        }
        $validated = filter_var($value, FILTER_VALIDATE_INT);

        return $validated === false ? $default : max(0, $validated);
    }

    private function queryChoice(string $key, array $allowed): string
    {
        $value = $this->queryString($key, 30);

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function textLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        $length = preg_match_all('/./us', $value);
        if ($length === false) {
            throw new InvalidArgumentException('Text input must be valid UTF-8.');
        }

        return $length;
    }

    private function editRedirectParameters(string $action): array
    {
        if ($action === 'admin_user_update') {
            $id = $this->postInteger('user_id');

            return $id > 0 ? ['id' => $id] : [];
        }
        if (in_array($action, ['admin_program_update', 'admin_company_update', 'admin_position_update', 'admin_skill_update', 'admin_requirement_update'], true)) {
            $id = $this->postInteger('record_id');

            return $id > 0 ? ['edit_id' => $id] : [];
        }

        return [];
    }

    private function redirectTo(string $page, array $parameters = []): never
    {
        $location = app_route_url($page, app_base_path());
        if ($parameters !== []) {
            $location .= '&' . http_build_query($parameters);
        }
        header('Location: ' . $location, true, 303);
        exit;
    }

    private function requireCurrentAdministrator(): bool
    {
        if (!(new AuthMiddleware())->requireRole(['admin'])) {
            return false;
        }

        $sessionUser = authenticated_user();
        $userId = (int) ($sessionUser['user_id'] ?? 0);
        $account = $userId > 0 ? $this->model->userRoleAndStatus($userId) : null;
        if ($account === null || $account['role'] !== 'admin' || $account['status'] !== 'active') {
            destroy_secure_session();
            start_secure_session();
            set_flash('error', 'Administrator access is no longer active. Please sign in again.');
            redirect_to_page('login');
        }

        return true;
    }

}
