<?php

declare(strict_types=1);

?>
<div class="page-heading">
    <nav class="breadcrumb-line" aria-label="Breadcrumb">
        <a href="<?= app_escape(app_route_url('home', $appBase)) ?>">Home</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page"><?= app_escape($title) ?></span>
    </nav>
    <div class="page-heading-main">
        <div>
            <h1><?= app_escape($title) ?></h1>
            <p><?= app_escape($subtitle) ?></p>
        </div>
        <span class="prototype-label"><i class="bi bi-shield-lock" aria-hidden="true"></i> Protected</span>
    </div>
</div>
