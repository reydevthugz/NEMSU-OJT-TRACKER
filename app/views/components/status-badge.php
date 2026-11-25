<?php

declare(strict_types=1);

$statusText = (string) ($status ?? 'Not started');
$statusKey = strtolower($statusText);
$tone = match (true) {
    str_contains($statusKey, 'approved'),
    str_contains($statusKey, 'active'),
    str_contains($statusKey, 'complete'),
    str_contains($statusKey, 'verified') => 'success',
    str_contains($statusKey, 'pending'),
    str_contains($statusKey, 'review'),
    str_contains($statusKey, 'progress'),
    str_contains($statusKey, 'awaiting') => 'warning',
    str_contains($statusKey, 'rejected'),
    str_contains($statusKey, 'overdue'),
    str_contains($statusKey, 'inactive') => 'danger',
    default => 'neutral',
};
?>
<span class="status-badge status-<?= $tone ?>"><?= app_escape($statusText) ?></span>
