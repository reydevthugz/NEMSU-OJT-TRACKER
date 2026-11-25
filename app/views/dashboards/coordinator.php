<?php

declare(strict_types=1);

?>
<?php if ($dashboardLoadError): ?>
    <div class="alert alert-danger" role="alert">Coordinator summary data could not be loaded. Please try again later.</div>
<?php else: ?>
    <section class="welcome-panel mb-4">
        <div>
            <p class="eyebrow mb-2">Coordinator workspace</p>
            <h2 class="h4 mb-2">OJT overview</h2>
            <p class="mb-0 text-muted">Read-only totals from current student, company, position, placement, and requirement records.</p>
        </div>
        <span class="status-badge status-neutral"><i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>Current records</span>
    </section>
    <section class="row g-3 dashboard-stat-grid mb-4" aria-label="Coordinator overview statistics">
        <?php foreach (($dashboardData['stats'] ?? []) as $stat): ?>
            <div class="col-12 col-sm-6 col-xl-4"><?php app_render_component('stat-card', ['stat' => $stat]); ?></div>
        <?php endforeach; ?>
    </section>
    <section class="panel-card" aria-labelledby="placement-status-heading">
        <div class="panel-heading">
            <div>
                <h2 class="h5 mb-1" id="placement-status-heading">Placement status overview</h2>
                <p class="text-muted mb-0">Counts grouped by status in the placement records.</p>
            </div>
        </div>
        <?php if (($dashboardData['placement_statuses'] ?? []) !== []): ?>
            <?php app_render_component('data-table', [
                'caption' => 'Placement totals grouped by status',
                'columns' => [
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ['key' => 'total', 'label' => 'Records'],
                ],
                'rows' => $dashboardData['placement_statuses'],
            ]); ?>
        <?php else: ?>
            <?php app_render_component('empty-state', [
                'icon' => 'bi-diagram-3',
                'title' => 'No placement records',
                'message' => 'Placement status totals will appear after placement records exist.',
            ]); ?>
        <?php endif; ?>
    </section>
    <section class="panel-card mt-4" aria-labelledby="coordinator-notifications-heading">
        <div class="panel-heading">
            <div>
                <h2 class="h5 mb-1" id="coordinator-notifications-heading">Recent notifications</h2>
                <p class="text-muted mb-0">Latest notices addressed to your account.</p>
            </div>
        </div>
        <?php app_render_component('notification-list', [
            'notifications' => $dashboardData['recent_notifications'] ?? [],
        ]); ?>
    </section>
<?php endif; ?>
