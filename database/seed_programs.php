<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

const OFFICIAL_PROGRAM_CODE = 'BSCS';
const OFFICIAL_PROGRAM_NAME = 'Bachelor of Science in Computer Science';

function inspect_program_schema(PDO $db): array
{
    $columnStatement = $db->prepare(
        'SELECT column_name, column_type, is_nullable, extra, character_maximum_length
         FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name
         ORDER BY ordinal_position'
    );
    $columnStatement->execute(['table_name' => 'programs']);
    $columns = $columnStatement->fetchAll();

    $requiredColumns = ['id', 'code', 'name', 'created_at', 'updated_at'];
    $columnMap = [];
    foreach ($columns as $column) {
        $columnMap[(string) $column['column_name']] = $column;
    }

    foreach ($requiredColumns as $columnName) {
        if (!isset($columnMap[$columnName])) {
            throw new RuntimeException('The programs table is missing a required column.');
        }
    }
    if (
        strtolower((string) $columnMap['id']['is_nullable']) !== 'no'
        || strtolower((string) $columnMap['code']['is_nullable']) !== 'no'
        || strtolower((string) $columnMap['name']['is_nullable']) !== 'no'
        || strpos(strtolower((string) $columnMap['code']['column_type']), 'varchar') !== 0
        || strpos(strtolower((string) $columnMap['name']['column_type']), 'varchar') !== 0
        || (int) $columnMap['code']['character_maximum_length'] < strlen(OFFICIAL_PROGRAM_CODE)
        || (int) $columnMap['name']['character_maximum_length'] < strlen(OFFICIAL_PROGRAM_NAME)
    ) {
        throw new RuntimeException('The programs table columns cannot store the official program.');
    }

    $indexStatement = $db->prepare(
        'SELECT index_name, column_name, non_unique, seq_in_index
         FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = :table_name
         ORDER BY index_name, seq_in_index'
    );
    $indexStatement->execute(['table_name' => 'programs']);
    $indexes = [];
    foreach ($indexStatement->fetchAll() as $index) {
        $indexName = (string) $index['index_name'];
        $indexes[$indexName]['non_unique'] = (int) $index['non_unique'];
        $indexes[$indexName]['columns'][] = (string) $index['column_name'];
    }

    $uniqueColumnSets = [];
    foreach ($indexes as $indexName => $index) {
        if ($index['non_unique'] === 0) {
            $uniqueColumnSets[] = array_values($index['columns']);
        }
    }

    if (
        !in_array(['id'], $uniqueColumnSets, true)
        || !in_array(['code'], $uniqueColumnSets, true)
        || !in_array(['name'], $uniqueColumnSets, true)
    ) {
        throw new RuntimeException('The programs table is missing expected unique indexes.');
    }

    return ['columns' => $columnMap, 'indexes' => $indexes];
}

function find_official_program(PDO $db, bool $lockRows): array
{
    $sql = 'SELECT id, code, name FROM programs WHERE code = :code OR name = :name';
    if ($lockRows) {
        $sql .= ' FOR UPDATE';
    }

    $statement = $db->prepare($sql);
    $statement->execute([
        'code' => OFFICIAL_PROGRAM_CODE,
        'name' => OFFICIAL_PROGRAM_NAME,
    ]);

    return $statement->fetchAll();
}

function is_official_program(array $rows): bool
{
    return count($rows) === 1
        && strcasecmp((string) $rows[0]['code'], OFFICIAL_PROGRAM_CODE) === 0
        && strcasecmp((string) $rows[0]['name'], OFFICIAL_PROGRAM_NAME) === 0;
}

try {
    $db = database_connection();
    $schema = inspect_program_schema($db);

    fwrite(STDOUT, "Programs table columns:\n");
    foreach ($schema['columns'] as $name => $column) {
        printf(
            "  %s %s, nullable=%s%s\n",
            $name,
            $column['column_type'],
            strtolower((string) $column['is_nullable']),
            $column['extra'] !== '' ? ', ' . $column['extra'] : ''
        );
    }

    fwrite(STDOUT, "Unique indexes:\n");
    foreach ($schema['indexes'] as $name => $index) {
        if ($index['non_unique'] === 0) {
            printf("  %s (%s)\n", $name, implode(', ', $index['columns']));
        }
    }

    $db->beginTransaction();
    $existingRows = find_official_program($db, true);
    if ($existingRows !== []) {
        $db->commit();
        if (is_official_program($existingRows)) {
            printf(
                "BSCS already exists (id %d); no program record was changed.\n",
                (int) $existingRows[0]['id']
            );
            exit(0);
        }

        fwrite(
            STDERR,
            "Seeding stopped: an existing program conflicts with the BSCS code or official name; no record was changed.\n"
        );
        exit(1);
    }

    $insertStatement = $db->prepare('INSERT INTO programs (code, name) VALUES (:code, :name)');
    $insertStatement->execute([
        'code' => OFFICIAL_PROGRAM_CODE,
        'name' => OFFICIAL_PROGRAM_NAME,
    ]);
    $programId = (int) $db->lastInsertId();
    $db->commit();

    printf(
        "Inserted BSCS - %s (id %d).\n",
        OFFICIAL_PROGRAM_NAME,
        $programId
    );
} catch (PDOException $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    if (($exception->errorInfo[1] ?? null) === 1062 && isset($db) && $db instanceof PDO) {
        try {
            $existingRows = find_official_program($db, false);
            if (is_official_program($existingRows)) {
                printf(
                    "BSCS already exists (id %d); no program record was changed.\n",
                    (int) $existingRows[0]['id']
                );
                exit(0);
            }
        } catch (Throwable) {
            error_log('Academic program duplicate check failed.');
            fwrite(STDERR, "Program seeding failed safely; no conflicting record was overwritten.\n");
            exit(1);
        }
    }

    error_log('Academic program seeding failed.');
    fwrite(STDERR, "Program seeding failed safely; no conflicting record was overwritten.\n");
    exit(1);
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Academic program seeding failed.');
    fwrite(STDERR, 'Program seeding stopped: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
