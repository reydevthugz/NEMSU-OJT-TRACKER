<?php

declare(strict_types=1);
?>
<?php if ($studentPageLoadError): ?>
    <div class="alert alert-danger" role="alert">This page's records could not be loaded. Please try again later.</div>
<?php elseif (($studentPageData['kind'] ?? '') === 'profile'): ?>
    <?php if (($studentPageData['profile'] ?? null) !== null): ?>
        <?php $profileUser = authenticated_user() ?? []; ?>
        <section class="panel-card profile-banner" aria-label="Student account">
            <span class="avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
            <div>
                <h2 class="h5 mb-1"><?= app_escape($profileUser['name'] ?? 'Student') ?></h2>
                <p class="text-muted mb-0"><?= app_escape($profileUser['email'] ?? '') ?></p>
            </div>
        </section>
        <section class="panel-card" aria-labelledby="student-profile-heading">
            <div class="panel-heading">
                <div>
                    <h2 class="h5 mb-1" id="student-profile-heading">Registered academic information</h2>
                    <p class="text-muted mb-0">These details are associated with your signed-in account.</p>
                </div>
                <i class="bi bi-person-vcard panel-heading-icon" aria-hidden="true"></i>
            </div>
            <?php $profile = $studentPageData['profile']; ?>
            <?php $profileSuccess = take_flash('success'); ?>
            <?php $profileError = take_flash('error'); ?>
            <?php if ($profileSuccess): ?>
                <div class="alert alert-success" role="status"><?= app_escape($profileSuccess) ?></div>
            <?php endif; ?>
            <?php if ($profileError): ?>
                <div class="alert alert-danger" role="alert"><?= app_escape($profileError) ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6"><label class="form-label">Student number</label><input class="form-control" value="<?= app_escape($profile['student_number']) ?>" readonly></div>
                <div class="col-12 col-md-6"><label class="form-label">Academic program</label><input class="form-control" value="<?= app_escape($profile['program_code'] . ' - ' . $profile['program_name']) ?>" readonly></div>
            </div>

            <form method="post" action="<?= app_escape(app_route_url('student/profile', $appBase)) ?>">
                <input type="hidden" name="action" value="student_profile_update">
                <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="profile-name">Full name</label>
                        <input class="form-control" id="profile-name" name="name" maxlength="120" value="<?= app_escape($profileUser['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="profile-email">Email address</label>
                        <input class="form-control" id="profile-email" name="email" type="email" maxlength="190" value="<?= app_escape($profileUser['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="profile-year">Year level</label>
                        <select class="form-select" id="profile-year" name="year_level" required>
                            <?php for ($year = 1; $year <= 8; $year++): ?>
                                <option value="<?= $year ?>"<?= (int) $profile['year_level'] === $year ? ' selected' : '' ?>>Year <?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label" for="profile-location">Preferred location</label>
                        <input class="form-control" id="profile-location" name="preferred_location" maxlength="180" value="<?= app_escape($profile['preferred_location'] ?? '') ?>" placeholder="Example: Butuan City">
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Save profile</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <?php app_render_component('empty-state', [
            'icon' => 'bi-person-vcard',
            'title' => 'No student profile is linked',
            'message' => 'Contact the OJT coordinator if you believe this account should have a student profile.',
        ]); ?>
    <?php endif; ?>
<?php elseif (($studentPageData['rows'] ?? []) !== []): ?>
    <section class="panel-card">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1"><?= app_escape($pageTitle) ?></h2>
                <p class="text-muted small mb-0">Read-only information from records linked to your account.</p>
            </div>
            <span class="status-badge status-neutral"><?= count($studentPageData['rows']) ?> records</span>
        </div>
        <?php app_render_component('data-table', [
            'caption' => $pageTitle . ' for the signed-in student',
            'columns' => $studentPageData['columns'],
            'rows' => $studentPageData['rows'],
        ]); ?>
    </section>
