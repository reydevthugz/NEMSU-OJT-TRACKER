<?php

declare(strict_types=1);

$currentUser = authenticated_user() ?? [];
$profile = $dashboardData['profile'] ?? null;
$placement = $dashboardData['placement'] ?? null;
?>
<?php if ($dashboardLoadError): ?>
    <div class="alert alert-danger" role="alert">Your dashboard summary could not be loaded. Please try again later.</div>
<?php else: ?>
    <section class="welcome-panel mb-4">
        <div>
            <p class="eyebrow mb-2">Student workspace</p>
            <h2 class="h4 mb-2">Welcome, <?= app_escape($currentUser['name'] ?? 'Student') ?></h2>
            <p class="mb-0 text-muted">A personal overview based only on records linked to your account.</p>
        </div>
        <span class="status-badge status-neutral"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Personal account</span>
    </section>

    <?php if ($profile !== null): ?>
        <section class="panel-card mb-4" aria-labelledby="student-profile-heading">
            <div class="panel-heading">
                <div>
                    <h2 class="h5 mb-1" id="student-profile-heading">Profile summary</h2>
                    <p class="text-muted mb-0">Your registered academic information.</p>
                </div>
                <i class="bi bi-person-vcard panel-heading-icon" aria-hidden="true"></i>
            </div>
            <dl class="profile-summary-grid mb-0">
                <div><dt>Student number</dt><dd><?= app_escape($profile['student_number']) ?></dd></div>
                <div><dt>Academic program</dt><dd><?= app_escape($profile['program_code'] . ' - ' . $profile['program_name']) ?></dd></div>
                <div><dt>Year level</dt><dd><?= app_escape($profile['year_level']) ?></dd></div>
                <div><dt>Preferred location</dt><dd><?= app_escape($profile['preferred_location'] ?: 'Not provided') ?></dd></div>
            </dl>
        </section>
    <?php else: ?>
        <div class="alert alert-warning" role="status">No student profile is linked to this account. Contact the OJT coordinator for assistance.</div>
    <?php endif; ?>

    <section class="row g-3 dashboard-stat-grid mb-4" aria-label="Your OJT summary">
        <?php foreach (($dashboardData['stats'] ?? []) as $stat): ?>
            <div class="col-12 col-sm-6 col-xl-4"><?php app_render_component('stat-card', ['stat' => $stat]); ?></div>
        <?php endforeach; ?>
    </section>

    <section class="panel-card" aria-labelledby="placement-heading">
        <div class="panel-heading">
            <div>
                <h2 class="h5 mb-1" id="placement-heading">Latest placement</h2>
                <p class="text-muted mb-0">Placement information linked to your profile.</p>
            </div>
        </div>
        <?php if ($placement !== null): ?>
            <dl class="profile-summary-grid mt-3 mb-0">
                <div><dt>Company</dt><dd><?= app_escape($placement['company_name']) ?></dd></div>
                <div><dt>Position</dt><dd><?= app_escape($placement['position_title']) ?></dd></div>
                <div><dt>Status</dt><dd><?php app_render_component('status-badge', ['status' => $placement['status']]); ?></dd></div>
                <div><dt>Required hours</dt><dd><?= app_escape($placement['required_hours']) ?></dd></div>
            </dl>
        <?php else: ?>
            <?php app_render_component('empty-state', [
                'icon' => 'bi-briefcase',
                'title' => 'No placement recorded',
                'message' => 'A placement will appear here after one is recorded for your profile.',
            ]); ?>
        <?php endif; ?>
    </section>

    <section class="panel-card mt-4" aria-labelledby="recent-notifications-heading">
        <div class="panel-heading">
            <div>
                <h2 class="h5 mb-1" id="recent-notifications-heading">Recent notifications</h2>
                <p class="text-muted mb-0">Latest notices addressed to your account.</p>
            </div>
        </div>
        <?php app_render_component('notification-list', [
            'notifications' => $dashboardData['recent_notifications'] ?? [],
            'emptyMessage' => 'Notifications addressed to your account will appear here.',
        ]); ?>
    </section>
<?php endif; ?>
