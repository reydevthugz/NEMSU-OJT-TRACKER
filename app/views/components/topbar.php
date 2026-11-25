<?php

declare(strict_types=1);

$currentUser = authenticated_user() ?? [];
$dashboard = dashboard_page_for_role((string) ($currentUser['role'] ?? ''));
?>
<header class="topbar">
    <div class="topbar-start">
        <button class="sidebar-toggle" type="button" aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false" data-sidebar-toggle>
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <nav class="topbar-context" aria-label="Breadcrumb">
            <?php if ($dashboard !== $pageKey): ?>
                <a class="topbar-context-label" href="<?= app_escape(app_route_url($dashboard, $appBase)) ?>">Dashboard</a>
            <?php else: ?>
                <span class="topbar-context-label"><?= app_escape($role) ?> workspace</span>
            <?php endif; ?>
            <span class="topbar-context-title" aria-current="page"><?= app_escape($pageTitle) ?></span>
        </nav>
    </div>
    <div class="topbar-actions">
        <?php if ($role === 'Student'): ?>
            <a class="topbar-notifications" aria-label="Notifications" href="<?= app_escape(app_route_url('student/notifications', $appBase)) ?>"><i class="bi bi-bell" aria-hidden="true"></i></a>
        <?php endif; ?>
        <div class="dropdown">
            <button class="profile-button dropdown-toggle" aria-label="Account menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar avatar-small" aria-hidden="true"><?= app_escape(substr((string) ($currentUser['name'] ?? $role), 0, 1)) ?></span>
                <span class="profile-text"><strong><?= app_escape($currentUser['name'] ?? $role) ?></strong><small><?= app_escape($role) ?></small></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end profile-menu">
                <span class="dropdown-item-text"><strong><?= app_escape($currentUser['name'] ?? $role) ?></strong><br><small><?= app_escape($currentUser['email'] ?? '') ?></small></span>
                <hr class="dropdown-divider">
                <form method="post" action="<?= app_escape(app_route_url('home', $appBase)) ?>" class="px-2">
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout</button>
                </form>
            </div>
        </div>
    </div>
</header>
