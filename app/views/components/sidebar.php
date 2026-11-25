<?php

declare(strict_types=1);

$navigation = [
    'Student' => [
        'Dashboard', 'My Profile', 'My Skills', 'OJT Companies', 'Company Preferences',
        'My Placement', 'Requirements', 'Attendance', 'OJT Progress', 'Evaluations',
    ],
    'Adviser' => [
        'Dashboard', 'My Students', 'Student Profiles', 'Placement Matching',
        'Placement Recommendations', 'OJT Monitoring',
    ],
    'Coordinator' => [
        'Dashboard', 'Students', 'Advisers', 'Partner Companies', 'Company Positions',
        'Placement Approvals', 'Requirements', 'Reports',
    ],
    'Company Supervisor' => [
        'Dashboard', 'My Trainees', 'Attendance Verification', 'Progress Monitoring', 'Evaluations',
    ],
    'System Administrator' => [
        'Dashboard', 'User Management', 'Role Management', 'System Settings', 'Activity Overview',
    ],
];
$items = $navigation[$role] ?? $navigation['Student'];
$dashboardRoute = match ($role) {
    'Adviser' => 'dashboard/adviser',
    'Coordinator' => 'dashboard/coordinator',
    'Company Supervisor' => 'dashboard/supervisor',
    'System Administrator' => 'dashboard/admin',
    default => 'dashboard/student',
};
?>
<div class="sidebar-inner">
    <a class="sidebar-brand" href="<?= app_escape(app_route_url('home', $appBase)) ?>">
        <span class="brand-mark" aria-hidden="true">N</span>
        <span class="brand-name">NEMSU <strong>OJT TRACKER</strong></span>
    </a>
    <div class="sidebar-role">
        <span class="sidebar-role-label">Workspace</span>
        <span class="sidebar-role-name"><?= app_escape($role) ?></span>
    </div>
    <nav class="sidebar-navigation" aria-label="<?= app_escape($role) ?> navigation">
        <p class="sidebar-section-label">MENU</p>
        <ul class="sidebar-nav-list">
            <?php foreach ($items as $index => $label): ?>
                <?php $isDashboard = $index === 0; ?>
                <li>
                    <?php if ($isDashboard): ?>
                        <a class="sidebar-link is-active" href="<?= app_escape(app_route_url($dashboardRoute, $appBase)) ?>" aria-current="page">
                            <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                            <span><?= app_escape($label) ?></span>
                        </a>
                    <?php else: ?>
                        <span class="sidebar-link is-placeholder" aria-disabled="true" title="Available in a later phase">
                            <i class="bi bi-circle" aria-hidden="true"></i>
                            <span><?= app_escape($label) ?></span>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-bottom">
        <div class="prototype-sidebar-note"><i class="bi bi-layout-text-window-reverse" aria-hidden="true"></i><span>Phase 2 foundation<br><small>Modules are not active</small></span></div>
        <a class="sidebar-home-link" href="<?= app_escape(app_route_url('home', $appBase)) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> System home</a>
    </div>
</div>
