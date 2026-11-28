<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/OperationalModel.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

final class OperationalController
{
    public function __construct(private OperationalModel $model)
    {
    }

    public function handle(string $action): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        if (!verify_csrf_post()) {
            set_flash('error', 'Your form session expired. Please try again.');
            $this->redirectByAction($action);
        }

        try {
            $userId = (int) (authenticated_user()['user_id'] ?? 0);
            $message = match ($action) {
                'attendance_submit' => $this->attendanceSubmit($userId),
                'attendance_review' => $this->attendanceReview($userId),
                'evaluation_submit' => $this->evaluationSubmit($userId),
                'requirement_submit' => $this->requirementSubmit($userId),
                'requirement_review' => $this->requirementReview($userId),
                'notification_read', 'notification_read_all' => $this->notificationRead($action, $userId),
                default => throw new DomainException('The requested action is not available.'),
            };
            set_flash('success', $message);
        } catch (DomainException | InvalidArgumentException $exception) {
            set_flash('error', $exception->getMessage());
        } catch (Throwable) {
            error_log('Operational workflow action failed.');
            set_flash('error', 'The requested change could not be completed.');
        }

        $this->redirectByAction($action);
    }

    public function supervisorAttendance(int $userId): array
    {
        return [
            'records' => $this->model->linkedAttendanceRecords($userId),
            'placements' => $this->model->linkedPlacementsForSupervisor($userId),
        ];
    }

    private function attendanceSubmit(int $userId): string
    {
        $date = trim((string) ($_POST['attendance_date'] ?? ''));
        $timeIn = trim((string) ($_POST['time_in'] ?? '')) ?: null;
        $timeOut = trim((string) ($_POST['time_out'] ?? '')) ?: null;
        $this->model->submitAttendance($userId, $date, $timeIn, $timeOut);
        return 'Attendance submitted for supervisor review.';
    }

    private function attendanceReview(int $userId): string
    {
        $id = $this->postInteger('attendance_id');
        $status = (string) ($_POST['review_status'] ?? '');
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $this->model->reviewAttendance($id, $userId, $status, $remarks);
        return 'Attendance record reviewed.';
    }

    private function evaluationSubmit(int $userId): string
    {
        $placementId = $this->postInteger('placement_id');
        $rating = $this->postInteger('rating');
        $date = trim((string) ($_POST['evaluation_date'] ?? date('Y-m-d')));
        $comments = trim((string) ($_POST['comments'] ?? ''));
        $this->model->submitEvaluation($userId, $placementId, $rating, $comments, $date);
        return 'Evaluation submitted.';
    }

    private function requirementSubmit(int $userId): string
    {
        $typeId = $this->postInteger('requirement_type_id');
        $upload = $_FILES['requirement_file'] ?? null;

        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Choose a valid requirement document to upload.');
        }
        if ((int) ($upload['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('The document must be 5 MB or smaller.');
        }

        $temporaryPath = (string) ($upload['tmp_name'] ?? '');
        $originalName = (string) ($upload['name'] ?? 'document');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Only PDF, JPG, PNG, DOC, and DOCX documents are allowed.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $temporaryPath !== '' ? $finfo->file($temporaryPath) : false;
        if (!is_string($mime) || !in_array($mime, $allowedMimes, true)) {
            throw new InvalidArgumentException('The uploaded document type could not be verified.');
        }

        $uploadDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'requirements';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('The upload directory is unavailable.');
        }

        $randomName = bin2hex(random_bytes(16));
        $storedName = $randomName . '.' . $extension;
        $storedPath = $uploadDirectory . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($temporaryPath, $storedPath)) {
            throw new RuntimeException('The document could not be stored.');
        }

        $fileReference = 'storage/uploads/requirements/' . $storedName;

        try {
            $this->model->submitRequirement($userId, $typeId, $fileReference);
        } catch (Throwable $exception) {
            @unlink($storedPath);
            throw $exception;
        }

        return 'Requirement submitted.';
    }

    private function requirementReview(int $userId): string
    {
        $id = $this->postInteger('submission_id');
        $status = (string) ($_POST['review_status'] ?? '');
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $this->model->reviewRequirement($id, $userId, $status, $remarks);
        return 'Requirement reviewed.';
    }

    private function notificationRead(string $action, int $userId): string
    {
        if ($action === 'notification_read_all') {
            $this->model->markAllNotificationsRead($userId);
            return 'All notifications marked as read.';
        }
        $id = $this->postInteger('notification_id');
        $this->model->markNotificationRead($id, $userId);
        return 'Notification marked as read.';
    }

    private function postInteger(string $name): int
    {
        $value = $_POST[$name] ?? null;
        return filter_var($value, FILTER_VALIDATE_INT) ?: 0;
    }

    private function redirectByAction(string $action): never
    {
        $page = match ($action) {
            'attendance_submit', 'requirement_submit', 'notification_read', 'notification_read_all' => 'student/attendance',
            'attendance_review', 'evaluation_submit' => 'supervisor/workflow',
            'requirement_review' => 'coordinator/requirements',
            default => 'home',
        };
        if (in_array($action, ['notification_read', 'notification_read_all'], true)) $page = 'student/notifications';
        if ($action === 'requirement_submit') $page = 'student/requirements';
        if ($action === 'requirement_review') $page = 'coordinator/requirements';
        header('Location: ' . app_route_url($page, app_base_path()), true, 303);
        exit;
    }
}
