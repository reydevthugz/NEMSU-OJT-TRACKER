<?php

declare(strict_types=1);

$isRegistration = $pageKey === 'register';
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/head.php'; ?>
</head>
<body class="auth-body">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <main class="auth-shell" id="main-content">
        <section class="auth-brand-panel" aria-label="Application introduction">
            <a class="brand-lockup" href="<?= app_escape(app_route_url('home', $appBase)) ?>">
                <span class="brand-mark" aria-hidden="true">N</span>
                <span class="brand-name">NEMSU <strong>OJT TRACKER</strong></span>
            </a>
            <div>
                <?php if ($isRegistration): ?>
                    <h1>Create your student account.</h1>
                    <p class="auth-brand-copy">Add your academic details and sign-in information to access your OJT records.</p>
                    <ol class="registration-steps" aria-label="Registration progress">
                        <li data-registration-progress>Student information</li>
                        <li data-registration-progress>Account information</li>
                    </ol>
                <?php else: ?>
                    <h1>Welcome to your OJT workspace.</h1>
                    <p class="auth-brand-copy">Access your records and follow your training journey from campus to workplace.</p>
                    <div class="auth-illustration" aria-hidden="true">
                        <i class="bi bi-stars"></i>
                        <i class="bi bi-person-workspace"></i>
                    </div>
                <?php endif; ?>
            </div>
            <p class="auth-brand-footer">North Eastern Mindanao State University</p>
        </section>
        <section class="auth-content-panel">
            <div class="auth-content-inner">
                <p class="auth-back-link"><a href="<?= app_escape(app_route_url('home', $appBase)) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home</a></p>
                <h2 class="auth-heading"><?= app_escape($pageTitle) ?></h2>
                <p class="auth-description"><?= app_escape($pageSubtitle) ?></p>
                <?php require $viewPath; ?>
            </div>
        </section>
    </main>
    <script src="<?= app_escape($assetBase) ?>/assets/js/app.js?v=3"></script>
</body>
</html>
