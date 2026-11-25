<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This migration command is available from the CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

$username = environment_value('DB_USERNAME');
$password = environment_value('DB_PASSWORD');
$database = environment_value('DB_DATABASE');

if (
    $username === null
    || $password === null
    || $username === ''
    || $username === 'your_mysql_username'
    || $password === 'your_mysql_password'
    || $database !== 'nemsu_ojt_tracker'
) {
    fwrite(STDERR, "Migration stopped: configure real local database credentials in .env first.\n");
    exit(1);
}

$stage = 'PDO connection';

try {
    $db = database_connection();
    $stage = 'migration tracking table setup';
    $db->exec(
        'CREATE TABLE IF NOT EXISTS migrations (
            migration VARCHAR(190) NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $stage = 'migration discovery';
    $migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql');
    if ($migrationFiles === false) {
        throw new RuntimeException('Migration files could not be discovered.');
    }
    sort($migrationFiles, SORT_STRING);

    if ($migrationFiles === []) {
        fwrite(STDOUT, "No SQL migrations were found.\n");
        exit(0);
    }

    $appliedCount = 0;

    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);
        $stage = 'migration history lookup';
        $statement = $db->prepare('SELECT 1 FROM migrations WHERE migration = :migration LIMIT 1');
        $statement->execute(['migration' => $migrationName]);

        if ($statement->fetchColumn() !== false) {
            fwrite(STDOUT, "Skipped: {$migrationName} (already applied)\n");
            continue;
        }

        $stage = 'migration file read';
        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException('Migration file could not be read.');
        }

        $statements = array_filter(
            array_map(
                static fn(string $statement): string => trim($statement),
                preg_split('/;\s*(?:\R|$)/', $sql) ?: []
            ),
            static fn(string $statement): bool => $statement !== ''
        );

        $stage = 'migration SQL execution';
        foreach ($statements as $sqlStatement) {
            $db->exec($sqlStatement);
        }

        $stage = 'migration history update';
        $recordStatement = $db->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
        $recordStatement->execute(['migration' => $migrationName]);
        ++$appliedCount;

        fwrite(STDOUT, "Applied: {$migrationName}\n");
    }

    fwrite(STDOUT, "Migration run complete. Applied {$appliedCount} migration(s).\n");
} catch (Throwable $exception) {
    $cause = $exception;
    while ($cause->getPrevious() !== null) {
        $cause = $cause->getPrevious();
    }

    $diagnostic = 'Migration failed during ' . $stage . '.';
    if ($cause instanceof PDOException) {
        $sqlState = (string) $cause->getCode();
        $driverCode = $cause->errorInfo[1] ?? null;

        if (preg_match('/^[0-9A-Z]{5}$/', $sqlState) === 1) {
            $diagnostic .= ' SQLSTATE ' . $sqlState . '.';
        }
        if (is_int($driverCode) || (is_string($driverCode) && ctype_digit($driverCode))) {
            $diagnostic .= ' Driver code ' . $driverCode . '.';
        }
    }

    fwrite(STDERR, $diagnostic . "\n");
    error_log($diagnostic);
    exit(1);
}
