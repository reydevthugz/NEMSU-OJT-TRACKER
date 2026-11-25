<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEMSU OJT TRACKER</title>
    <link rel="icon" href="<?= app_escape($assetBase) ?>/assets/images/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/app.css" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/components.css" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/responsive.css" rel="stylesheet">
</head>
<body class="home-body">
    <header class="home-topbar">
        <a class="brand-lockup" href="<?= app_escape(app_route_url('home', $appBase)) ?>">
            <span class="brand-mark">N</span><span class="brand-name">NEMSU <strong>OJT TRACKER</strong></span>
        </a>
        <nav class="home-actions" aria-label="Account links">
            <a class="btn btn-link" href="<?= app_escape(app_route_url('login', $appBase)) ?>">Sign in</a>
            <a class="btn btn-primary" href="<?= app_escape(app_route_url('register', $appBase)) ?>">Student registration</a>
        </nav>
    </header>
    <main class="home-main container">
        <section class="home-hero">
            <p class="hero-kicker"><i class="bi bi-mortarboard"></i> On-the-Job Training</p>
            <h1>NEMSU OJT TRACKER</h1>
            <p class="home-system-title">On-the-Job Training Placement and Monitoring System</p>
            <p>Sign in or create a student account to access the role-protected system workspace.</p>
        </section>
        <section>
            <h2>Protected role workspaces</h2>
            <div class="row g-3 mt-1">
                <?php foreach ([
                    ['Student dashboard', 'dashboard/student', 'bi-mortarboard'],
                    ['Adviser dashboard', 'dashboard/adviser', 'bi-person-workspace'],
                    ['Coordinator dashboard', 'dashboard/coordinator', 'bi-diagram-3'],
                    ['Supervisor dashboard', 'dashboard/supervisor', 'bi-buildings'],
                    ['Administrator dashboard', 'dashboard/admin', 'bi-gear'],
                ] as $preview): ?>
                    <div class="col-12 col-sm-6 col-xl-4">
                        <a class="preview-card" href="<?= app_escape(app_route_url($preview[1], $appBase)) ?>">
                            <span class="preview-card-icon"><i class="bi <?= app_escape($preview[2]) ?>"></i></span>
                            <strong><?= app_escape($preview[0]) ?></strong>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
