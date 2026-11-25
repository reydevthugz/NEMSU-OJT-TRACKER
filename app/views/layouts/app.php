<?php

declare(strict_types=1);

?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/head.php'; ?>
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
                'pageKey' => $pageKey,
                'appBase' => $appBase,
            ]); ?>
            <main class="content-area" id="main-content">
                <?php app_render_component('page-header', [
                    'title' => $pageTitle,
                    'subtitle' => $pageSubtitle,
                ]); ?>
                <?php require $viewPath; ?>
            </main>
            <?php app_render_component('footer', ['variant' => 'app']); ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_escape($assetBase) ?>/assets/js/sidebar.js?v=2"></script>
    <script src="<?= app_escape($assetBase) ?>/assets/js/app.js?v=3"></script>
</body>
</html>
