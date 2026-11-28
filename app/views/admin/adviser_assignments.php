<?php
$options = $adviserData['options'] ?? [];
$assignments = $adviserData['assignments'] ?? [];
$advisers = $options['advisers'] ?? [];
$students = $options['students'] ?? [];
$success = take_flash('success');
$error = take_flash('error');
?>
<div class="dashboard-stack">
<?php if (is_string($success) && $success !== ''): ?><div class="alert alert-success" role="status"><?= app_escape($success) ?></div><?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?><div class="alert alert-danger" role="alert"><?= app_escape($error) ?></div><?php endif; ?>

<section class="panel-card" aria-labelledby="assign-adviser-heading">
  <div class="panel-card-header"><div><h2 class="h5 mb-1" id="assign-adviser-heading">Assign adviser to student</h2><p class="text-muted mb-0">Each active student can have one adviser.</p></div></div>
  <form method="post" action="<?= app_escape(app_route_url('admin/adviser-assignments', $appBase)) ?>">
    <input type="hidden" name="action" value="adviser_assignment_create">
    <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
    <div class="row g-3">
      <div class="col-12 col-md-5">
        <label class="form-label" for="adviser-user">Adviser</label>
        <select class="form-select" id="adviser-user" name="adviser_user_id" required>
          <option value="">Select adviser</option>
          <?php foreach ($advisers as $adviser): ?><option value="<?= (int) $adviser['id'] ?>"><?= app_escape($adviser['name'] . ' — ' . $adviser['email']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-5">
        <label class="form-label" for="adviser-student">Student</label>
        <select class="form-select" id="adviser-student" name="student_profile_id" required>
          <option value="">Select student</option>
          <?php foreach ($students as $student): ?><option value="<?= (int) $student['id'] ?>"><?= app_escape($student['name'] . ' — ' . $student['student_number']) ?><?= $student['assigned_adviser_id'] ? ' (assigned)' : '' ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Save</button></div>
    </div>
  </form>
</section>

<section class="panel-card" aria-labelledby="adviser-list-heading">
  <div class="panel-card-header"><div><h2 class="h5 mb-1" id="adviser-list-heading">Current assignments</h2></div></div>
  <?php if ($assignments !== []): ?>
  <div class="table-responsive"><table class="table data-table align-middle"><thead><tr><th>Adviser</th><th>Student</th><th>Student number</th><th>Status</th><th>Assigned</th><th></th></tr></thead><tbody>
  <?php foreach ($assignments as $assignment): ?><tr>
    <td><?= app_escape($assignment['adviser_name']) ?></td><td><?= app_escape($assignment['student_name']) ?></td><td><?= app_escape($assignment['student_number']) ?></td>
    <td><?php app_render_component('status-badge', ['status' => $assignment['status']]); ?></td><td><?= app_escape($assignment['assigned_at']) ?></td>
    <td><?php if ($assignment['status'] === 'active'): ?><form method="post" action="<?= app_escape(app_route_url('admin/adviser-assignments', $appBase)) ?>"><input type="hidden" name="action" value="adviser_assignment_deactivate"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>"><input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>"><button class="btn btn-sm btn-outline-secondary" type="submit">End</button></form><?php endif; ?></td>
  </tr><?php endforeach; ?></tbody></table></div>
  <?php else: ?><?php app_render_component('empty-state', ['title' => 'No adviser assignments', 'message' => 'Assign an adviser to a student using the form above.']); ?><?php endif; ?>
</section>
</div>
