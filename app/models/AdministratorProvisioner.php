<?php

declare(strict_types=1);

final class AdministratorProvisioner
{
    private const LOCK_NAME = 'nemsu_ojt_tracker:first_admin_provisioning';

    public function __construct(private PDO $db)
    {
    }

    public function createFirstAdministrator(string $name, string $email, string $password): int
    {
        $name = trim($name);
        $email = strtolower(trim($email));
        $this->validate($name, $email, $password);

        $lockAcquired = false;
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $lockStatement = $this->db->prepare('SELECT GET_LOCK(:lock_name, :timeout)');
            $lockStatement->bindValue(':lock_name', self::LOCK_NAME);
            $lockStatement->bindValue(':timeout', 10, PDO::PARAM_INT);
            $lockStatement->execute();
            if ((int) $lockStatement->fetchColumn() !== 1) {
                throw new RuntimeException('Could not obtain the first-administrator provisioning lock.');
            }
            $lockAcquired = true;
        }

        $ownsTransaction = !$this->db->inTransaction();
        $savepoint = 'first_admin_provisioning';

        try {
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            } else {
                $this->db->exec('SAVEPOINT ' . $savepoint);
            }

            $emailStatement = $this->db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
            $emailStatement->execute(['email' => $email]);
            if ($emailStatement->fetchColumn() !== false) {
                throw new DomainException('Provisioning refused: an account already uses this email address.');
            }

            $administratorStatement = $this->db->prepare(
                'SELECT 1 FROM users WHERE role = :role LIMIT 1'
            );
            $administratorStatement->execute(['role' => 'admin']);
            if ($administratorStatement->fetchColumn() !== false) {
                throw new DomainException('Provisioning refused: an administrator account already exists.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new RuntimeException('Password hashing failed.');
            }

            $insertStatement = $this->db->prepare(
                'INSERT INTO users (name, email, password, role, status)
                 VALUES (:name, :email, :password, :role, :status)'
            );
            $insertStatement->execute([
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
                'role' => 'admin',
                'status' => 'active',
            ]);

            if ($ownsTransaction) {
                $this->db->commit();
            } else {
                $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
            }

            return (int) $this->db->lastInsertId();
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            } elseif ($this->db->inTransaction()) {
                $this->db->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
            }

            if ($exception instanceof PDOException && $this->isUniqueConstraintViolation($exception)) {
                throw new DomainException(
                    'Provisioning refused: an account already uses this email address.',
                    0,
                    $exception
                );
            }

            throw $exception;
        } finally {
            if ($lockAcquired) {
                try {
                    $releaseStatement = $this->db->prepare('SELECT RELEASE_LOCK(:lock_name)');
                    $releaseStatement->execute(['lock_name' => self::LOCK_NAME]);
                } catch (Throwable) {
                    error_log('First-administrator database lock release failed.');
                }
            }
        }
    }

    private function validate(string $name, string $email, string $password): void
    {
        if ($name === '' || $this->textLength($name) > 120) {
            throw new InvalidArgumentException('Enter an administrator name of 1 to 120 characters.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $this->textLength($email) > 190) {
            throw new InvalidArgumentException('Enter a valid administrator email address.');
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('Password must be between 8 and 72 bytes.');
        }
    }

    private function textLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        $length = preg_match_all('/./us', $value);
        if ($length === false) {
            throw new InvalidArgumentException('Name and email must contain valid UTF-8 text.');
        }

        return $length;
    }

    private function isUniqueConstraintViolation(PDOException $exception): bool
    {
        return ($exception->errorInfo[1] ?? null) === 1062
            || (string) $exception->getCode() === '23000';
    }
}