<?php else: ?>
    <?php app_render_component('empty-state', [
        'icon' => 'bi-inbox',
        'title' => $studentPageData['empty_title'] ?? 'No records available',
        'message' => $studentPageData['empty_message'] ?? 'There are no records linked to your account.',
    ]); ?>
<?php endif; ?>
<?php if ($pageKey === 'student/attendance'): ?>
<section class="panel-card" aria-labelledby="attendance-submit-heading">
  <div class="panel-card-header"><div><h2 class="h5 mb-1" id="attendance-submit-heading">Submit attendance</h2><p class="text-muted mb-0">Records are submitted for supervisor verification.</p></div></div>
  <?php if (($operationalData['active_placement'] ?? null) !== null): ?>
  <form method="post" action="<?= app_escape(app_route_url('student/attendance', $appBase)) ?>">
    <input type="hidden" name="action" value="attendance_submit">
    <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
    <div class="row g-3"><div class="col-12 col-md-4"><label class="form-label" for="attendance-date">Date</label><input class="form-control" id="attendance-date" name="attendance_date" type="date" value="<?= app_escape(date('Y-m-d')) ?>" required></div><div class="col-12 col-md-4"><label class="form-label" for="time-in">Time in</label><input class="form-control" id="time-in" name="time_in" type="time"></div><div class="col-12 col-md-4"><label class="form-label" for="time-out">Time out</label><input class="form-control" id="time-out" name="time_out" type="time"></div></div>
    <div class="mt-3"><button class="btn btn-primary" type="submit">Submit attendance</button></div>
  </form>
  <?php else: ?><?php app_render_component('empty-state',['title'=>'No active placement','message'=>'Attendance can only be submitted after an active placement is approved.']); ?><?php endif; ?>
</section>
<?php elseif ($pageKey === 'student/requirements'): ?>
<section class="panel-card" aria-labelledby="requirement-submit-heading">
  <div class="panel-card-header"><div><h2 class="h5 mb-1" id="requirement-submit-heading">Submit requirement</h2><p class="text-muted mb-0">Submit a document reference or uploaded file path for coordinator review.</p></div></div>
  <form method="post" enctype="multipart/form-data" action="<?= app_escape(app_route_url('student/requirements', $appBase)) ?>">
    <input type="hidden" name="action" value="requirement_submit"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
    <div class="row g-3"><div class="col-12 col-md-5"><label class="form-label" for="requirement-type">Requirement</label><select class="form-select" id="requirement-type" name="requirement_type_id" required><option value="">Select requirement</option><?php foreach (($operationalData['requirement_types'] ?? []) as $type): ?><option value="<?= (int)$type['id'] ?>"><?= app_escape($type['name']) ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-7"><label class="form-label" for="requirement-file">Requirement document</label><input class="form-control" id="requirement-file" name="requirement_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required><div class="form-text">PDF, JPG, PNG, DOC, or DOCX; maximum 5 MB.</div></div></div>
    <div class="mt-3"><button class="btn btn-primary" type="submit">Submit requirement</button></div>
  </form>
</section>
<?php elseif ($pageKey === 'student/notifications'): ?>
<section class="panel-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h2 class="h5 mb-0">Notification actions</h2>
    <form method="post" action="<?= app_escape(app_route_url('student/notifications', $appBase)) ?>"><input type="hidden" name="action" value="notification_read_all"><input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>"><button class="btn btn-sm btn-outline-primary" type="submit">Mark all as read</button></form>
  </div>
  <?php foreach (($studentPageData['rows'] ?? []) as $notification): ?>
    <?php if (($notification['read_at'] ?? null) === null): ?>
      <form class="mt-2 d-flex justify-content-between align-items-center gap-2 border-top pt-2" method="post" action="<?= app_escape(app_route_url('student/notifications', $appBase)) ?>">
        <span><?= app_escape($notification['title']) ?></span>
        <input type="hidden" name="action" value="notification_read">
        <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
        <button class="btn btn-sm btn-outline-secondary" type="submit">Mark read</button>
      </form>
    <?php endif; ?>
  <?php endforeach; ?>
</section>
<?php endif; ?>
