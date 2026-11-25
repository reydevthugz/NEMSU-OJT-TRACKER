<?php

declare(strict_types=1);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="NEMSU OJT TRACKER role-protected workspace">
    <title><?= app_escape($pageTitle) ?> | NEMSU OJT TRACKER</title>
    <link rel="icon" href="<?= app_escape($assetBase) ?>/assets/images/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/app.css?v=1" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/components.css?v=1" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/responsive.css?v=1" rel="stylesheet">
</head>
<body class="app-body">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <div class="app-shell">
        <aside class="app-sidebar" id="app-sidebar" aria-label="Main navigation">
            <?php app_render_component('sidebar', [
                'role' => $role,
                'activePage' => $pageKey,
                'appBase' => $appBase,
            ]); ?>
        </aside>
        <button class="sidebar-backdrop" type="button" aria-label="Close navigation" tabindex="-1" data-sidebar-close></button>

        <div class="app-main">
            <?php app_render_component('topbar', [
                'role' => $role,
                'pageTitle' => $pageTitle,
                'appBase' => $appBase,
            ]); ?>
            <main class="content-area" id="main-content">
                <?php if (!str_starts_with($pageKey, 'admin/') && $pageKey !== 'dashboard/admin'): ?>
                    <div class="prototype-banner" role="note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span><strong>Phase 2 placeholder.</strong> This role-protected workspace is ready for later OJT modules; operational features are not active.</span>
                    </div>
                <?php endif; ?>
                <?php app_render_component('page-header', [
                    'title' => $pageTitle,
                    'subtitle' => $pageSubtitle,
                    'appBase' => $appBase,
                ]); ?>
                <?php require $viewPath; ?>
            </main>
            <?php app_render_component('footer'); ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_escape($assetBase) ?>/assets/js/sidebar.js?v=1"></script>
    <script src="<?= app_escape($assetBase) ?>/assets/js/app.js?v=1"></script>
</body>
</html>
