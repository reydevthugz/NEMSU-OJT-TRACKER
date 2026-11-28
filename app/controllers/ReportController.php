<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/ReportModel.php';
require_once dirname(__DIR__) . '/helpers/auth.php';

final class ReportController
{
    public function __construct(private ReportModel $model)
    {
    }

    public function pageData(): array
    {
        return [
            'summary' => $this->model->summary(),
            'placements' => $this->model->placements(),
            'attendance' => $this->model->attendance(),
            'evaluations' => $this->model->evaluations(),
        ];
    }

    public function exportCsv(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_post()) {
            http_response_code(403);
            exit;
        }

        $report = (string) ($_POST['report'] ?? 'placements');
        $rows = match ($report) {
            'attendance' => $this->model->attendance(),
            'evaluations' => $this->model->evaluations(),
            default => $this->model->placements(),
        };

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="nemsu-' . $report . '-report.csv"');
        $output = fopen('php://output', 'wb');
        if ($rows === []) {
            fputs($output, "No records\n");
            fclose($output);
            exit;
        }
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $row) fputcsv($output, array_values($row));
        fclose($output);
        exit;
    }
}
