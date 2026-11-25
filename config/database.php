<?php

declare(strict_types=1);

function database_connection(): PDO
{
    $host = environment_value('DB_HOST');
    $port = environment_value('DB_PORT');
    $database = environment_value('DB_DATABASE');
    $username = environment_value('DB_USERNAME');
    $password = environment_value('DB_PASSWORD');

    if ($host === null || $port === null || $database === null || $username === null || $password === null) {
        throw new RuntimeException('Database configuration is incomplete. Check the local environment configuration.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $database
    );

    try {
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('Database connection failed.');
        throw new RuntimeException(
            'Database connection is unavailable. Check the local server and environment configuration.',
            0,
            $exception
        );
    }
}
