<?php

declare(strict_types=1);

require __DIR__ . '/_feedback.php';
$listing = $adminData['listing'];
$filters = $adminData['filters'];
$record = $adminData['record'];
$viewRecord = $adminData['view_record'];
$editing = is_array($record);
$companyValue = static fn (string $key): string => (string) ($record[$key] ?? '');
?>
<?php if (is_array($viewRecord)): ?>
    <section class="panel-card mb-4">
        <div class="panel-heading">
            <div><h2><?= app_escape($viewRecord['name']) ?></h2><p>Company details · <?php app_render_component('status-badge', ['status' => $viewRecord['status']]); ?></p></div>
            <div class="d-flex gap-2">
                <a class="btn btn-primary" href="<?= app_escape(app_route_url('admin/companies', $appBase) . '&edit_id=' . (int) $viewRecord['id']) ?>">Edit company</a>
                <a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/companies', $appBase)) ?>">Close details</a>
            </div>
        </div>
        <dl class="row mb-0">
            <dt class="col-sm-3">Address</dt><dd class="col-sm-9"><?= app_escape($viewRecord['address'] ?? '—') ?></dd>
            <dt class="col-sm-3">Contact name</dt><dd class="col-sm-9"><?= app_escape($viewRecord['contact_name'] ?? '—') ?></dd>
            <dt class="col-sm-3">Contact email</dt><dd class="col-sm-9"><?= app_escape($viewRecord['contact_email'] ?? '—') ?></dd>
            <dt class="col-sm-3">Contact phone</dt><dd class="col-sm-9"><?= app_escape($viewRecord['contact_phone'] ?? '—') ?></dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9"><?= nl2br(app_escape($viewRecord['description'] ?? '—')) ?></dd>
        </dl>
    </section>
<?php endif; ?>
<section class="panel-card mb-4">
    <div class="panel-heading">
        <div><h2><?= $editing ? 'Edit company' : 'Add company' ?></h2><p>Only enter verified partner information. Referenced companies cannot be deleted.</p></div>
        <?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/companies', $appBase)) ?>">Cancel edit</a><?php endif; ?>
    </div>
    <form method="post" action="<?= app_escape(app_route_url('admin/companies', $appBase)) ?>" class="row g-3"<?= $editing ? ' data-confirm="Save these company changes?"' : '' ?>>
        <input type="hidden" name="action" value="<?= $editing ? 'admin_company_update' : 'admin_company_create' ?>">
        <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <?php if ($editing): ?><input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>"><?php endif; ?>
        <div class="col-12 col-md-6"><label class="form-label" for="company-name">Company name</label><input class="form-control" id="company-name" name="name" maxlength="180" value="<?= app_escape($companyValue('name')) ?>" required></div>
        <div class="col-12 col-md-6"><label class="form-label" for="company-address">Address</label><input class="form-control" id="company-address" name="address" maxlength="255" value="<?= app_escape($companyValue('address')) ?>"></div>
        <div class="col-12 col-md-4"><label class="form-label" for="contact-name">Contact name</label><input class="form-control" id="contact-name" name="contact_name" maxlength="120" value="<?= app_escape($companyValue('contact_name')) ?>"></div>
        <div class="col-12 col-md-4"><label class="form-label" for="contact-email">Contact email</label><input class="form-control" id="contact-email" type="email" name="contact_email" maxlength="190" value="<?= app_escape($companyValue('contact_email')) ?>"></div>
        <div class="col-12 col-md-4"><label class="form-label" for="contact-phone">Contact phone</label><input class="form-control" id="contact-phone" name="contact_phone" maxlength="50" value="<?= app_escape($companyValue('contact_phone')) ?>"></div>
        <div class="col-12"><label class="form-label" for="company-description">Description</label><textarea class="form-control" id="company-description" name="description" rows="3"><?= app_escape($companyValue('description')) ?></textarea></div>
        <div class="col-12 col-md-4"><label class="form-label" for="company-status">Status</label><select class="form-select" id="company-status" name="status" required>
            <?php foreach ($adminData['statuses'] as $status): ?><option value="<?= app_escape($status) ?>"<?= ($record['status'] ?? 'active') === $status ? ' selected' : '' ?>><?= app_escape(ucfirst($status)) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-12"><button class="btn btn-primary" type="submit"><?= $editing ? 'Save company' : 'Add company' ?></button></div>
    </form>
</section>
<section class="panel-card">
    <div class="panel-heading"><div><h2>Registered companies</h2><p><?= (int) $listing['total'] ?> company(ies) found.</p></div></div>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="page" value="admin/companies">
        <div class="col-12 col-md-6"><label class="visually-hidden" for="company-search">Search companies</label><input class="form-control" id="company-search" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search company names"></div>
        <div class="col-8 col-md-3"><label class="visually-hidden" for="company-filter-status">Filter status</label><select class="form-select" id="company-filter-status" name="status"><option value="">All statuses</option><?php foreach ($adminData['statuses'] as $status): ?><option value="<?= app_escape($status) ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= app_escape(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
        <div class="col-4 col-md-3 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Filter</button><a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/companies', $appBase)) ?>">Clear</a></div>
    </form>
    <?php if ($listing['items'] === []): ?>
        <?php app_render_component('empty-state', ['title' => 'No companies found', 'message' => 'Add verified company records when available.']); ?>
    <?php else: ?>
        <div class="table-responsive"><table class="table data-table align-middle"><caption class="visually-hidden">Registered companies</caption><thead><tr><th>Company</th><th>Contact</th><th>Email</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
            <?php foreach ($listing['items'] as $company): ?><tr>
                <td><?= app_escape($company['name']) ?></td><td><?= app_escape($company['contact_name'] ?? '—') ?></td><td><?= app_escape($company['contact_email'] ?? '—') ?></td><td><?php app_render_component('status-badge', ['status' => $company['status']]); ?></td>
                <td class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="<?= app_escape(app_route_url('admin/companies', $appBase) . '&view_id=' . (int) $company['id']) ?>">View</a> <a class="btn btn-sm btn-outline-primary" href="<?= app_escape(app_route_url('admin/companies', $appBase) . '&edit_id=' . (int) $company['id']) ?>">Edit</a></td>
            </tr><?php endforeach; ?>
        </tbody></table></div>
        <?php require __DIR__ . '/_pagination.php'; ?>
    <?php endif; ?>
</section>
