<?php

declare(strict_types=1);

$statusText = (string) ($status ?? 'Not started');
$statusKey = strtolower(trim($statusText));
$tone = match ($statusKey) {
    'approved', 'active', 'completed', 'verified', 'open', 'present' => 'success',
    'pending', 'submitted', 'in progress', 'under review', 'awaiting review' => 'warning',
    'rejected', 'overdue', 'inactive', 'disabled', 'cancelled', 'canceled', 'closed', 'withdrawn', 'absent' => 'danger',
    default => 'neutral',
};
?>
<span class="status-badge status-<?= $tone ?>"><?= app_escape($statusText) ?></span>
