<?php

declare(strict_types=1);

?>
<?php if ($dashboardLoadError): ?>
    <div class="alert alert-danger" role="alert">Supervisor summary data could not be loaded. Please try again later.</div>
<?php else: ?>
    <section class="welcome-panel mb-4">
        <div>
            <p class="eyebrow mb-2">Company supervisor workspace</p>
            <h2 class="h4 mb-2">Company trainee overview</h2>
            <p class="mb-0 text-muted">Only placements at companies linked to your supervisor account are included.</p>
        </div>
        <span class="status-badge status-neutral"><i class="bi bi-building-check me-1" aria-hidden="true"></i>Company-scoped</span>
    </section>
    <section class="row g-3 dashboard-stat-grid mb-4" aria-label="Supervisor overview statistics">
        <?php foreach (($dashboardData['stats'] ?? []) as $stat): ?>
            <div class="col-12 col-sm-6 col-xl-4"><?php app_render_component('stat-card', ['stat' => $stat]); ?></div>
        <?php endforeach; ?>
    </section>
    <section class="panel-card" aria-labelledby="trainees-heading">
        <div class="panel-heading">
            <div>
                <h2 class="h5 mb-1" id="trainees-heading">Trainees at your linked companies</h2>
                <p class="text-muted mb-0">Read-only placement records; attendance and evaluation actions are not enabled.</p>
            </div>
        </div>
        <?php if (($dashboardData['trainees'] ?? []) !== []): ?>
            <?php app_render_component('data-table', [
                'caption' => 'Trainee placement records at companies linked to this supervisor',
                'columns' => [
                    ['key' => 'student_name', 'label' => 'Student'],
                    ['key' => 'student_number', 'label' => 'Student number'],
                    ['key' => 'company_name', 'label' => 'Company'],
                    ['key' => 'position_title', 'label' => 'Position'],
                    ['key' => 'status', 'label' => 'Placement status', 'type' => 'status'],
                ],
                'rows' => $dashboardData['trainees'],
            ]); ?>
        <?php else: ?>
            <?php app_render_component('empty-state', [
                'icon' => 'bi-mortarboard',
                'title' => 'No linked trainee placements',
                'message' => 'No placement records are available at companies linked to your supervisor account.',
            ]); ?>
        <?php endif; ?>
    </section>
    <section class="panel-card mt-4" aria-labelledby="supervisor-notifications-heading">
        <div class="panel-heading">
            <div>
                <h2 class="h5 mb-1" id="supervisor-notifications-heading">Recent notifications</h2>
                <p class="text-muted mb-0">Latest notices addressed to your account.</p>
            </div>
        </div>
        <?php app_render_component('notification-list', [
            'notifications' => $dashboardData['recent_notifications'] ?? [],
        ]); ?>
    </section>
<?php endif; ?>
