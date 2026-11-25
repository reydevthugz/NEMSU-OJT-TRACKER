<?php

declare(strict_types=1);

$icon = $stat['icon'] ?? 'bi-graph-up';
$tone = $stat['tone'] ?? 'blue';
?>
<article class="stat-card stat-card-<?= app_escape($tone) ?>">
    <div class="stat-card-top">
        <span class="stat-label"><?= app_escape($stat['label'] ?? '') ?></span>
        <span class="stat-icon" aria-hidden="true"><i class="bi <?= app_escape($icon) ?>"></i></span>
    </div>
    <p class="stat-value"><?= app_escape($stat['value'] ?? '—') ?></p>
    <p class="stat-caption"><?= app_escape($stat['caption'] ?? 'No operational data displayed') ?></p>
</article>
