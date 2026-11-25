<?php

declare(strict_types=1);

?>
<form class="auth-form registration-form" id="registration-form" method="post" action="<?= app_escape(app_route_url('home', $appBase)) ?>">
    <input type="hidden" name="action" value="register">
    <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
    <?php $registrationErrors = take_flash('errors'); ?>
    <?php if (is_array($registrationErrors) && $registrationErrors !== []): ?>
        <div class="alert alert-danger py-2" role="alert">
            <ul class="mb-0 ps-3">
                <?php foreach ($registrationErrors as $registrationError): ?>
                    <li><?= app_escape($registrationError) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (is_string($registrationErrors) && $registrationErrors !== ''): ?>
        <div class="alert alert-danger py-2" role="alert"><?= app_escape($registrationErrors) ?></div>
    <?php endif; ?>
    <?php $registrationError = take_flash('error'); ?>
    <?php if (is_string($registrationError) && $registrationError !== ''): ?>
        <div class="alert alert-danger py-2" role="alert"><?= app_escape($registrationError) ?></div>
    <?php endif; ?>
    <?php if ($programLoadError): ?>
        <div class="alert alert-warning py-2" role="alert">Registration is temporarily unavailable because academic programs could not be loaded.</div>
    <?php elseif ($programOptions === []): ?>
        <div class="alert alert-info py-2" role="status">No academic programs have been configured yet. Please contact the OJT coordinator.</div>
    <?php endif; ?>
    <fieldset class="form-section" data-registration-step>
        <legend>Student information</legend>
        <div class="row g-3">
            <div class="col-12 col-sm-6">
                <label class="form-label" for="student-first-name">First name</label>
                <input class="form-control" id="student-first-name" name="first_name" type="text" autocomplete="given-name" required maxlength="60" placeholder="First name">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label" for="student-last-name">Last name</label>
                <input class="form-control" id="student-last-name" name="last_name" type="text" autocomplete="family-name" required maxlength="60" placeholder="Last name">
            </div>
            <div class="col-12 col-sm-7">
                <label class="form-label" for="student-program">Program</label>
                <select class="form-select" id="student-program" name="program_id" required>
                    <option value="">Select a program</option>
                    <?php foreach ($programOptions as $program): ?>
                        <option value="<?= app_escape($program['id']) ?>"><?= app_escape($program['code'] . ' - ' . $program['name']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($programOptions === []): ?>
                        <option value="" disabled>No programs available</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-12 col-sm-5">
                <label class="form-label" for="student-year">Year level</label>
                <select class="form-select" id="student-year" name="year_level" required>
                    <option value="">Select year</option>
                    <option value="1">First year</option>
                    <option value="2">Second year</option>
                    <option value="3">Third year</option>
                    <option value="4">Fourth year</option>
                    <option value="5">Fifth year</option>
                    <option value="6">Sixth year</option>
                    <option value="7">Seventh year</option>
                    <option value="8">Eighth year</option>
                </select>
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label" for="student-number">Student number</label>
                <input class="form-control" id="student-number" name="student_number" type="text" required maxlength="50" placeholder="Student number">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label" for="preferred-location">Preferred location <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="preferred-location" name="preferred_location" type="text" maxlength="180" placeholder="City or municipality">
            </div>
        </div>
    </fieldset>
    <fieldset class="form-section" data-registration-step>
        <legend>Account information</legend>
        <div class="mb-3">
            <label class="form-label" for="register-email">Student email</label>
            <input class="form-control" id="register-email" name="email" type="email" autocomplete="email" required maxlength="190" placeholder="student@example.com">
        </div>
        <div class="mb-3">
            <label class="form-label" for="register-password">Create password</label>
            <div class="password-field">
                <input class="form-control" id="register-password" name="password" type="password" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Choose a password">
                <button class="password-toggle" type="button" data-password-toggle aria-controls="register-password" aria-pressed="false" aria-label="Show password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            <span class="form-hint">Use at least 8 characters. The password must fit within 72 bytes.</span>
        </div>
        <div class="mb-3">
            <label class="form-label" for="register-password-confirmation">Confirm password</label>
            <div class="password-field">
                <input class="form-control" id="register-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" maxlength="72" placeholder="Repeat the password">
                <button class="password-toggle" type="button" data-password-toggle aria-controls="register-password-confirmation" aria-pressed="false" aria-label="Show password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </fieldset>
    <div class="registration-actions">
        <button class="btn btn-secondary" type="button" data-registration-back hidden>Back</button>
        <button class="btn btn-primary" type="button" data-registration-next hidden>Continue <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        <button class="btn btn-primary" type="submit">Create account</button>
    </div>
    <p class="form-feedback" id="register-feedback" aria-live="polite"></p>
    <p class="auth-switch">Already have an account? <a href="<?= app_escape(app_route_url('login', $appBase)) ?>">Sign in</a></p>
</form>
<p class="auth-security-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Public registration always creates a student account.</p>
