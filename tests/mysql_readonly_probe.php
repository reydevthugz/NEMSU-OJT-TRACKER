<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $db = database_connection();
    $databaseState = $db->query('SELECT DATABASE() AS database_name, VERSION() AS server_version')->fetch();
    $tableStatement = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_type = ?'
    );
    $tableStatement->execute(['BASE TABLE']);
    $tableCount = (int) $tableStatement->fetchColumn();
    $programCount = (int) $db->query('SELECT COUNT(*) FROM programs')->fetchColumn();

    printf(
        "PDO connection: OK; database: %s; MySQL: %s; tables: %d; programs: %d\n",
        $databaseState['database_name'],
        $databaseState['server_version'],
        $tableCount,
        $programCount
    );
} catch (Throwable $exception) {
    fwrite(STDERR, 'Read-only database probe failed (' . get_class($exception) . "). Check local server/configuration.\n");
    exit(1);
}
