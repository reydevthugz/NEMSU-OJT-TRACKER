<?php
$students = $adviserData['students'] ?? [];
?>
<section class="panel-card" aria-labelledby="assigned-students-heading">
  <div class="panel-card-header"><div><h2 class="h5 mb-1" id="assigned-students-heading">Assigned students</h2><p class="text-muted mb-0">Verified attendance hours are included in progress totals.</p></div></div>
  <?php if ($students !== []): ?>
  <div class="table-responsive"><table class="table data-table align-middle"><thead><tr><th>Student</th><th>Number</th><th>Program</th><th>Placement</th><th>Status</th><th>Progress</th></tr></thead><tbody>
  <?php foreach ($students as $student): ?>
  <tr>
    <td><strong><?= app_escape($student['name']) ?></strong><div class="text-muted small"><?= app_escape($student['email']) ?></div></td>
    <td><?= app_escape($student['student_number']) ?></td><td><?= app_escape($student['program_code']) ?></td>
    <td><?= app_escape(($student['company_name'] ?: '—') . ' / ' . ($student['position_title'] ?: '—')) ?></td>
    <td><?php if ($student['placement_status']): app_render_component('status-badge', ['status' => $student['placement_status']]); else: ?><span class="text-muted">No placement</span><?php endif; ?></td>
    <td><?= app_escape((string) $student['recorded_hours']) ?> / <?= app_escape((string) ($student['required_hours'] ?: '—')) ?> hours</td>
  </tr>
  <?php endforeach; ?></tbody></table></div>
  <?php else: ?><?php app_render_component('empty-state', ['title' => 'No assigned students', 'message' => 'An administrator must assign students to this adviser account.']); ?><?php endif; ?>
</section>
