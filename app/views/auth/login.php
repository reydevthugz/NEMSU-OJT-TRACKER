<?php

declare(strict_types=1);

?>
<form class="prototype-form" method="post" action="<?= app_escape(app_route_url('home', $appBase)) ?>" data-prototype-scope>
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
    <?php $loginError = take_flash('error'); ?>
    <?php if (is_string($loginError) && $loginError !== ''): ?>
        <div class="alert alert-danger py-2" role="alert"><?= app_escape($loginError) ?></div>
    <?php endif; ?>
    <?php $loginSuccess = take_flash('success'); ?>
    <?php if (is_string($loginSuccess) && $loginSuccess !== ''): ?>
        <div class="alert alert-success py-2" role="status"><?= app_escape($loginSuccess) ?></div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label" for="login-identity">Email address</label>
        <div class="input-icon-wrap">
            <i class="bi bi-person" aria-hidden="true"></i>
            <input class="form-control" id="login-identity" name="email" type="email" autocomplete="username" required maxlength="190" placeholder="student@example.com">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="login-password">Password</label>
        <div class="input-icon-wrap">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input class="form-control" id="login-password" name="password" type="password" autocomplete="current-password" required minlength="8" maxlength="255" placeholder="Enter your password">
        </div>
    </div>
    <div class="form-options">
        <span class="form-muted-link">Your session expires after 30 minutes of inactivity.</span>
    </div>
    <button class="btn btn-primary btn-auth w-100" type="submit">
        Sign in <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </button>
    <p class="prototype-feedback" id="login-feedback" aria-live="polite"></p>
    <p class="auth-switch">New student? <a href="<?= app_escape(app_route_url('register', $appBase)) ?>">Create an account</a></p>
</form>
<p class="auth-security-note"><i class="bi bi-shield-check" aria-hidden="true"></i> Sign-in is protected with server-side sessions and password verification.</p>
