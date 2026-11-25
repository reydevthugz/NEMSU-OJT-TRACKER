<?php

declare(strict_types=1);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Secure NEMSU OJT TRACKER account access">
    <title><?= app_escape($pageTitle) ?> | NEMSU OJT TRACKER</title>
    <link rel="icon" href="<?= app_escape($assetBase) ?>/assets/images/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/app.css?v=1" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/components.css?v=1" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/responsive.css?v=1" rel="stylesheet">
</head>
<body class="auth-body">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <main class="auth-shell" id="main-content">
        <section class="auth-brand-panel" aria-label="Application introduction">
            <a class="brand-lockup" href="<?= app_escape(app_route_url('home', $appBase)) ?>">
                <span class="brand-mark" aria-hidden="true">N</span>
                <span class="brand-name">NEMSU <strong>OJT TRACKER</strong></span>
            </a>
            <p class="auth-brand-kicker">On-the-Job Training</p>
            <h1>Building pathways from campus to career.</h1>
            <p class="auth-brand-copy">A proposed placement and monitoring experience for the NEMSU OJT community.</p>
            <div class="prototype-chip"><i class="bi bi-shield-check" aria-hidden="true"></i> Secure account access</div>
        </section>
        <section class="auth-content-panel">
            <div class="auth-content-inner">
                <div class="auth-mobile-brand">
                    <span class="brand-mark" aria-hidden="true">N</span>
                    <span class="brand-name">NEMSU <strong>OJT TRACKER</strong></span>
                </div>
                <p class="eyebrow"><?= app_escape($pageSubtitle) ?></p>
                <h2 class="auth-heading"><?= app_escape($pageTitle) ?></h2>
                <?php require $viewPath; ?>
                <p class="auth-back-link"><a href="<?= app_escape(app_route_url('home', $appBase)) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to system home</a></p>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_escape($assetBase) ?>/assets/js/app.js?v=1"></script>
</body>
</html>
