<?php

declare(strict_types=1);

require __DIR__ . '/_feedback.php';
$listing = $adminData['listing'];
$filters = $adminData['filters'];
?>
<section class="panel-card mb-4">
    <div class="panel-heading">
        <div>
            <h2>Create a staff account</h2>
            <p>Students continue to register through the public registration page.</p>
        </div>
    </div>
    <form method="post" action="<?= app_escape(app_route_url('admin/users', $appBase)) ?>" class="row g-3" data-confirm="Create this staff account with the selected role?">
        <input type="hidden" name="action" value="admin_user_create">
        <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <div class="col-12 col-md-6">
            <label class="form-label" for="staff-name">Full name</label>
            <input class="form-control" id="staff-name" name="name" maxlength="120" required>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="staff-email">Email</label>
            <input class="form-control" id="staff-email" type="email" name="email" maxlength="190" required>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="staff-role">Staff role</label>
            <select class="form-select" id="staff-role" name="role" required>
                <option value="">Select role</option>
                <?php foreach (['adviser', 'coordinator', 'supervisor', 'admin'] as $staffRole): ?>
                    <option value="<?= app_escape($staffRole) ?>"><?= app_escape(ucfirst($staffRole)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="staff-password">Initial password</label>
            <div class="password-field">
                <input class="form-control" id="staff-password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="staff-password" aria-pressed="false" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="staff-password-confirm">Confirm password</label>
            <div class="password-field">
                <input class="form-control" id="staff-password-confirm" type="password" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="staff-password-confirm" aria-pressed="false" aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Create staff account</button></div>
    </form>
</section>
<section class="panel-card">
    <div class="panel-heading">
        <div><h2>User accounts</h2><p><?= (int) $listing['total'] ?> account(s) found.</p></div>
    </div>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="page" value="admin/users">
        <div class="col-12 col-lg-5">
            <label class="visually-hidden" for="user-search">Search accounts</label>
            <input class="form-control" id="user-search" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search name, email, student number">
        </div>
        <div class="col-6 col-lg-3">
            <label class="visually-hidden" for="user-role-filter">Filter role</label>
            <select class="form-select" id="user-role-filter" name="role">
                <option value="">All roles</option>
                <?php foreach ($adminData['roles'] as $filterRole): ?>
                    <option value="<?= app_escape($filterRole) ?>"<?= $filters['role'] === $filterRole ? ' selected' : '' ?>><?= app_escape(ucfirst($filterRole)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="visually-hidden" for="user-status-filter">Filter status</label>
            <select class="form-select" id="user-status-filter" name="status">
                <option value="">All statuses</option>
                <?php foreach ($adminData['statuses'] as $filterStatus): ?>
                    <option value="<?= app_escape($filterStatus) ?>"<?= $filters['status'] === $filterStatus ? ' selected' : '' ?>><?= app_escape(ucfirst($filterStatus)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-lg-2 d-flex gap-2">
            <button class="btn btn-outline-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/users', $appBase)) ?>">Clear</a>
        </div>
    </form>
    <?php if ($listing['items'] === []): ?>
        <?php app_render_component('empty-state', ['title' => 'No user accounts found', 'message' => 'Try changing your search or filters.']); ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table data-table align-middle">
                <caption class="visually-hidden">User accounts and their roles and statuses</caption>
                <thead><tr><th>Name</th><th>Email</th><th>Student number</th><th>Role</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    <?php foreach ($listing['items'] as $user): ?>
                        <tr>
                            <td><?= app_escape($user['name']) ?></td>
                            <td><?= app_escape($user['email']) ?></td>
                            <td><?= app_escape($user['student_number'] ?? '—') ?></td>
                            <td><?= app_escape(ucfirst($user['role'])) ?></td>
                            <td><?php app_render_component('status-badge', ['status' => $user['status']]); ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?= app_escape(app_route_url('admin/user', $appBase) . '&id=' . (int) $user['id']) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/_pagination.php'; ?>
    <?php endif; ?>
</section>
