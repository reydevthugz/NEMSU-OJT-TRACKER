<?php

declare(strict_types=1);

?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/head.php'; ?>
</head>
<body class="home-body">
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <?php app_render_component('public-header', ['appBase' => $appBase]); ?>
    <?php require $viewPath; ?>
    <?php app_render_component('footer', ['variant' => 'public']); ?>
</body>
</html>
