<?php

declare(strict_types=1);

require __DIR__ . '/_feedback.php';
$user = $adminData['user'];
?>
<section class="panel-card">
    <div class="panel-heading">
        <div>
            <h2><?= app_escape($user['name']) ?></h2>
            <p><?= app_escape($user['email']) ?> · Account #<?= (int) $user['id'] ?> · <?php app_render_component('status-badge', ['status' => $user['status']]); ?></p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/users', $appBase)) ?>">Back to accounts</a>
    </div>
    <?php if ($user['student_number'] !== null): ?>
        <dl class="row">
            <dt class="col-sm-3">Student number</dt><dd class="col-sm-9"><?= app_escape($user['student_number']) ?></dd>
            <dt class="col-sm-3">Academic program</dt><dd class="col-sm-9"><?= app_escape($user['program_name'] ?? 'Unavailable') ?><?= $user['program_code'] ? ' (' . app_escape($user['program_code']) . ')' : '' ?></dd>
            <dt class="col-sm-3">Year level</dt><dd class="col-sm-9"><?= app_escape($user['year_level'] ?? '—') ?></dd>
            <dt class="col-sm-3">Preferred location</dt><dd class="col-sm-9"><?= app_escape($user['preferred_location'] ?? '—') ?></dd>
        </dl>
    <?php endif; ?>
    <form method="post" action="<?= app_escape(app_route_url('admin/user', $appBase)) ?>" class="row g-3" data-confirm="Save these account detail, role, or status changes?">
        <input type="hidden" name="action" value="admin_user_update">
        <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
        <div class="col-12 col-md-6">
            <label class="form-label" for="account-name">Name</label>
            <input class="form-control" id="account-name" name="name" maxlength="120" value="<?= app_escape($user['name']) ?>" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="account-email">Email</label>
            <input class="form-control" id="account-email" type="email" name="email" maxlength="190" value="<?= app_escape($user['email']) ?>" required>
        </div>
        <div class="col-12 col-md-5">
            <label class="form-label" for="account-role">Role</label>
            <select class="form-select" id="account-role" name="role" required>
                <?php foreach ($adminData['roles'] as $accountRole): ?>
                    <?php if ($accountRole === 'student' && $user['student_number'] === null && $user['role'] !== 'student'): ?>
                        <option value="student" disabled>Student (no student profile)</option>
                    <?php else: ?>
                        <option value="<?= app_escape($accountRole) ?>"<?= $user['role'] === $accountRole ? ' selected' : '' ?>><?= app_escape(ucfirst($accountRole)) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <?php if ($adminData['is_current_user']): ?><div class="form-text">Your account cannot be demoted or deactivated here.</div><?php endif; ?>
        </div>
        <div class="col-12 col-md-5">
            <label class="form-label" for="account-status">Status</label>
            <select class="form-select" id="account-status" name="status" required>
                <?php foreach ($adminData['statuses'] as $accountStatus): ?>
                    <option value="<?= app_escape($accountStatus) ?>"<?= $user['status'] === $accountStatus ? ' selected' : '' ?>><?= app_escape(ucfirst($accountStatus)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Save account changes</button></div>
    </form>
</section>
