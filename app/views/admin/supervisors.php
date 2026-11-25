<?php

declare(strict_types=1);

require __DIR__ . '/_feedback.php';
$listing = $adminData['listing'];
$filters = $adminData['filters'];
?>
<section class="panel-card mb-4">
    <div class="panel-heading"><div><h2>Associate a supervisor</h2><p>Only active accounts with the supervisor role may be associated.</p></div></div>
    <form method="post" action="<?= app_escape(app_route_url('admin/supervisors', $appBase)) ?>" class="row g-3" data-confirm="Associate this supervisor with the selected company?">
        <input type="hidden" name="action" value="admin_supervisor_create"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <div class="col-12 col-md-5"><label class="form-label" for="new-supervisor-company">Company</label><select class="form-select" id="new-supervisor-company" name="company_id" required><option value="">Choose company</option><?php foreach ($adminData['companies'] as $company): ?><option value="<?= (int) $company['id'] ?>"><?= app_escape($company['name']) ?><?= $company['status'] === 'inactive' ? ' (inactive)' : '' ?></option><?php endforeach; ?></select></div>
        <div class="col-12 col-md-5"><label class="form-label" for="new-supervisor-user">Supervisor account</label><select class="form-select" id="new-supervisor-user" name="supervisor_user_id" required><option value="">Choose active supervisor</option><?php foreach ($adminData['supervisor_users'] as $user): ?><option value="<?= (int) $user['id'] ?>"><?= app_escape($user['name']) ?> — <?= app_escape($user['email']) ?></option><?php endforeach; ?></select></div>
        <div class="col-12 col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Associate</button></div>
    </form>
</section>
<section class="panel-card">
    <div class="panel-heading"><div><h2>Company supervisor associations</h2><p><?= (int) $listing['total'] ?> association(s) found. Account status is shown without exposing password data.</p></div></div>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="page" value="admin/supervisors">
        <div class="col-12 col-md-7"><label class="visually-hidden" for="supervisor-search">Search associations</label><input class="form-control" id="supervisor-search" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search supervisor, email, or company"></div>
        <div class="col-8 col-md-3"><label class="visually-hidden" for="supervisor-status">Filter supervisor status</label><select class="form-select" id="supervisor-status" name="status"><option value="">All statuses</option><?php foreach ($adminData['statuses'] as $status): ?><option value="<?= app_escape($status) ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= app_escape(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
        <div class="col-4 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filter</button></div>
    </form>
    <?php if ($listing['items'] === []): ?>
        <?php app_render_component('empty-state', ['title' => 'No supervisor associations', 'message' => 'Create supervisor accounts in user management, then associate them with companies.']); ?>
    <?php else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Supervisor</th><th>Email</th><th>Company</th><th>Account status</th><th>Update association</th></tr></thead><tbody>
            <?php foreach ($listing['items'] as $association): ?>
                <tr>
                    <td><?= app_escape($association['supervisor_name']) ?></td><td><?= app_escape($association['supervisor_email']) ?></td><td><?= app_escape($association['company_name']) ?></td><td><?= app_escape(ucfirst($association['supervisor_status'])) ?></td>
                    <td>
                        <?php if ($association['supervisor_status'] !== 'active'): ?>
                            <span class="text-body-secondary">Reactivate the supervisor account before changing this association.</span>
                        <?php else: ?>
                        <form method="post" action="<?= app_escape(app_route_url('admin/supervisors', $appBase)) ?>" class="d-flex flex-column flex-xl-row gap-2" data-confirm="Change this supervisor-company association?">
                            <input type="hidden" name="action" value="admin_supervisor_update"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>"><input type="hidden" name="association_id" value="<?= (int) $association['id'] ?>">
                            <label class="visually-hidden" for="company-<?= (int) $association['id'] ?>">Company</label><select class="form-select form-select-sm" id="company-<?= (int) $association['id'] ?>" name="company_id" required><?php foreach ($adminData['companies'] as $company): ?><option value="<?= (int) $company['id'] ?>"<?= (int) $association['company_id'] === (int) $company['id'] ? ' selected' : '' ?>><?= app_escape($company['name']) ?></option><?php endforeach; ?></select>
                            <label class="visually-hidden" for="supervisor-<?= (int) $association['id'] ?>">Supervisor</label><select class="form-select form-select-sm" id="supervisor-<?= (int) $association['id'] ?>" name="supervisor_user_id" required><?php foreach ($adminData['supervisor_users'] as $user): ?><option value="<?= (int) $user['id'] ?>"<?= (int) $association['supervisor_user_id'] === (int) $user['id'] ? ' selected' : '' ?>><?= app_escape($user['name']) ?></option><?php endforeach; ?></select>
                            <button class="btn btn-sm btn-outline-primary text-nowrap" type="submit">Save</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
        <?php require __DIR__ . '/_pagination.php'; ?>
    <?php endif; ?>
</section>
