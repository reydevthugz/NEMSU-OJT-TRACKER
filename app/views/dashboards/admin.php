<?php

declare(strict_types=1);

$statDefinitions = [
    ['label' => 'Registered users', 'key' => 'total_users', 'icon' => 'bi-people'],
    ['label' => 'Students', 'key' => 'total_students', 'icon' => 'bi-mortarboard'],
    ['label' => 'Advisers', 'key' => 'total_advisers', 'icon' => 'bi-person-workspace'],
    ['label' => 'Coordinators', 'key' => 'total_coordinators', 'icon' => 'bi-person-badge'],
    ['label' => 'Company supervisors', 'key' => 'total_supervisors', 'icon' => 'bi-person-video3'],
    ['label' => 'Companies', 'key' => 'total_companies', 'icon' => 'bi-buildings'],
    ['label' => 'Academic programs', 'key' => 'total_programs', 'icon' => 'bi-journal-bookmark'],
    ['label' => 'Skills', 'key' => 'total_skills', 'icon' => 'bi-lightning'],
    ['label' => 'Company positions', 'key' => 'total_positions', 'icon' => 'bi-briefcase'],
    ['label' => 'Requirement types', 'key' => 'total_requirement_types', 'icon' => 'bi-card-checklist'],
];
$adminShortcuts = [
    ['User accounts', 'admin/users'],
    ['Academic programs', 'admin/programs'],
    ['Companies', 'admin/companies'],
    ['Company supervisors', 'admin/supervisors'],
    ['Company positions', 'admin/positions'],
    ['Skills catalog', 'admin/skills'],
    ['Requirement types', 'admin/requirements'],
];
require __DIR__ . '/../admin/_feedback.php';
?>
<section class="panel-card mb-4">
    <div class="panel-heading">
        <div>
            <h2>System overview</h2>
            <p>Current totals from registered database records. No sample data is included.</p>
        </div>
    </div>
    <div class="row g-3">
        <?php foreach ($statDefinitions as $definition): ?>
            <?php
            $stat = [
                'label' => $definition['label'],
                'value' => $adminData['stats'][$definition['key']] ?? 0,
                'caption' => 'Current records',
                'icon' => $definition['icon'],
            ];
            ?>
            <div class="col-12 col-sm-6 col-xl-4">
                <?php app_render_component('stat-card', compact('stat')); ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<section class="panel-card">
    <div class="panel-heading">
        <div>
            <h2>Administration</h2>
            <p>Manage accounts and system reference data.</p>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($adminShortcuts as [$label, $route]): ?>
            <a class="btn btn-outline-primary" href="<?= app_escape(app_route_url($route, $appBase)) ?>"><?= app_escape($label) ?></a>
        <?php endforeach; ?>
    </div>
</section>
