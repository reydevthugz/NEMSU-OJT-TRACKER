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
        <div><h2><?= $editing ? 'Edit company position' : 'Add company position' ?></h2><p>Capacity cannot be lower than the number of active placements. No student assignment is performed here.</p></div>
        <?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/positions', $appBase)) ?>">Cancel edit</a><?php endif; ?>
    </div>
    <form method="post" action="<?= app_escape(app_route_url('admin/positions', $appBase)) ?>" class="row g-3"<?= $editing ? ' data-confirm="Save these position and capacity changes?"' : '' ?>>
        <input type="hidden" name="action" value="<?= $editing ? 'admin_position_update' : 'admin_position_create' ?>"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <?php if ($editing): ?><input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>"><?php endif; ?>
        <div class="col-12 col-md-5"><label class="form-label" for="position-company">Company</label><select class="form-select" id="position-company" name="company_id" required><option value="">Choose company</option><?php foreach ($adminData['companies'] as $company): ?><option value="<?= (int) $company['id'] ?>"<?= (int) ($record['company_id'] ?? 0) === (int) $company['id'] ? ' selected' : '' ?>><?= app_escape($company['name']) ?><?= $company['status'] === 'inactive' ? ' (inactive)' : '' ?></option><?php endforeach; ?></select></div>
        <div class="col-12 col-md-7"><label class="form-label" for="position-title">Position title</label><input class="form-control" id="position-title" name="title" maxlength="180" value="<?= app_escape($record['title'] ?? '') ?>" required></div>
        <div class="col-12"><label class="form-label" for="position-description">Description</label><textarea class="form-control" id="position-description" name="description" rows="3"><?= app_escape($record['description'] ?? '') ?></textarea></div>
        <div class="col-12 col-md-4"><label class="form-label" for="position-slots">Available slots</label><input class="form-control" id="position-slots" type="number" min="0" max="65535" name="available_slots" value="<?= app_escape($record['available_slots'] ?? 1) ?>" required><div class="form-text">Active placement counts are shown in the positions table.</div></div>
        <div class="col-12 col-md-4"><label class="form-label" for="position-status">Status</label><select class="form-select" id="position-status" name="status" required><?php foreach ($adminData['statuses'] as $status): ?><option value="<?= app_escape($status) ?>"<?= ($record['status'] ?? 'open') === $status ? ' selected' : '' ?>><?= app_escape(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><button class="btn btn-primary" type="submit"><?= $editing ? 'Save position' : 'Add position' ?></button></div>
    </form>
</section>
<section class="panel-card">
    <div class="panel-heading"><div><h2>Company positions</h2><p><?= (int) $listing['total'] ?> position(s) found.</p></div></div>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="page" value="admin/positions">
        <div class="col-12 col-md-4"><label class="visually-hidden" for="position-search">Search positions</label><input class="form-control" id="position-search" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search title or company"></div>
        <div class="col-6 col-md-3"><label class="visually-hidden" for="position-company-filter">Filter company</label><select class="form-select" id="position-company-filter" name="company_id"><option value="">All companies</option><?php foreach ($adminData['companies'] as $company): ?><option value="<?= (int) $company['id'] ?>"<?= (int) $filters['company_id'] === (int) $company['id'] ? ' selected' : '' ?>><?= app_escape($company['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-md-2"><label class="visually-hidden" for="position-status-filter">Filter status</label><select class="form-select" id="position-status-filter" name="status"><option value="">All statuses</option><?php foreach ($adminData['statuses'] as $status): ?><option value="<?= app_escape($status) ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= app_escape(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
        <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Filter</button><a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/positions', $appBase)) ?>">Clear</a></div>
    </form>
    <?php if ($listing['items'] === []): ?>
        <?php app_render_component('empty-state', ['title' => 'No positions found', 'message' => 'Add positions only after receiving verified company information.']); ?>
    <?php else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Company</th><th>Position</th><th>Capacity</th><th>Active placements</th><th>Remaining slots</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
            <?php foreach ($listing['items'] as $position): ?><tr>
                <td><?= app_escape($position['company_name']) ?></td><td><?= app_escape($position['title']) ?></td><td><?= (int) $position['available_slots'] ?></td><td><?= (int) $position['active_placements'] ?></td><td><?= (int) $position['remaining_slots'] ?></td><td><?= app_escape(ucfirst($position['status'])) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= app_escape(app_route_url('admin/positions', $appBase) . '&edit_id=' . (int) $position['id']) ?>">Edit</a></td>
            </tr><?php endforeach; ?>
        </tbody></table></div>
        <?php require __DIR__ . '/_pagination.php'; ?>
    <?php endif; ?>
</section>
