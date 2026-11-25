<?php

declare(strict_types=1);

$adminSuccess = take_flash('success');
$adminError = take_flash('error');
?>
<?php if (is_string($adminSuccess) && $adminSuccess !== ''): ?>
    <div class="alert alert-success" role="status"><?= app_escape($adminSuccess) ?></div>
<?php endif; ?>
<?php if (is_string($adminError) && $adminError !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= app_escape($adminError) ?></div>
<?php elseif (is_array($adminError) && $adminError !== []): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($adminError as $message): ?>
                <li><?= app_escape($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
