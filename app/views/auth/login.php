<?php

declare(strict_types=1);

?>
<form class="auth-form" method="post" action="<?= app_escape(app_route_url('home', $appBase)) ?>">
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
            <input class="form-control" id="login-identity" name="email" type="email" autocomplete="username" required maxlength="190" placeholder="name@example.com">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="login-password">Password</label>
        <div class="input-icon-wrap">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input class="form-control" id="login-password" name="password" type="password" autocomplete="current-password" required minlength="8" maxlength="255" placeholder="Enter your password">
            <button class="password-toggle" type="button" data-password-toggle aria-controls="login-password" aria-pressed="false" aria-label="Show password">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <button class="btn btn-primary btn-auth w-100" type="submit">
        Sign in <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </button>
    <p class="auth-switch">New student? <a href="<?= app_escape(app_route_url('register', $appBase)) ?>">Create an account</a></p>
</form>
