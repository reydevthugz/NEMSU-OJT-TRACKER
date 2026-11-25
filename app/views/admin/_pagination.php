<?php

declare(strict_types=1);

$paginationQuery = array_filter($filters ?? [], static fn (mixed $value): bool => $value !== '' && $value !== null && $value !== 0);
if (array_key_exists('search', $paginationQuery)) {
    $paginationQuery['q'] = $paginationQuery['search'];
    unset($paginationQuery['search']);
}
$paginationStart = max(1, (int) $listing['page'] - 2);
$paginationEnd = min((int) $listing['pages'], (int) $listing['page'] + 2);
$paginationUrl = static function (int $number) use ($paginationQuery, $pageKey, $appBase): string {
    $query = $paginationQuery;
    $query['p'] = $number;

    return app_route_url($pageKey, $appBase) . '&' . http_build_query($query);
};
?>
<?php if (($listing['pages'] ?? 1) > 1): ?>
    <nav aria-label="Pagination">
        <ul class="pagination justify-content-end mb-0">
            <li class="page-item<?= (int) $listing['page'] <= 1 ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= app_escape($paginationUrl(max(1, (int) $listing['page'] - 1))) ?>" aria-label="Previous page">Previous</a>
            </li>
            <?php if ($paginationStart > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= app_escape($paginationUrl(1)) ?>">1</a></li>
                <?php if ($paginationStart > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endif; ?>
            <?php foreach (range($paginationStart, $paginationEnd) as $number): ?>
                <li class="page-item<?= (int) $listing['page'] === $number ? ' active' : '' ?>">
                    <a class="page-link" href="<?= app_escape($paginationUrl($number)) ?>"<?= (int) $listing['page'] === $number ? ' aria-current="page"' : '' ?>>
                        <?= $number ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if ($paginationEnd < (int) $listing['pages']): ?>
                <?php if ($paginationEnd < (int) $listing['pages'] - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= app_escape($paginationUrl((int) $listing['pages'])) ?>"><?= (int) $listing['pages'] ?></a></li>
            <?php endif; ?>
            <li class="page-item<?= (int) $listing['page'] >= (int) $listing['pages'] ? ' disabled' : '' ?>">
                <a class="page-link" href="<?= app_escape($paginationUrl(min((int) $listing['pages'], (int) $listing['page'] + 1))) ?>" aria-label="Next page">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
