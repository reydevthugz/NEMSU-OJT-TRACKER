<?php

declare(strict_types=1);

final class User
{
    public function __construct(private PDO $db)
    {
    }

    private function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (
            $user === null
            || (string) $user['status'] !== 'active'
            || !password_verify($password, (string) $user['password'])
            || !in_array((string) $user['role'], ['student', 'adviser', 'coordinator', 'supervisor', 'admin'], true)
        ) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        return $statement->fetchColumn() !== false;
    }

    public function studentNumberExists(string $studentNumber): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM student_profiles WHERE student_number = :student_number LIMIT 1'
        );
        $statement->execute(['student_number' => $studentNumber]);

        return $statement->fetchColumn() !== false;
    }

    public function createStudent(array $data): int
    {
        $ownsTransaction = !$this->db->inTransaction();
        $savepoint = 'student_registration';
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        } else {
            $this->db->exec('SAVEPOINT ' . $savepoint);
        }

        try {
            $userStatement = $this->db->prepare(
                'INSERT INTO users (name, email, password, role, status)
                 VALUES (:name, :email, :password, :role, :status)'
            );
            $userStatement->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => 'student',
                'status' => 'active',
            ]);
            $userId = (int) $this->db->lastInsertId();

            $profileStatement = $this->db->prepare(
                'INSERT INTO student_profiles
                    (user_id, program_id, student_number, year_level, preferred_location)
                 VALUES
                    (:user_id, :program_id, :student_number, :year_level, :preferred_location)'
            );
            $profileStatement->execute([
                'user_id' => $userId,
                'program_id' => $data['program_id'],
                'student_number' => $data['student_number'],
                'year_level' => $data['year_level'],
                'preferred_location' => $data['preferred_location'] ?: null,
            ]);

            if ($ownsTransaction) {
                $this->db->commit();
            } else {
                $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
            }

            return $userId;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            } elseif ($this->db->inTransaction()) {
                $this->db->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
            }

            throw $exception;
        }
    }
}
