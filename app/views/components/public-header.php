<?php

declare(strict_types=1);

?>
<header class="home-topbar">
    <a class="brand-lockup" href="<?= app_escape(app_route_url('home', $appBase)) ?>">
        <span class="brand-mark" aria-hidden="true">N</span>
        <span class="brand-name">NEMSU <strong>OJT TRACKER</strong></span>
    </a>
    <nav class="home-actions" aria-label="Main navigation">
        <a class="btn btn-link" href="#about-heading">About</a>
        <a class="btn btn-link" href="#features-heading">Features</a>
        <a class="btn btn-link" href="<?= app_escape(app_route_url('login', $appBase)) ?>">Sign in</a>
        <a class="btn btn-secondary" href="<?= app_escape(app_route_url('register', $appBase)) ?>">Register</a>
    </nav>
</header>
