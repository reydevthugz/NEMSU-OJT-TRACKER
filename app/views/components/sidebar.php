<?php

declare(strict_types=1);

$navigation = [
    'Student' => [
        ['Dashboard', 'dashboard/student', 'bi-grid-1x2'],
        ['My profile', 'student/profile', 'bi-person-vcard'],
        ['My skills', 'student/skills', 'bi-lightning'],
        ['Company preferences', 'student/preferences', 'bi-heart'],
        ['My placement', 'student/placement', 'bi-briefcase'],
        ['OJT requirements', 'student/requirements', 'bi-files'],
        ['Attendance', 'student/attendance', 'bi-calendar-check'],
        ['OJT progress', 'student/progress', 'bi-graph-up'],
        ['Notifications', 'student/notifications', 'bi-bell'],
    ],
    'Adviser' => [['Dashboard', 'dashboard/adviser', 'bi-grid-1x2']],
    'Coordinator' => [['Dashboard', 'dashboard/coordinator', 'bi-grid-1x2']],
    'Company Supervisor' => [['Dashboard', 'dashboard/supervisor', 'bi-grid-1x2']],
    'System Administrator' => [
        ['Dashboard', 'dashboard/admin', 'bi-grid-1x2'],
        ['User accounts', 'admin/users', 'bi-people'],
        ['Academic programs', 'admin/programs', 'bi-journal-bookmark'],
        ['Companies', 'admin/companies', 'bi-buildings'],
        ['Company supervisors', 'admin/supervisors', 'bi-person-video3'],
        ['Company positions', 'admin/positions', 'bi-briefcase'],
        ['Skills catalog', 'admin/skills', 'bi-lightning'],
        ['Requirement types', 'admin/requirements', 'bi-card-checklist'],
    ],
];
$items = $navigation[$role] ?? [];
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
        <p class="sidebar-section-label">Overview</p>
        <ul class="sidebar-nav-list">
            <?php foreach ($items as $index => [$label, $route, $icon]): ?>
                <?php if ($index === 1): ?>
                    </ul>
                    <p class="sidebar-section-label"><?= $role === 'System Administrator' ? 'Management' : 'OJT records' ?></p>
                    <ul class="sidebar-nav-list">
                <?php endif; ?>
                    <?php
                    $isActive = $activePage === $route
                        || ($route === 'admin/users' && $activePage === 'admin/user');
                    ?>
                <li>
                        <a class="sidebar-link<?= $isActive ? ' is-active' : '' ?>" href="<?= app_escape(app_route_url($route, $appBase)) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                            <i class="bi <?= app_escape($icon) ?>" aria-hidden="true"></i>
                            <span><?= app_escape($label) ?></span>
                        </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="sidebar-bottom">
        <div>North Eastern Mindanao State University</div>
        <a class="sidebar-home-link" href="<?= app_escape(app_route_url('home', $appBase)) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> System home</a>
    </div>
</div>
