<?php

declare(strict_types=1);

function app_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_route_url(string $page, string $basePath): string
{
    $encodedPage = str_ireplace('%2F', '/', rawurlencode($page));

    return rtrim($basePath, '/') . '/?page=' . $encodedPage;
}

function app_render_component(string $component, array $data = []): void
{
    $componentFiles = [
        'sidebar' => dirname(__DIR__) . '/views/components/sidebar.php',
        'topbar' => dirname(__DIR__) . '/views/components/topbar.php',
        'footer' => dirname(__DIR__) . '/views/components/footer.php',
        'public-header' => dirname(__DIR__) . '/views/components/public-header.php',
        'page-header' => dirname(__DIR__) . '/views/components/page-header.php',
        'stat-card' => dirname(__DIR__) . '/views/components/stat-card.php',
        'status-badge' => dirname(__DIR__) . '/views/components/status-badge.php',
        'data-table' => dirname(__DIR__) . '/views/components/data-table.php',
        'empty-state' => dirname(__DIR__) . '/views/components/empty-state.php',
        'notification-list' => dirname(__DIR__) . '/views/components/notification-list.php',
    ];

    if (!isset($componentFiles[$component])) {
        throw new InvalidArgumentException('Unknown view component.');
    }

    extract($data, EXTR_SKIP);
    require $componentFiles[$component];
}
