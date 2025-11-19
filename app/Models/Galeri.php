<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\FileUploadHelper;
use PDO;
use PDOException;

class Galeri
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, type, file_url, tanggal_kegiatan, created_at 
             FROM galeri 
             ORDER BY tanggal_kegiatan DESC, created_at DESC'
        );
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformFileUrl'], $results);
    }

    public function paginate(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare(
            'SELECT id, type, file_url, tanggal_kegiatan, created_at 
             FROM galeri 
             ORDER BY tanggal_kegiatan DESC, created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformFileUrl'], $results);
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM galeri');
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, type, file_url, tanggal_kegiatan, created_at FROM galeri WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformFileUrl($result);
    }

    public function create(string $type, string $fileUrl, ?string $tanggalKegiatan = null): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO galeri (type, file_url, tanggal_kegiatan) VALUES (:type, :file_url, :tanggal_kegiatan) 
             RETURNING id, type, file_url, tanggal_kegiatan, created_at'
        );
        $stmt->execute([
            'type' => $type,
            'file_url' => $fileUrl,
            'tanggal_kegiatan' => $tanggalKegiatan,
        ]);

        $result = $stmt->fetch();
        return $this->transformFileUrl($result);
    }

    public function update(
        int $id, 
        string $type, 
        string $fileUrl, 
        ?string $tanggalKegiatan = null
    ): ?array {
        $stmt = $this->db->prepare(
            'UPDATE galeri 
             SET type = :type, file_url = :file_url, tanggal_kegiatan = :tanggal_kegiatan 
             WHERE id = :id 
             RETURNING id, type, file_url, tanggal_kegiatan, created_at'
        );
        $stmt->execute([
            'id' => $id,
            'type' => $type,
            'file_url' => $fileUrl,
            'tanggal_kegiatan' => $tanggalKegiatan,
        ]);

        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformFileUrl($result);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM galeri WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function findByType(string $type): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, type, file_url, tanggal_kegiatan, created_at 
             FROM galeri 
             WHERE LOWER(type) = LOWER(:type)
             ORDER BY tanggal_kegiatan DESC'
        );
        $stmt->execute(['type' => $type]);
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformFileUrl'], $results);
    }

    private function transformFileUrl(array $row): array
    {
        if (isset($row['file_url'])) {
            $row['file_url'] = FileUploadHelper::getFullUrl($row['file_url']);
        }
        return $row;
    }
}