<?php

declare(strict_types=1);

$notifications = $notifications ?? [];
?>
<?php if ($notifications !== []): ?>
    <ul class="notification-list">
        <?php foreach ($notifications as $notification): ?>
            <li class="notification-item">
                <div class="notification-meta">
                    <strong><?= app_escape($notification['title']) ?></strong>
                    <time datetime="<?= app_escape($notification['created_at']) ?>"><?= app_escape($notification['created_at']) ?></time>
                </div>
                <p><?= app_escape($notification['message']) ?></p>
                <?php if ($notification['read_at'] === null): ?>
                    <span class="status-badge status-warning">Unread</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <?php app_render_component('empty-state', [
        'icon' => 'bi-bell',
        'title' => 'No recent notifications',
        'message' => $emptyMessage ?? 'Notifications addressed to this account will appear here.',
    ]); ?>
<?php endif; ?>
