<?php

declare(strict_types=1);

final class ReportModel
{
    public function __construct(private PDO $db)
    {
    }

    public function summary(): array
    {
        $summary = [];
        $summary['placements'] = $this->counts(
            'SELECT status, COUNT(*) AS total FROM placements GROUP BY status', 'status', 'total'
        );
        $summary['requirements'] = $this->counts(
            'SELECT status, COUNT(*) AS total FROM requirement_submissions GROUP BY status', 'status', 'total'
        );
        $summary['attendance'] = $this->counts(
            'SELECT status, COUNT(*) AS total FROM attendance_records GROUP BY status', 'status', 'total'
        );
        $summary['verified_hours'] = (float) $this->db->query(
            "SELECT COALESCE(SUM(total_hours), 0) FROM attendance_records WHERE status = 'verified'"
        )->fetchColumn();
        $summary['evaluation_average'] = $this->db->query(
            'SELECT COALESCE(AVG(rating), 0) FROM evaluations'
        )->fetchColumn();
        return $summary;
    }

    public function placements(): array
    {
        return $this->db->query(
            'SELECT p.id, u.name AS student_name, sp.student_number,
                    c.name AS company_name, cp.title AS position_title,
                    p.status, p.required_hours, p.start_date, p.end_date,
                    COALESCE(SUM(CASE WHEN ar.status = \'verified\' THEN ar.total_hours ELSE 0 END), 0) AS verified_hours
             FROM placements p
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             LEFT JOIN attendance_records ar ON ar.placement_id = p.id
             GROUP BY p.id, u.name, sp.student_number, c.name, cp.title,
                      p.status, p.required_hours, p.start_date, p.end_date
             ORDER BY p.created_at DESC, p.id DESC'
        )->fetchAll();
    }

    public function attendance(): array
    {
        return $this->db->query(
            'SELECT ar.attendance_date, u.name AS student_name, sp.student_number,
                    c.name AS company_name, cp.title AS position_title,
                    ar.time_in, ar.time_out, ar.total_hours, ar.status,
                    verifier.name AS verified_by_name, ar.verified_at
             FROM attendance_records ar
             INNER JOIN placements p ON p.id = ar.placement_id
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             LEFT JOIN users verifier ON verifier.id = ar.verified_by
             ORDER BY ar.attendance_date DESC, ar.id DESC'
        )->fetchAll();
    }

    public function evaluations(): array
    {
        return $this->db->query(
            'SELECT u.name AS student_name, sp.student_number,
                    c.name AS company_name, cp.title AS position_title,
                    supervisor.name AS supervisor_name, e.rating, e.comments, e.evaluation_date
             FROM evaluations e
             INNER JOIN placements p ON p.id = e.placement_id
             INNER JOIN student_profiles sp ON sp.id = p.student_profile_id
             INNER JOIN users u ON u.id = sp.user_id
             INNER JOIN company_positions cp ON cp.id = p.position_id
             INNER JOIN companies c ON c.id = cp.company_id
             INNER JOIN users supervisor ON supervisor.id = e.supervisor_id
             ORDER BY e.evaluation_date DESC, e.id DESC'
        )->fetchAll();
    }

    private function counts(string $sql, string $labelKey, string $valueKey): array
    {
        $rows = $this->db->query($sql)->fetchAll();
        return array_map(static fn(array $row): array => [
            'label' => (string) $row[$labelKey],
            'value' => (int) $row[$valueKey],
        ], $rows);
    }
}
