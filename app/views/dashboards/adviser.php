<?php

declare(strict_types=1);

?>
<?php if ($dashboardLoadError): ?>
    <div class="alert alert-danger" role="alert">The adviser workspace could not be loaded. Please try again later.</div>
<?php else: ?>
    <section class="welcome-panel mb-4">
        <div>
            <p class="eyebrow mb-2">Adviser workspace</p>
            <h2 class="h4 mb-2">Student monitoring</h2>
            <p class="mb-0 text-muted">Adviser access is active for this account.</p>
        </div>
        <span class="status-badge status-neutral"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Protected workspace</span>
    </section>
    <?php $assignedStudents = $dashboardData['assigned_students'] ?? []; ?>
    <?php if ($assignedStudents !== []): ?>
        <section class="panel-card mb-4" aria-labelledby="adviser-students-heading">
            <div class="panel-heading">
                <div>
                    <h2 class="h5 mb-1" id="adviser-students-heading">Assigned students</h2>
                    <p class="text-muted mb-0"><?= (int) ($dashboardData['assigned_student_count'] ?? count($assignedStudents)) ?> active assignment(s).</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead><tr><th>Student</th><th>Number</th><th>Program</th><th>Placement</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($assignedStudents as $student): ?>
                        <tr>
                            <td><strong><?= app_escape($student['name']) ?></strong><div class="small text-muted"><?= app_escape($student['email']) ?></div></td>
                            <td><?= app_escape($student['student_number']) ?></td>
                            <td><?= app_escape($student['program_code']) ?></td>
                            <td><?= app_escape(($student['company_name'] ?: '—') . ' / ' . ($student['position_title'] ?: '—')) ?></td>
                            <td><?php if ($student['placement_status']): app_render_component('status-badge', ['status' => $student['placement_status']]); else: ?><span class="text-muted">No placement</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <?php app_render_component('empty-state', [
            'icon' => 'bi-person-workspace',
            'title' => 'No adviser assignments are available',
            'message' => 'An administrator must assign active students to this adviser account.',
        ]); ?>
    <?php endif; ?>
    <section class="panel-card mt-4" aria-labelledby="adviser-notifications-heading">
        <div class="panel-heading">
            <div>
                <h2 class="h5 mb-1" id="adviser-notifications-heading">Recent notifications</h2>
                <p class="text-muted mb-0">Latest notices addressed to your account.</p>
            </div>
        </div>
        <?php app_render_component('notification-list', [
            'notifications' => $dashboardData['recent_notifications'] ?? [],
        ]); ?>
    </section>
<?php endif; ?>
