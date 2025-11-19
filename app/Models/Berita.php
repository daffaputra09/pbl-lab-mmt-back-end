<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\FileUploadHelper;
use PDO;
use PDOException;

class Berita
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, judul, description, image_url, id_user, status, created_at 
             FROM berita 
             ORDER BY created_at DESC'
        );
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformImageUrl'], $results);
    }

    public function paginate(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare(
            'SELECT id, judul, description, image_url, id_user, status, created_at 
             FROM berita 
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformImageUrl'], $results);
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM berita');
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, judul, description, image_url, id_user, status, created_at 
             FROM berita WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformImageUrl($result);
    }

    public function create(string $judul, string $description, string $imageUrl, ?int $idUser = null, string $status = 'published'): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO berita (judul, description, image_url, id_user, status)
             VALUES (:judul, :description, :image_url, :id_user, :status)
             RETURNING id, judul, description, image_url, id_user, status, created_at'
        );
        $stmt->execute([
            'judul' => $judul,
            'description' => $description,
            'image_url' => $imageUrl,
            'id_user' => $idUser,
            'status' => $status,
        ]);

        $result = $stmt->fetch();
        return $this->transformImageUrl($result);
    }

    public function update(
        int $id,
        string $judul,
        string $description,
        string $imageUrl,
        ?int $idUser = null,
        string $status = 'published'
    ): ?array {
        $stmt = $this->db->prepare(
            'UPDATE berita 
             SET judul = :judul, description = :description, image_url = :image_url, id_user = :id_user, status = :status
             WHERE id = :id
             RETURNING id, judul, description, image_url, id_user, status, created_at'
        );
        $stmt->execute([
            'id' => $id,
            'judul' => $judul,
            'description' => $description,
            'image_url' => $imageUrl,
            'id_user' => $idUser,
            'status' => $status,
        ]);

        $result = $stmt->fetch();
        if ($result === false) {
            return null;
        }

        return $this->transformImageUrl($result);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM berita WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, judul, description, image_url, id_user, status, created_at 
             FROM berita 
             WHERE LOWER(status) = LOWER(:status)
             ORDER BY created_at DESC'
        );
        $stmt->execute(['status' => $status]);
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
