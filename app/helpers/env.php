<?php

declare(strict_types=1);

function load_environment(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    if (!is_readable($filePath)) {
        throw new RuntimeException('The environment configuration file is not readable.');
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('The environment configuration file could not be read.');
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
            continue;
        }

        $name = $matches[1];
        if (getenv($name) !== false) {
            continue;
        }

        $value = trim($matches[2]);
        if (
            strlen($value) >= 2
            && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
                || ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function environment_value(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }

    return $default;
}
