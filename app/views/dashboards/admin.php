<?php

declare(strict_types=1);

$statDefinitions = [
    ['label' => 'Registered users', 'key' => 'total_users', 'icon' => 'bi-people', 'tone' => 'blue'],
    ['label' => 'Students', 'key' => 'total_students', 'icon' => 'bi-mortarboard', 'tone' => 'green'],
    ['label' => 'Advisers', 'key' => 'total_advisers', 'icon' => 'bi-person-workspace', 'tone' => 'purple'],
    ['label' => 'Coordinators', 'key' => 'total_coordinators', 'icon' => 'bi-person-badge', 'tone' => 'orange'],
    ['label' => 'Company supervisors', 'key' => 'total_supervisors', 'icon' => 'bi-person-video3', 'tone' => 'blue'],
    ['label' => 'Companies', 'key' => 'total_companies', 'icon' => 'bi-buildings', 'tone' => 'green'],
    ['label' => 'Academic programs', 'key' => 'total_programs', 'icon' => 'bi-journal-bookmark', 'tone' => 'purple'],
    ['label' => 'Skills', 'key' => 'total_skills', 'icon' => 'bi-lightning', 'tone' => 'orange'],
    ['label' => 'Company positions', 'key' => 'total_positions', 'icon' => 'bi-briefcase', 'tone' => 'blue'],
    ['label' => 'Requirement types', 'key' => 'total_requirement_types', 'icon' => 'bi-card-checklist', 'tone' => 'green'],
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
                'tone' => $definition['tone'],
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
            <p>Manage accounts and system master data.</p>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($adminShortcuts as [$label, $route]): ?>
            <a class="btn btn-outline-primary" href="<?= app_escape(app_route_url($route, $appBase)) ?>"><?= app_escape($label) ?></a>
        <?php endforeach; ?>
    </div>
</section>
