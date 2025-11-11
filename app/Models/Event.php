<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class Event 
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, image_url, judul, description FROM event ORDER BY judul');

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, image_url, judul, description FROM event WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(string $judul, ?string $description = null, ?string $imageUrl = null): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO event (image_url, judul, description) 
             VALUES (:image_url, :judul, :description) 
             RETURNING id, image_url, judul, description'
        );
        $stmt->execute([
            'image_url' => $imageUrl,
            'judul' => $judul,
            'description' => $description,
        ]);

        return $stmt->fetch();
    }

    public function update(int $id, string $judul, ?string $description = null, ?string $imageUrl = null): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE event 
             SET image_url = :image_url, judul = :judul, description = :description 
             WHERE id = :id 
             RETURNING id, image_url, judul, description'
        );
        $stmt->execute([
            'id' => $id,
            'image_url' => $imageUrl,
            'judul' => $judul,
            'description' => $description,
        ]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM event WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function judulExists(string $judul, ?int $ignoreId = null): bool
    {
        $query = 'SELECT 1 FROM event WHERE LOWER(judul) = LOWER(:judul)';
        $params = ['judul' => $judul];

        if ($ignoreId !== null) {
            $query .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function search(string $keyword): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, image_url, judul, description 
             FROM event 
             WHERE LOWER(judul) LIKE LOWER(:keyword) 
                OR LOWER(description) LIKE LOWER(:keyword)
             ORDER BY judul'
        );
        $stmt->execute(['keyword' => "%{$keyword}%"]);

        return $stmt->fetchAll();
    }

    public function recent(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, image_url, judul, description 
             FROM event 
             ORDER BY id DESC 
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}