<?php

declare(strict_types=1);

?>
<main class="home-main container" id="main-content">
        <section class="home-hero">
            <p class="hero-kicker"><i class="bi bi-mortarboard" aria-hidden="true"></i> NEMSU On-the-Job Training</p>
            <h1>NEMSU OJT TRACKER</h1>
            <p class="home-system-title">On-the-Job Training Placement and Monitoring System</p>
            <p class="home-hero-copy">A role-protected university portal for OJT placement coordination and student monitoring from campus to workplace.</p>
            <div class="home-hero-actions">
                <a class="btn btn-light text-primary" href="<?= app_escape(app_route_url('login', $appBase)) ?>">Sign in to your account <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                <a class="btn btn-secondary" href="<?= app_escape(app_route_url('register', $appBase)) ?>">Create a student account</a>
            </div>
        </section>
        <section class="home-section" aria-labelledby="about-heading">
            <div class="home-section-heading">
                <h2 id="about-heading">About the system</h2>
                <p>NEMSU OJT TRACKER brings student, academic, company, and training records together for the university's OJT community.</p>
            </div>
            <div class="home-info-card">
                <p>Students can review their OJT records, staff can view summaries for their roles, and administrators maintain academic and company reference data.</p>
            </div>
        </section>
        <section class="home-section" aria-labelledby="features-heading">
            <div class="home-section-heading">
                <h2 id="features-heading">System features</h2>
            </div>
            <div class="row g-3">
                <?php foreach ([
                    ['Secure account access', 'Protected sign-in and student registration with server-side role checks.', 'bi-shield-lock'],
                    ['Reference data management', 'Authorized administrators maintain accounts and approved academic and company reference records.', 'bi-journal-check'],
                    ['Personal student records', 'Students can review academic details, skills, preferences, and linked OJT records.', 'bi-person-vcard'],
                    ['Training overview', 'Role-specific summaries show only records supported by the current database relationships.', 'bi-graph-up'],
                    ['Company-scoped supervisor view', 'Supervisor summaries include placements only at companies linked to that account.', 'bi-buildings'],
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
            <ul class="home-user-list" aria-label="Intended system users">
                <li>Students</li>
                <li>Advisers</li>
                <li>OJT coordinators</li>
                <li>Company supervisors</li>
                <li>System administrators</li>
            </ul>
        </section>
</main>
