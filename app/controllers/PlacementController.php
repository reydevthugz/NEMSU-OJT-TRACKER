<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/PlacementModel.php';
require_once dirname(__DIR__) . '/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

final class PlacementController
{
    public function __construct(private PlacementModel $model)
    {
    }

    public function pageData(): array
    {
        $search = $this->queryString('q', 100);
        $status = $this->queryChoice('status', ['pending', 'approved', 'rejected', 'active', 'completed', 'cancelled']);
        $studentProfileId = $this->queryInteger('student_id', 0);

        return [
            'placements' => $this->model->placements($search, $status),
            'students' => $this->model->students(),
            'positions' => $this->model->positions(),
            'matches' => $studentProfileId > 0 ? $this->model->matches($studentProfileId) : [],
            'selected_student_id' => $studentProfileId,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => ['pending', 'approved', 'rejected', 'active', 'completed', 'cancelled'],
            'history' => $this->model->placementHistory(),
        ];
    }

    public function handleAction(string $action): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!verify_csrf_post()) {
            set_flash('error', 'Your form session expired. Please try again.');
            $this->redirect();
        }

        try {
            $actorUserId = (int) (authenticated_user()['user_id'] ?? 0);

            $message = match ($action) {
                'placement_assign' => $this->assign($actorUserId),
                'placement_approve', 'placement_reject', 'placement_activate',
                'placement_complete', 'placement_cancel' => $this->transition($action, $actorUserId),
                default => throw new DomainException('The requested placement action is not available.'),
            };

            set_flash('success', $message);
            $this->redirect();
        } catch (DomainException | InvalidArgumentException $exception) {
            set_flash('error', $exception->getMessage());
            $this->redirect();
        } catch (Throwable) {
            error_log('Placement operation failed unexpectedly.');
            set_flash('error', 'The placement change could not be completed.');
            $this->redirect();
        }
    }

    private function assign(int $actorUserId): string
    {
        $studentProfileId = $this->postInteger('student_profile_id');
        $positionId = $this->postInteger('position_id');
        $requiredHours = $this->postInteger('required_hours');
        $startDate = $this->optionalPostDate('start_date');
        $endDate = $this->optionalPostDate('end_date');

        if ($studentProfileId <= 0 || $positionId <= 0) {
            throw new InvalidArgumentException('Select a student and an open position.');
        }
        if ($requiredHours < 1 || $requiredHours > 3000) {
            throw new InvalidArgumentException('Required hours must be between 1 and 3000.');
        }
        if ($startDate !== null && $endDate !== null && $endDate < $startDate) {
            throw new InvalidArgumentException('The end date cannot be earlier than the start date.');
        }

        $this->model->assign(
            $studentProfileId,
            $positionId,
            $requiredHours,
            $startDate,
            $endDate,
            $actorUserId
        );

        return 'Placement assigned and awaiting coordinator approval.';
    }

    private function transition(string $action, int $actorUserId): string
    {
        $placementId = $this->postInteger('placement_id');
        if ($placementId <= 0) {
            throw new InvalidArgumentException('Select a valid placement.');
        }

        $status = match ($action) {
            'placement_approve' => 'approved',
            'placement_reject' => 'rejected',
            'placement_activate' => 'active',
            'placement_complete' => 'completed',
            'placement_cancel' => 'cancelled',
        };

        $this->model->transition($placementId, $status, $actorUserId);

        return match ($status) {
            'approved' => 'Placement approved.',
            'rejected' => 'Placement rejected.',
            'active' => 'Placement activated.',
            'completed' => 'Placement completed.',
            'cancelled' => 'Placement cancelled.',
        };
    }

    private function redirect(): never
    {
        header('Location: ' . app_route_url('coordinator/placements', app_base_path()), true, 303);
        exit;
    }

    private function queryString(string $name, int $maxLength): string
    {
        $value = $_GET[$name] ?? '';
        $value = is_string($value) ? trim($value) : '';

        return substr($value, 0, $maxLength);
    }

    private function queryInteger(string $name, int $default): int
    {
        $value = $_GET[$name] ?? null;

        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => $default]]) ?: $default;
    }

    private function queryChoice(string $name, array $choices): string
    {
        $value = $_GET[$name] ?? '';

        return is_string($value) && in_array($value, $choices, true) ? $value : '';
    }

    private function postInteger(string $name): int
    {
        $value = $_POST[$name] ?? null;

        return filter_var($value, FILTER_VALIDATE_INT) ?: 0;
    }

    private function optionalPostDate(string $name): ?string
    {
        $value = $_POST[$name] ?? '';
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = trim($value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Enter valid start and end dates.');
        }

        return $date;
    }
}
