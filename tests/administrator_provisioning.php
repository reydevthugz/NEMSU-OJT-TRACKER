<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/models/AdministratorProvisioner.php';
require_once dirname(__DIR__) . '/app/models/User.php';

$checks = 0;

function provisioning_check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }

    $checks++;
}

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec(
    "CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role TEXT NOT NULL CHECK (role IN ('student', 'adviser', 'coordinator', 'supervisor', 'admin')),
        status TEXT NOT NULL CHECK (status IN ('active', 'inactive', 'disabled')),
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

$provisioner = new AdministratorProvisioner($db);
$invalidEmailRejected = false;
try {
    $provisioner->createFirstAdministrator('Test Admin', 'not-an-email', 'safe-test-password');
} catch (InvalidArgumentException) {
    $invalidEmailRejected = true;
}
provisioning_check($invalidEmailRejected, 'Invalid email addresses must be rejected before insertion.');

$shortPasswordRejected = false;
try {
    $provisioner->createFirstAdministrator('Test Admin', 'admin@example.test', 'short');
} catch (InvalidArgumentException) {
    $shortPasswordRejected = true;
}
provisioning_check($shortPasswordRejected, 'Passwords shorter than the existing authentication policy must be rejected.');
provisioning_check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0, 'Invalid inputs must not create accounts.');

$db->beginTransaction();
$rolledBackAdminId = $provisioner->createFirstAdministrator(
    'Transaction Test Admin',
    'transaction-admin@example.test',
    'valid-test-password'
);
$duringTransaction = $db->prepare('SELECT role, status, password FROM users WHERE id = :id');
$duringTransaction->execute(['id' => $rolledBackAdminId]);
$transactionAdmin = $duringTransaction->fetch();
provisioning_check($db->inTransaction(), 'Provisioning must preserve an outer transaction for rollback-safe tests.');
provisioning_check($transactionAdmin['role'] === 'admin', 'The provisioned account must receive the admin role.');
provisioning_check($transactionAdmin['status'] === 'active', 'The provisioned account must be active.');
provisioning_check(
    $transactionAdmin['password'] !== 'valid-test-password'
        && password_verify('valid-test-password', $transactionAdmin['password']),
    'The administrator password must be stored as a password_hash() value.'
);
$db->rollBack();
provisioning_check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0, 'The outer rollback must remove the test account.');

$adminId = $provisioner->createFirstAdministrator(
    'First Administrator',
    'FIRST.ADMIN@example.test',
    'another-valid-password'
);
$authenticatedAdmin = (new User($db))->authenticate('first.admin@example.test', 'another-valid-password');
provisioning_check($adminId > 0, 'The first administrator should be inserted.');
provisioning_check(
    $authenticatedAdmin !== null && $authenticatedAdmin['role'] === 'admin',
    'The provisioned account should authenticate with the admin role.'
);

$originalAccountStatement = $db->prepare('SELECT name, email, password, role, status FROM users WHERE id = :id');
$originalAccountStatement->execute(['id' => $adminId]);
$originalAccount = $originalAccountStatement->fetch();
$duplicateEmailRejected = false;
try {
    $provisioner->createFirstAdministrator(
        'Changed Name',
        'first.admin@example.test',
        'replacement-password'
    );
} catch (DomainException) {
    $duplicateEmailRejected = true;
}
$originalAccountStatement->execute(['id' => $adminId]);
provisioning_check($duplicateEmailRejected, 'Repeated provisioning with the same email must be refused.');
provisioning_check($originalAccountStatement->fetch() === $originalAccount, 'A duplicate attempt must not change the existing account.');

$secondAdminRejected = false;
try {
    $provisioner->createFirstAdministrator(
        'Second Administrator',
        'second.admin@example.test',
        'another-valid-password'
    );
} catch (DomainException) {
    $secondAdminRejected = true;
}
provisioning_check($secondAdminRejected, 'Provisioning must refuse to create another administrator once one exists.');
provisioning_check((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1, 'Refused attempts must not create additional accounts.');

$db->exec(
    "INSERT INTO users (name, email, password, role, status)
     VALUES ('Existing Student', 'student@example.test', 'existing-hash', 'student', 'active')"
);
$existingStudentId = (int) $db->lastInsertId();
$existingAccountStatement = $db->prepare('SELECT name, password, role, status FROM users WHERE id = :id');
$existingAccountStatement->execute(['id' => $existingStudentId]);
$existingStudent = $existingAccountStatement->fetch();
$existingEmailRejected = false;
try {
    $provisioner->createFirstAdministrator(
        'Existing Student',
        'student@example.test',
        'replacement-password'
    );
} catch (DomainException) {
    $existingEmailRejected = true;
}
$existingAccountStatement->execute(['id' => $existingStudentId]);
provisioning_check($existingEmailRejected, 'An existing non-admin account email must prevent provisioning.');
provisioning_check($existingAccountStatement->fetch() === $existingStudent, 'Existing non-admin account details must remain unchanged.');

echo 'Administrator provisioning checks passed: ' . $checks . PHP_EOL;
