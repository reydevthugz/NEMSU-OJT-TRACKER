<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NEMSU OJT TRACKER</title>
    <link rel="icon" href="<?= app_escape($assetBase) ?>/assets/images/favicon.svg?v=3" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<<<<<<< Updated upstream
    <link href="<?= app_escape($assetBase) ?>/assets/css/app.css" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/components.css" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/responsive.css" rel="stylesheet">
=======
    <link href="<?= app_escape($assetBase) ?>/assets/css/app.css?v=3" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/components.css?v=3" rel="stylesheet">
    <link href="<?= app_escape($assetBase) ?>/assets/css/responsive.css?v=3" rel="stylesheet">
>>>>>>> Stashed changes
</head>
<body class="home-body">
    <header class="home-topbar">
        <a class="brand-lockup" href="<?= app_escape(app_route_url('home', $appBase)) ?>">
            <span class="brand-mark">N</span><span class="brand-name">NEMSU <strong>OJT TRACKER</strong></span>
        </a>
        <nav class="home-actions" aria-label="Account links">
            <a class="btn btn-link" href="<?= app_escape(app_route_url('login', $appBase)) ?>">Sign in</a>
            <a class="btn btn-secondary" href="<?= app_escape(app_route_url('register', $appBase)) ?>">Student registration</a>
        </nav>
    </header>
    <main class="home-main container">
        <section class="home-hero">
            <p class="hero-kicker"><i class="bi bi-mortarboard" aria-hidden="true"></i> NEMSU On-the-Job Training</p>
            <h1>NEMSU OJT TRACKER</h1>
            <p class="home-system-title">On-the-Job Training Placement and Monitoring System</p>
<<<<<<< Updated upstream
            <p>Sign in or create a student account to access the role-protected system workspace.</p>
=======
            <p class="home-hero-copy">A university-focused portal designed to support OJT placement coordination and student monitoring from campus to workplace.</p>
            <div class="home-hero-actions">
                <a class="btn btn-light text-primary" href="<?= app_escape(app_route_url('login', $appBase)) ?>">Sign in to your account <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                <a class="btn btn-secondary" href="<?= app_escape(app_route_url('register', $appBase)) ?>">Create a student account</a>
            </div>
>>>>>>> Stashed changes
        </section>
        <section class="home-section" aria-labelledby="about-heading">
            <div class="home-section-heading">
                <h2 id="about-heading">About the system</h2>
                <p>NEMSU OJT TRACKER is intended to give students and OJT stakeholders a shared view of academic and workplace training information.</p>
            </div>
            <div class="home-info-card">
                <p>The current build provides secure account access, role-protected dashboards, and read-only views of records already linked to an account. Operational placement and monitoring actions are not enabled yet.</p>
            </div>
        </section>
        <section class="home-section" aria-labelledby="features-heading">
            <div class="home-section-heading">
                <h2 id="features-heading">System features</h2>
                <p>Available foundation features are presented clearly, without implying that unfinished workflows are active.</p>
            </div>
            <div class="row g-3">
                <?php foreach ([
                    ['Secure account access', 'Protected sign-in and student registration with role-based access.', 'bi-shield-lock'],
                    ['Personal student records', 'View academic details, skills, preferences, and any OJT records linked to your account.', 'bi-person-vcard'],
                    ['Training overview', 'See current placement records and stored attendance totals when those records exist.', 'bi-graph-up'],
                    ['Company-scoped supervisor view', 'Supervisor summaries only include placements at companies linked to that account.', 'bi-buildings'],
                ] as [$featureTitle, $featureDescription, $featureIcon]): ?>
                    <div class="col-12 col-sm-6">
                        <article class="home-feature-card">
                            <span class="home-feature-icon" aria-hidden="true"><i class="bi <?= app_escape($featureIcon) ?>"></i></span>
                            <h3><?= app_escape($featureTitle) ?></h3>
                            <p><?= app_escape($featureDescription) ?></p>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="home-section" aria-labelledby="users-heading">
            <div class="home-section-heading">
                <h2 id="users-heading">Intended users</h2>
                <p>Access to protected workspaces depends on the role assigned to each account.</p>
            </div>
            <ul class="home-user-list">
                <li>Students</li>
                <li>Advisers</li>
                <li>OJT coordinators</li>
                <li>Company supervisors</li>
                <li>System administrators</li>
            </ul>
        </section>
    </main>
</body>
</html>
