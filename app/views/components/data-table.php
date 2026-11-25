<?php

declare(strict_types=1);

?>
<div class="table-responsive data-table-wrap">
    <table class="table data-table align-middle mb-0">
        <caption class="visually-hidden"><?= app_escape($caption ?? 'Illustrative records') ?></caption>
        <thead>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <th scope="col"><?= app_escape($column['label']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <?php $value = $row[$column['key']] ?? ''; ?>
                        <td>
                            <?php if (($column['type'] ?? '') === 'status'): ?>
                                <?php app_render_component('status-badge', ['status' => $value]); ?>
                            <?php else: ?>
                                <?= app_escape($value) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
