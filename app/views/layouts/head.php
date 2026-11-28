<?php

declare(strict_types=1);

$metaDescription = $metaDescription ?? $pageSubtitle ?? 'NEMSU OJT TRACKER';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= app_escape($metaDescription) ?>">
<title><?= app_escape($pageTitle) ?> | NEMSU OJT TRACKER</title>
<link rel="icon" href="<?= app_escape($assetBase) ?>/assets/images/favicon.svg?v=3" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= app_escape($assetBase) ?>/assets/css/app.css?v=10" rel="stylesheet">
<link href="<?= app_escape($assetBase) ?>/assets/css/components.css?v=10" rel="stylesheet">
<link href="<?= app_escape($assetBase) ?>/assets/css/responsive.css?v=10" rel="stylesheet">
