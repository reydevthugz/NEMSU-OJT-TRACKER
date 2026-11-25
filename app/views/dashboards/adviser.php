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
    <?php app_render_component('empty-state', [
        'icon' => 'bi-person-workspace',
        'title' => 'No adviser assignments are available',
        'message' => 'The current database schema does not define an adviser-to-student assignment relationship, so no student records are shown.',
    ]); ?>
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
