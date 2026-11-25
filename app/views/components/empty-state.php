<?php

declare(strict_types=1);

?>
<div class="empty-state">
    <span class="empty-state-icon" aria-hidden="true"><i class="bi <?= app_escape($icon ?? 'bi-inbox') ?>"></i></span>
    <h3><?= app_escape($title ?? 'Nothing to show yet') ?></h3>
    <p><?= app_escape($message ?? 'This area is a visual placeholder for a later phase.') ?></p>
</div>
