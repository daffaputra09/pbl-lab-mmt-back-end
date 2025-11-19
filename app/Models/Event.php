<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\FileUploadHelper;
use PDO;
use PDOException;

class Event 
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT id, image_url, judul, description, tanggal_event FROM event ORDER BY judul');

        $results = $stmt->fetchAll();
        return array_map([$this, 'transformImageUrl'], $results);
    }

    public function paginate(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare(
            'SELECT id, image_url, judul, description, tanggal_event 
             FROM event 
             ORDER BY judul
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformImageUrl'], $results);
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM event');
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, image_url, judul, description, tanggal_event FROM event WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformImageUrl($result);
    }

    public function create(string $judul, ?string $description = null, ?string $imageUrl = null, ?string $tanggalEvent = null): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO event (image_url, judul, description, tanggal_event) 
             VALUES (:image_url, :judul, :description, :tanggal_event) 
             RETURNING id, image_url, judul, description, tanggal_event'
        );
        $stmt->execute([
            'image_url' => $imageUrl,
            'judul' => $judul,
            'description' => $description,
            'tanggal_event' => $tanggalEvent,
        ]);

        $result = $stmt->fetch();
        return $this->transformImageUrl($result);
    }

    public function update(int $id, string $judul, ?string $description = null, ?string $imageUrl = null, ?string $tanggalEvent = null): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE event 
             SET image_url = :image_url, judul = :judul, description = :description, tanggal_event = :tanggal_event 
             WHERE id = :id 
             RETURNING id, image_url, judul, description, tanggal_event'
        );
        $stmt->execute([
            'id' => $id,
            'image_url' => $imageUrl,
            'judul' => $judul,
            'description' => $description,
            'tanggal_event' => $tanggalEvent,
        ]);

        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformImageUrl($result);
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
            'SELECT id, image_url, judul, description, tanggal_event 
             FROM event 
             WHERE LOWER(judul) LIKE LOWER(:keyword) 
                OR LOWER(description) LIKE LOWER(:keyword)
             ORDER BY judul'
        );
        $stmt->execute(['keyword' => "%{$keyword}%"]);

        $results = $stmt->fetchAll();
        return array_map([$this, 'transformImageUrl'], $results);
    }

    public function recent(?int $limit = null): array
    {
        $query = 'SELECT id, image_url, judul, description, tanggal_event 
                  FROM event 
                  WHERE tanggal_event > CURRENT_DATE 
                  ORDER BY tanggal_event ASC, id DESC';
        
        if ($limit !== null) {
            $query .= ' LIMIT :limit';
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
        }

        $results = $stmt->fetchAll();
        return array_map([$this, 'transformImageUrl'], $results);
    }

    private function transformImageUrl(array $row): array
    {
        if (isset($row['image_url'])) {
            $row['image_url'] = FileUploadHelper::getFullUrl($row['image_url']);
        }
        return $row;
    }
}