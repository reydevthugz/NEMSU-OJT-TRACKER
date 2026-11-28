<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

final class StudentController
{
    public function __construct(private User $model)
    {
    }

    public function updateProfile(): void
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
            $userId = (int) (authenticated_user()['user_id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $yearLevel = filter_var($_POST['year_level'] ?? null, FILTER_VALIDATE_INT) ?: 0;
            $preferredLocation = trim((string) ($_POST['preferred_location'] ?? ''));

            $this->model->updateStudentProfile($userId, $name, $email, $yearLevel, $preferredLocation);
            set_flash('success', 'Profile updated successfully.');
        } catch (DomainException | InvalidArgumentException $exception) {
            set_flash('error', $exception->getMessage());
        } catch (Throwable) {
            error_log('Student profile update failed.');
            set_flash('error', 'The profile could not be updated.');
        }

        $this->redirect();
    }

    private function redirect(): never
    {
        header('Location: ' . app_route_url('student/profile', app_base_path()), true, 303);
        exit;
    }
}
