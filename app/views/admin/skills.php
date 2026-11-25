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
        <div><h2><?= $editing ? 'Edit skill' : 'Add skill' ?></h2><p>Skills used by student or position records are retained; this schema has no activation field.</p></div>
        <?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/skills', $appBase)) ?>">Cancel edit</a><?php endif; ?>
    </div>
    <form method="post" action="<?= app_escape(app_route_url('admin/skills', $appBase)) ?>" class="row g-3">
        <input type="hidden" name="action" value="<?= $editing ? 'admin_skill_update' : 'admin_skill_create' ?>"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <?php if ($editing): ?><input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>"><?php endif; ?>
        <div class="col-12 col-md-4"><label class="form-label" for="skill-name">Skill name</label><input class="form-control" id="skill-name" name="name" maxlength="120" value="<?= app_escape($record['name'] ?? '') ?>" required></div>
        <div class="col-12 col-md-6"><label class="form-label" for="skill-description">Description</label><input class="form-control" id="skill-description" name="description" maxlength="255" value="<?= app_escape($record['description'] ?? '') ?>"></div>
        <div class="col-12 col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><?= $editing ? 'Save skill' : 'Add skill' ?></button></div>
    </form>
</section>
<section class="panel-card">
    <div class="panel-heading"><div><h2>Skills catalog</h2><p><?= (int) $listing['total'] ?> skill(s) found.</p></div></div>
    <form method="get" class="row g-2 mb-3"><input type="hidden" name="page" value="admin/skills"><div class="col"><label class="visually-hidden" for="skill-search">Search skills</label><input class="form-control" id="skill-search" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search skills"></div><div class="col-auto"><button class="btn btn-outline-primary" type="submit">Search</button></div></form>
    <?php if ($listing['items'] === []): ?>
        <?php app_render_component('empty-state', ['title' => 'No skills found', 'message' => 'Add approved skills to build the catalog.']); ?>
    <?php else: ?>
        <div class="table-responsive"><table class="table data-table align-middle"><caption class="visually-hidden">Skills catalog</caption><thead><tr><th>Skill</th><th>Description</th><th><span class="visually-hidden">Actions</span></th></tr></thead><tbody><?php foreach ($listing['items'] as $skill): ?><tr><td><?= app_escape($skill['name']) ?></td><td><?= app_escape($skill['description'] ?? '—') ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= app_escape(app_route_url('admin/skills', $appBase) . '&edit_id=' . (int) $skill['id']) ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div>
        <?php require __DIR__ . '/_pagination.php'; ?>
    <?php endif; ?>
</section>
