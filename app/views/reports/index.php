<?php
$data = $reportData ?? [];
$summary = $data['summary'] ?? [];
$placements = $data['placements'] ?? [];
$attendance = $data['attendance'] ?? [];
$evaluations = $data['evaluations'] ?? [];
?>
<div class="dashboard-stack">
<section class="panel-card"><div class="panel-card-header"><div><h2 class="h5 mb-1">OJT report summary</h2><p class="text-muted mb-0">Verified hours and workflow totals.</p></div></div>
<div class="row g-3">
<div class="col-12 col-md-4"><div class="stat-card"><span>Verified hours</span><strong><?= app_escape(number_format((float)($summary['verified_hours'] ?? 0), 2)) ?></strong></div></div>
<div class="col-12 col-md-4"><div class="stat-card"><span>Evaluation average</span><strong><?= app_escape(number_format((float)($summary['evaluation_average'] ?? 0), 2)) ?> / 5</strong></div></div>
</div></section>

<section class="panel-card"><div class="panel-card-header"><div><h2 class="h5 mb-1">Export records</h2></div></div>
<div class="d-flex flex-wrap gap-2">
<?php foreach (['placements','attendance','evaluations'] as $report): ?>
<form method="post" action="<?= app_escape(app_route_url('reports/index', $appBase)) ?>"><input type="hidden" name="action" value="report_export"><input type="hidden" name="report" value="<?= app_escape($report) ?>"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>"><button class="btn btn-outline-primary" type="submit">Export <?= app_escape(ucfirst($report)) ?> CSV</button></form>
<?php endforeach; ?></div></section>

<section class="panel-card"><div class="panel-card-header"><div><h2 class="h5 mb-1">Placements</h2></div></div>
<?php if ($placements !== []): ?><div class="table-responsive"><table class="table data-table align-middle"><thead><tr><th>Student</th><th>Company</th><th>Position</th><th>Status</th><th>Required</th><th>Verified</th></tr></thead><tbody><?php foreach ($placements as $row): ?><tr><td><?= app_escape($row['student_name']) ?></td><td><?= app_escape($row['company_name']) ?></td><td><?= app_escape($row['position_title']) ?></td><td><?php app_render_component('status-badge',['status'=>$row['status']]); ?></td><td><?= app_escape($row['required_hours']) ?></td><td><?= app_escape(number_format((float)$row['verified_hours'],2)) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><?php app_render_component('empty-state',['title'=>'No placement records','message'=>'Placement reports will appear after placements are recorded.']); ?><?php endif; ?></section>

<section class="panel-card"><div class="panel-card-header"><div><h2 class="h5 mb-1">Print or save as PDF</h2><p class="text-muted mb-0">Use your browser's Print dialog and select Save as PDF.</p></div></div><button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print report</button></section>
</div>
