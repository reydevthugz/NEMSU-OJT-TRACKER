<?php
$submissions = $operationalData['submissions'] ?? [];
$success = take_flash('success');
$error = take_flash('error');
?>
<div class="dashboard-stack">
<?php if (is_string($success)&&$success!== ''): ?><div class="alert alert-success"><?= app_escape($success) ?></div><?php endif; ?>
<?php if (is_string($error)&&$error!== ''): ?><div class="alert alert-danger"><?= app_escape($error) ?></div><?php endif; ?>
<section class="panel-card"><div class="panel-card-header"><div><h2 class="h5 mb-1">Requirement submissions</h2><p class="text-muted mb-0">Approve or reject student requirement documents.</p></div></div>
<?php if ($submissions !== []): ?><div class="table-responsive"><table class="table data-table align-middle"><thead><tr><th>Student</th><th>Requirement</th><th>Document</th><th>Submitted</th><th>Status</th><th>Review</th></tr></thead><tbody>
<?php foreach ($submissions as $row): ?><tr><td><strong><?= app_escape($row['student_name']) ?></strong><div class="small text-muted"><?= app_escape($row['student_number']) ?></div></td><td><?= app_escape($row['requirement_name']) ?></td><td><?= app_escape($row['file_path']) ?></td><td><?= app_escape($row['submitted_at']) ?></td><td><?php app_render_component('status-badge',['status'=>$row['status']]); ?></td><td><?php if ($row['status']==='submitted'||$row['status']==='pending'): ?><form method="post" action="<?= app_escape(app_route_url('coordinator/requirements',$appBase)) ?>"><input type="hidden" name="action" value="requirement_review"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>"><input type="hidden" name="submission_id" value="<?= (int)$row['id'] ?>"><select class="form-select form-select-sm mb-1" name="review_status"><option value="approved">Approve</option><option value="rejected">Reject</option></select><input class="form-control form-control-sm mb-1" name="remarks" maxlength="5000" placeholder="Remarks"><button class="btn btn-sm btn-primary" type="submit">Save</button></form><?php else: ?><?= app_escape($row['remarks'] ?: '—') ?><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><?php app_render_component('empty-state',['title'=>'No requirement submissions','message'=>'Student requirement submissions will appear here.']); ?><?php endif; ?></section>
</div>
