<?php

declare(strict_types=1);

$currentUser = authenticated_user() ?? [];
?>
<header class="topbar">
    <div class="topbar-start">
        <button class="sidebar-toggle" type="button" aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false" data-sidebar-toggle>
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div class="topbar-context">
            <span class="topbar-context-label">NEMSU OJT TRACKER</span>
            <span class="topbar-context-title"><?= app_escape($pageTitle) ?></span>
        </div>
    </div>
    <div class="topbar-actions">
        <button class="icon-button notification-button" type="button" aria-label="Notifications placeholder; no notifications are connected" title="Notifications are a visual placeholder">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <span class="notification-dot" aria-hidden="true"></span>
        </button>
        <div class="dropdown">
            <button class="profile-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar avatar-small" aria-hidden="true"><?= app_escape(substr((string) ($currentUser['name'] ?? $role), 0, 1)) ?></span>
                <span class="profile-text"><strong><?= app_escape($currentUser['name'] ?? $role) ?></strong><small><?= app_escape($currentUser['email'] ?? '') ?></small></span>
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
