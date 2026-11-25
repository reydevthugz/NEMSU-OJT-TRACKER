<?php

declare(strict_types=1);

require __DIR__ . '/_feedback.php';
$listing = $adminData['listing'];
$filters = $adminData['filters'];
$record = $adminData['record'];
$editing = is_array($record);
?>
<section class="panel-card mb-4">
    <div class="panel-heading">
        <div><h2><?= $editing ? 'Edit requirement type' : 'Add requirement type' ?></h2><p>Configure institution-approved document types. Mandatory settings are not present in the current schema.</p></div>
        <?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/requirements', $appBase)) ?>">Cancel edit</a><?php endif; ?>
    </div>
    <form method="post" action="<?= app_escape(app_route_url('admin/requirements', $appBase)) ?>" class="row g-3"<?= $editing ? ' data-confirm="Save these requirement type changes?"' : '' ?>>
        <input type="hidden" name="action" value="<?= $editing ? 'admin_requirement_update' : 'admin_requirement_create' ?>"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <?php if ($editing): ?><input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>"><?php endif; ?>
        <div class="col-12 col-md-5"><label class="form-label" for="requirement-name">Requirement type</label><input class="form-control" id="requirement-name" name="name" maxlength="150" value="<?= app_escape($record['name'] ?? '') ?>" required></div>
        <div class="col-12 col-md-5"><label class="form-label" for="requirement-description">Description</label><textarea class="form-control" id="requirement-description" name="description" rows="2"><?= app_escape($record['description'] ?? '') ?></textarea></div>
        <div class="col-12 col-md-2"><label class="form-label" for="requirement-active">Status</label><select class="form-select" id="requirement-active" name="is_active" required><option value="1"<?= (int) ($record['is_active'] ?? 1) === 1 ? ' selected' : '' ?>>Active</option><option value="0"<?= (int) ($record['is_active'] ?? 1) === 0 ? ' selected' : '' ?>>Inactive</option></select></div>
        <div class="col-12"><button class="btn btn-primary" type="submit"><?= $editing ? 'Save requirement type' : 'Add requirement type' ?></button></div>
    </form>
</section>
<section class="panel-card">
    <div class="panel-heading"><div><h2>Requirement types</h2><p><?= (int) $listing['total'] ?> type(s) found.</p></div></div>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="page" value="admin/requirements">
        <div class="col-12 col-md-6"><label class="visually-hidden" for="requirement-search">Search types</label><input class="form-control" id="requirement-search" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search requirement types"></div>
        <div class="col-8 col-md-3"><label class="visually-hidden" for="requirement-status-filter">Filter status</label><select class="form-select" id="requirement-status-filter" name="status"><option value="">All statuses</option><option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Active</option><option value="inactive"<?= $filters['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option></select></div>
        <div class="col-4 col-md-3"><button class="btn btn-outline-primary w-100" type="submit">Filter</button></div>
    </form>
    <?php if ($listing['items'] === []): ?>
        <?php app_render_component('empty-state', ['title' => 'No requirement types found', 'message' => 'Add requirement types after they are approved.']); ?>
    <?php else: ?>
        <div class="table-responsive"><table class="table data-table align-middle"><caption class="visually-hidden">Requirement types</caption><thead><tr><th>Requirement type</th><th>Description</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead><tbody><?php foreach ($listing['items'] as $requirement): ?><tr><td><?= app_escape($requirement['name']) ?></td><td><?= app_escape($requirement['description'] ?? '—') ?></td><td><?php app_render_component('status-badge', ['status' => (int) $requirement['is_active'] === 1 ? 'active' : 'inactive']); ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= app_escape(app_route_url('admin/requirements', $appBase) . '&edit_id=' . (int) $requirement['id']) ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div>
        <?php require __DIR__ . '/_pagination.php'; ?>
    <?php endif; ?>
</section>
