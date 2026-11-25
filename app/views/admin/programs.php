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
        <div><h2><?= $editing ? 'Edit academic program' : 'Add academic program' ?></h2><p>The official BSCS program is preserved and protected from changes.</p></div>
        <?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= app_escape(app_route_url('admin/programs', $appBase)) ?>">Cancel edit</a><?php endif; ?>
    </div>
    <?php if (!$editing || strcasecmp((string) $record['code'], 'BSCS') !== 0): ?>
        <form method="post" action="<?= app_escape(app_route_url('admin/programs', $appBase)) ?>" class="row g-3">
            <input type="hidden" name="action" value="<?= $editing ? 'admin_program_update' : 'admin_program_create' ?>">
            <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
            <?php if ($editing): ?><input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>"><?php endif; ?>
            <div class="col-12 col-md-3">
                <label class="form-label" for="program-code">Program code</label>
                <input class="form-control" id="program-code" name="code" maxlength="30" value="<?= app_escape($record['code'] ?? '') ?>" required>
            </div>
            <div class="col-12 col-md-7">
                <label class="form-label" for="program-name">Program name</label>
                <input class="form-control" id="program-name" name="name" maxlength="150" value="<?= app_escape($record['name'] ?? '') ?>" required>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit"><?= $editing ? 'Save changes' : 'Add program' ?></button></div>
        </form>
    <?php else: ?>
        <p class="mb-0">BSCS cannot be edited.</p>
    <?php endif; ?>
</section>
<section class="panel-card">
    <div class="panel-heading"><div><h2>Academic programs</h2><p><?= (int) $listing['total'] ?> program(s) found.</p></div></div>
    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="page" value="admin/programs">
        <div class="col"><label class="visually-hidden" for="program-search">Search programs</label><input class="form-control" id="program-search" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search program name or code"></div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Search</button></div>
    </form>
    <?php if ($listing['items'] === []): ?>
        <?php app_render_component('empty-state', ['title' => 'No programs found', 'message' => 'Add the official academic programs when approved.']); ?>
    <?php else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Code</th><th>Program name</th><th><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
            <?php foreach ($listing['items'] as $program): ?>
                <tr>
                    <td><?= app_escape($program['code']) ?></td><td><?= app_escape($program['name']) ?></td>
                    <td><?php if (strcasecmp((string) $program['code'], 'BSCS') === 0): ?><span class="badge text-bg-secondary">Protected official record</span><?php else: ?><a class="btn btn-sm btn-outline-primary" href="<?= app_escape(app_route_url('admin/programs', $appBase) . '&edit_id=' . (int) $program['id']) ?>">Edit</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
        <?php require __DIR__ . '/_pagination.php'; ?>
    <?php endif; ?>
</section>
