<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/AdviserModel.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

final class AdviserController
{
    public function __construct(private AdviserModel $model)
    {
    }

    public function adminPage(): array
    {
        return [
            'options' => $this->model->assignmentOptions(),
            'assignments' => $this->model->assignments(),
        ];
    }

    public function studentsPage(int $adviserUserId): array
    {
        return ['students' => $this->model->assignedStudents($adviserUserId)];
    }

    public function handleAdminAction(string $action): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        if (!verify_csrf_post()) {
            set_flash('error', 'Your form session expired. Please try again.');
            $this->redirect('admin/adviser-assignments');
        }

        try {
            $actorUserId = (int) (authenticated_user()['user_id'] ?? 0);
            if ($action === 'adviser_assignment_create') {
                $adviserUserId = $this->postInteger('adviser_user_id');
                $studentProfileId = $this->postInteger('student_profile_id');
                if ($adviserUserId <= 0 || $studentProfileId <= 0) {
                    throw new InvalidArgumentException('Select both an adviser and a student.');
                }
                $this->model->assign($adviserUserId, $studentProfileId, $actorUserId);
                set_flash('success', 'Adviser assignment saved.');
            } elseif ($action === 'adviser_assignment_deactivate') {
                $assignmentId = $this->postInteger('assignment_id');
                if ($assignmentId <= 0) {
                    throw new InvalidArgumentException('Select a valid assignment.');
                }
                $this->model->deactivate($assignmentId);
                set_flash('success', 'Adviser assignment ended.');
            } else {
                throw new DomainException('The requested adviser action is not available.');
            }
        } catch (DomainException | InvalidArgumentException $exception) {
            set_flash('error', $exception->getMessage());
        } catch (Throwable) {
            set_flash('error', 'The adviser assignment could not be saved.');
        }

        $this->redirect('admin/adviser-assignments');
    }

    private function postInteger(string $name): int
    {
        $value = $_POST[$name] ?? null;
        return filter_var($value, FILTER_VALIDATE_INT) ?: 0;
    }

    private function redirect(string $page): never
    {
        header('Location: ' . app_route_url($page, app_base_path()), true, 303);
        exit;
    }
}
