<?php

declare(strict_types=1);

$placements = $placementPageData['placements'] ?? [];
$students = $placementPageData['students'] ?? [];
$positions = $placementPageData['positions'] ?? [];
$matches = $placementPageData['matches'] ?? [];
$filters = $placementPageData['filters'] ?? ['search' => '', 'status' => ''];
$statuses = $placementPageData['statuses'] ?? [];
$selectedStudentId = (int) ($placementPageData['selected_student_id'] ?? 0);
$success = take_flash('success');
$error = take_flash('error');
?>

<div class="dashboard-stack">
    <?php if (is_string($success) && $success !== ''): ?>
        <div class="alert alert-success" role="status"><?= app_escape($success) ?></div>
    <?php endif; ?>
    <?php if (is_string($error) && $error !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= app_escape($error) ?></div>
    <?php endif; ?>

    <section class="panel-card" aria-labelledby="assign-placement-heading">
        <div class="panel-card-header">
            <div>
                <h2 class="h5 mb-1" id="assign-placement-heading">Assign a placement</h2>
                <p class="text-muted mb-0">Only students without an open placement and positions with remaining slots are shown.</p>
            </div>
        </div>

        <form method="post" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>">
            <input type="hidden" name="action" value="placement_assign">
            <input type="hidden" name="csrf_token" value="<?= app_escape(csrf_token()) ?>">
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="placement-student">Student</label>
                    <select class="form-select" id="placement-student" name="student_profile_id" required>
                        <option value="">Select student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= (int) $student['student_profile_id'] ?>"<?= $selectedStudentId === (int) $student['student_profile_id'] ? ' selected' : '' ?>><?= app_escape($student['name'] . ' — ' . $student['student_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="placement-position">Open position</label>
                    <select class="form-select" id="placement-position" name="position_id" required>
                        <option value="">Select position</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?= (int) $position['id'] ?>">
                                <?= app_escape($position['company_name'] . ' — ' . $position['title'] . ' (' . $position['remaining_slots'] . ' slot(s) left)') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="required-hours">Required hours</label>
                    <input class="form-control" id="required-hours" name="required_hours" type="number" min="1" max="3000" value="486" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="start-date">Start date</label>
                    <input class="form-control" id="start-date" name="start_date" type="date">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="end-date">End date</label>
                    <input class="form-control" id="end-date" name="end_date" type="date">
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary" type="submit">Assign placement</button></div>
        </form>
    </section>

    <?php if ($selectedStudentId > 0): ?>
    <section class="panel-card" aria-labelledby="matches-heading">
        <div class="panel-card-header"><div><h2 class="h5 mb-1" id="matches-heading">Suggested matches</h2><p class="text-muted mb-0">Preference matches are listed first; remaining slots are considered.</p></div></div>
        <?php if ($matches !== []): ?>
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead><tr><th>Company</th><th>Position</th><th>Preference</th><th>Required skills</th><th>Location</th><th>Available</th></tr></thead>
                    <tbody>
                    <?php foreach ($matches as $match): ?>
                        <tr>
                            <td><?= app_escape($match['company_name']) ?></td>
                            <td><?= app_escape($match['title']) ?></td>
                            <td><?= $match['priority'] !== null ? 'Priority ' . (int) $match['priority'] : 'No preference' ?></td>
                            <td><?= (int) $match['matched_skills'] ?> / <?= (int) $match['required_skills'] ?> required skills</td>
                            <td><?= ((int) ($match['location_match'] ?? 0) === 1) ? 'Preferred location match' : app_escape($match['address'] ?: 'No location match') ?></td>
                            <td><?= (int) $match['remaining_slots'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <?php app_render_component('empty-state', ['title' => 'No matching open positions', 'message' => 'No open position has capacity for this student.']); ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="panel-card" aria-labelledby="placements-heading">
        <div class="panel-card-header"><div><h2 class="h5 mb-1" id="placements-heading">Placement records</h2><p class="text-muted mb-0">Assignment and coordinator approval are separate workflow steps.</p></div></div>

        <form class="row g-2 mb-3" method="get" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>">
            <input type="hidden" name="page" value="coordinator/placements">
            <div class="col-12 col-md-6"><input class="form-control" name="q" value="<?= app_escape($filters['search']) ?>" placeholder="Search student, email, position, or company"></div>
            <div class="col-8 col-md-4"><select class="form-select" name="status"><option value="">All statuses</option><?php foreach ($statuses as $status): ?><option value="<?= app_escape($status) ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= app_escape(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
            <div class="col-4 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filter</button></div>
        </form>

        <?php if ($placements !== []): ?>
            <div class="table-responsive">
                <table class="table data-table align-middle">
                    <thead><tr><th>Student</th><th>Company</th><th>Position</th><th>Hours</th><th>Status</th><th>Dates</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                    <?php foreach ($placements as $placement): ?>
                        <tr>
                            <td><strong><?= app_escape($placement['student_name']) ?></strong><div class="text-muted small"><?= app_escape($placement['student_number']) ?></div></td>
                            <td><?= app_escape($placement['company_name']) ?></td>
                            <td><?= app_escape($placement['position_title']) ?></td>
                            <td><?= (int) $placement['required_hours'] ?></td>
                            <td><?php app_render_component('status-badge', ['status' => $placement['status']]); ?></td>
                            <td><?= app_escape(($placement['start_date'] ?: '—') . ' / ' . ($placement['end_date'] ?: '—')) ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php $status = $placement['status']; ?>
                                    <?php if ($status === 'pending'): ?>
                                        <form method="post" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>"><?php echo '<input type="hidden" name="action" value="placement_approve"><input type="hidden" name="csrf_token" value="' . app_escape(csrf_token()) . '"><input type="hidden" name="placement_id" value="' . (int) $placement['id'] . '"><button class="btn btn-sm btn-success" type="submit">Approve</button>'; ?></form>
                                        <form method="post" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>"><?php echo '<input type="hidden" name="action" value="placement_reject"><input type="hidden" name="csrf_token" value="' . app_escape(csrf_token()) . '"><input type="hidden" name="placement_id" value="' . (int) $placement['id'] . '"><button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>'; ?></form>
                                    <?php elseif ($status === 'approved'): ?>
                                        <form method="post" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>"><?php echo '<input type="hidden" name="action" value="placement_activate"><input type="hidden" name="csrf_token" value="' . app_escape(csrf_token()) . '"><input type="hidden" name="placement_id" value="' . (int) $placement['id'] . '"><button class="btn btn-sm btn-primary" type="submit">Activate</button>'; ?></form>
                                        <form method="post" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>"><?php echo '<input type="hidden" name="action" value="placement_cancel"><input type="hidden" name="csrf_token" value="' . app_escape(csrf_token()) . '"><input type="hidden" name="placement_id" value="' . (int) $placement['id'] . '"><button class="btn btn-sm btn-outline-secondary" type="submit">Cancel</button>'; ?></form>
                                    <?php elseif ($status === 'active'): ?>
                                        <form method="post" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>"><?php echo '<input type="hidden" name="action" value="placement_complete"><input type="hidden" name="csrf_token" value="' . app_escape(csrf_token()) . '"><input type="hidden" name="placement_id" value="' . (int) $placement['id'] . '"><button class="btn btn-sm btn-success" type="submit">Complete</button>'; ?></form>
                                        <form method="post" action="<?= app_escape(app_route_url('coordinator/placements', $appBase)) ?>"><?php echo '<input type="hidden" name="action" value="placement_cancel"><input type="hidden" name="csrf_token" value="' . app_escape(csrf_token()) . '"><input type="hidden" name="placement_id" value="' . (int) $placement['id'] . '"><button class="btn btn-sm btn-outline-secondary" type="submit">Cancel</button>'; ?></form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <?php app_render_component('empty-state', ['title' => 'No placement records', 'message' => 'Assignments matching the filters will appear here.']); ?>
        <?php endif; ?>
    </section>

    <section class="panel-card" aria-labelledby="placement-history-heading">
        <div class="panel-card-header"><div><h2 class="h5 mb-1" id="placement-history-heading">Placement history</h2><p class="text-muted mb-0">Status changes and responsible actors.</p></div></div>
        <?php $history = $placementPageData['history'] ?? []; ?>
        <?php if ($history !== []): ?>
            <div class="table-responsive"><table class="table data-table align-middle"><thead><tr><th>Student</th><th>Placement</th><th>Change</th><th>Actor</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($history as $entry): ?><tr><td><?= app_escape($entry['student_name']) ?></td><td><?= app_escape($entry['company_name'] . ' / ' . $entry['position_title']) ?></td><td><?= app_escape(($entry['previous_status'] ?: '—') . ' → ' . $entry['new_status']) ?></td><td><?= app_escape($entry['actor_name']) ?></td><td><?= app_escape($entry['created_at']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php else: ?><?php app_render_component('empty-state', ['title' => 'No placement history', 'message' => 'Approved, rejected, activated, completed, or cancelled placements will appear here.']); ?><?php endif; ?>
    </section>
</div>
