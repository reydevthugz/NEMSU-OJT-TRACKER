<?php

declare(strict_types=1);
?>
<?php if ($studentPageLoadError): ?>
    <div class="alert alert-danger" role="alert">This page's records could not be loaded. Please try again later.</div>
<?php elseif (($studentPageData['kind'] ?? '') === 'profile'): ?>
    <?php if (($studentPageData['profile'] ?? null) !== null): ?>
        <?php $profileUser = authenticated_user() ?? []; ?>
        <section class="panel-card profile-banner" aria-label="Student account">
            <span class="avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
            <div>
                <h2 class="h5 mb-1"><?= app_escape($profileUser['name'] ?? 'Student') ?></h2>
                <p class="text-muted mb-0"><?= app_escape($profileUser['email'] ?? '') ?></p>
            </div>
        </section>
        <section class="panel-card" aria-labelledby="student-profile-heading">
            <div class="panel-heading">
                <div>
                    <h2 class="h5 mb-1" id="student-profile-heading">Registered academic information</h2>
                    <p class="text-muted mb-0">These details are associated with your signed-in account.</p>
                </div>
                <i class="bi bi-person-vcard panel-heading-icon" aria-hidden="true"></i>
            </div>
            <?php $profile = $studentPageData['profile']; ?>
            <dl class="profile-summary-grid mb-0">
                <div><dt>Student number</dt><dd><?= app_escape($profile['student_number']) ?></dd></div>
                <div><dt>Academic program</dt><dd><?= app_escape($profile['program_code'] . ' - ' . $profile['program_name']) ?></dd></div>
                <div><dt>Year level</dt><dd><?= app_escape($profile['year_level']) ?></dd></div>
                <div><dt>Preferred location</dt><dd><?= app_escape($profile['preferred_location'] ?: 'Not provided') ?></dd></div>
            </dl>
            <p class="text-muted small mt-3 mb-0">Profile editing is not enabled in this build.</p>
        </section>
    <?php else: ?>
        <?php app_render_component('empty-state', [
            'icon' => 'bi-person-vcard',
            'title' => 'No student profile is linked',
            'message' => 'Contact the OJT coordinator if you believe this account should have a student profile.',
        ]); ?>
    <?php endif; ?>
<?php elseif (($studentPageData['rows'] ?? []) !== []): ?>
    <section class="panel-card">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1"><?= app_escape($pageTitle) ?></h2>
                <p class="text-muted small mb-0">Read-only information from records linked to your account.</p>
            </div>
            <span class="status-badge status-neutral"><?= count($studentPageData['rows']) ?> records</span>
        </div>
        <?php app_render_component('data-table', [
            'caption' => $pageTitle . ' for the signed-in student',
            'columns' => $studentPageData['columns'],
            'rows' => $studentPageData['rows'],
        ]); ?>
    </section>
<?php else: ?>
    <?php app_render_component('empty-state', [
        'icon' => 'bi-inbox',
        'title' => $studentPageData['empty_title'] ?? 'No records available',
        'message' => $studentPageData['empty_message'] ?? 'There are no records linked to your account.',
    ]); ?>
<?php endif; ?>
