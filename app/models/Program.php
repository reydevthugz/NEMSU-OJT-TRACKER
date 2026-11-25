<?php

declare(strict_types=1);

final class Program
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $statement = $this->db->query(
            'SELECT id, code, name FROM programs ORDER BY name ASC'
        );

        return $statement->fetchAll();
    }

    public function exists(int $programId): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM programs WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $programId]);

        return $statement->fetchColumn() !== false;
    }
}
