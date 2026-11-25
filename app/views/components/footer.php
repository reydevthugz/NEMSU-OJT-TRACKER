<?php

declare(strict_types=1);

$variant = $variant ?? 'app';
?>
<footer class="app-footer">
    <?php if ($variant === 'public'): ?>
        <span>NEMSU OJT TRACKER <span class="footer-divider">·</span> North Eastern Mindanao State University</span>
        <span>On-the-Job Training Placement and Monitoring System</span>
    <?php else: ?>
        <span>NEMSU OJT TRACKER <span class="footer-divider">·</span> Role-protected workspace</span>
        <span>On-the-Job Training Placement and Monitoring System</span>
    <?php endif; ?>
</footer>
