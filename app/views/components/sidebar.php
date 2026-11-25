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
$adminLinks = [
    'Dashboard' => 'dashboard/admin',
    'User accounts' => 'admin/users',
    'Academic programs' => 'admin/programs',
    'Companies' => 'admin/companies',
    'Company supervisors' => 'admin/supervisors',
    'Company positions' => 'admin/positions',
    'Skills catalog' => 'admin/skills',
    'Requirement types' => 'admin/requirements',
];
$isAdmin = $role === 'System Administrator';
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
            <?php if ($isAdmin): ?>
                <?php foreach ($adminLinks as $label => $route): ?>
                    <?php $active = $activePage === $route || ($route === 'admin/users' && $activePage === 'admin/user'); ?>
                    <li>
                        <a class="sidebar-link<?= $active ? ' is-active' : '' ?>" href="<?= app_escape(app_route_url($route, $appBase)) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                            <i class="bi <?= $route === 'dashboard/admin' ? 'bi-grid-1x2' : 'bi-circle' ?>" aria-hidden="true"></i>
                            <span><?= app_escape($label) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($items as $index => $label): ?>
                    <li>
                        <?php if ($index === 0): ?>
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
            <?php endif; ?>
        </ul>
    </nav>
    <div class="sidebar-bottom">
        <div class="prototype-sidebar-note"><i class="bi bi-layout-text-window-reverse" aria-hidden="true"></i><span><?= $isAdmin ? 'Phase 3 administration' : 'Phase 2 foundation' ?><br><small><?= $isAdmin ? 'Master data management' : 'Modules are not active' ?></small></span></div>
        <a class="sidebar-home-link" href="<?= app_escape(app_route_url('home', $appBase)) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> System home</a>
    </div>
</div>
