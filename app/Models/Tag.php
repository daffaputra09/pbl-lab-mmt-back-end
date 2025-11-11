<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class Tag
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, name FROM tag ORDER BY name');

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name FROM tag WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(string $name): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tag (name) VALUES (:name) RETURNING id, name'
        );
        $stmt->execute([
            'name' => $name,
        ]);

        return $stmt->fetch();
    }

    public function update(int $id, string $name): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE tag SET name = :name WHERE id = :id RETURNING id, name'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
        ]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tag WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function nameExists(string $name, ?int $ignoreId = null): bool
    {
        $query = 'SELECT 1 FROM tag WHERE LOWER(name) = LOWER(:name)';
        $params = ['name' => $name];

        if ($ignoreId !== null) {
            $query .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }
}

