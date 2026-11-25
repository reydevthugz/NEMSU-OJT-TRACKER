<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (($argv[1] ?? '') !== '--payload-base64-stdin' || count($argv) !== 2) {
    fwrite(
        STDERR,
        "Run database\\provision-admin.ps1 in PowerShell to securely enter the first administrator credentials.\n"
    );
    exit(2);
}

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/models/AdministratorProvisioner.php';

try {
    $encodedPayload = stream_get_contents(STDIN, 8193);
    if ($encodedPayload === false || strlen($encodedPayload) > 8192) {
        throw new InvalidArgumentException('Provisioning input is invalid or too large.');
    }
    $decodedPayload = base64_decode(trim($encodedPayload), true);
    if ($decodedPayload === false) {
        throw new InvalidArgumentException('Provisioning input is invalid.');
    }
    $payload = json_decode($decodedPayload, true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) {
        throw new InvalidArgumentException('Provisioning input is invalid.');
    }

    $name = $payload['name'] ?? null;
    $email = $payload['email'] ?? null;
    $password = $payload['password'] ?? null;
    unset($payload, $decodedPayload, $encodedPayload);
    if (!is_string($name) || !is_string($email) || !is_string($password)) {
        throw new InvalidArgumentException('Name, email, and password are required.');
    }

    $userId = (new AdministratorProvisioner(database_connection()))
        ->createFirstAdministrator($name, $email, $password);
    unset($password);

    printf("First administrator created successfully (user ID %d). You can now sign in.\n", $userId);
} catch (InvalidArgumentException | DomainException | JsonException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
} catch (Throwable) {
    error_log('First-administrator provisioning failed.');
    fwrite(STDERR, "Provisioning failed safely. Check the local database service and configuration, then retry.\n");
    exit(1);
}
