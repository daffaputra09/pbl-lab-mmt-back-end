<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\FileUploadHelper;
use PDO;
use PDOException;

class Project
{
    public function __construct(private PDO $db)
    {
    }

    public function all(?int $idKategori = null): array
    {
        if ($idKategori) {
            $stmt = $this->db->prepare(
                'SELECT id, name, description, id_kategori, video_url, image_url, status, created_at 
                FROM project 
                WHERE id_kategori = :id_kategori 
                ORDER BY created_at DESC'
            );
            $stmt->execute(['id_kategori' => $idKategori]);
        } else {
            $stmt = $this->db->query(
                'SELECT id, name, description, id_kategori, video_url, image_url, status, created_at 
                FROM project 
                ORDER BY created_at DESC'
            );
        }

        $results = $stmt->fetchAll();
        return array_map([$this, 'parseImageUrl'], $results);
    }

    public function paginate(int $page = 1, int $limit = 10, ?int $idKategori = null): array
    {
        $offset = ($page - 1) * $limit;
        
        if ($idKategori) {
            $stmt = $this->db->prepare(
                'SELECT id, name, description, id_kategori, video_url, image_url, status, created_at 
                FROM project 
                WHERE id_kategori = :id_kategori 
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':id_kategori', $idKategori, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare(
                'SELECT id, name, description, id_kategori, video_url, image_url, status, created_at 
                FROM project 
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
        }

        $results = $stmt->fetchAll();
        return array_map([$this, 'parseImageUrl'], $results);
    }

    public function count(?int $idKategori = null): int
    {
        if ($idKategori) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM project WHERE id_kategori = :id_kategori');
            $stmt->execute(['id_kategori' => $idKategori]);
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) FROM project');
        }
        return (int) $stmt->fetchColumn();
    }


    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, id_kategori, video_url, image_url, status, created_at 
             FROM project WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->parseImageUrl($result);
    }

    public function create(
        string $name,
        ?string $description,
        int $idKategori,
        ?string $videoUrl,
        array $imageUrls,
        string $status = 'active'
    ): array {
        // Convert imageUrls array into a Postgres array literal (if using Postgres)
        $imageUrlsSql = '{' . implode(',', array_map(fn($u) => '"' . $u . '"', $imageUrls)) . '}';

        $stmt = $this->db->prepare(
            'INSERT INTO project (name, description, id_kategori, video_url, image_url, status)
             VALUES (:name, :description, :id_kategori, :video_url, :image_url, :status)
             RETURNING id, name, description, id_kategori, video_url, image_url, status, created_at'
        );
        $stmt->execute([
            'name'        => $name,
            'description' => $description,
            'id_kategori' => $idKategori,
            'video_url'   => $videoUrl,
            'image_url'   => $imageUrlsSql,
            'status'      => $status,
        ]);

        $result = $stmt->fetch();
        return $this->parseImageUrl($result);
    }

    public function update(
        int $id,
        string $name,
        ?string $description,
        int $idKategori,
        ?string $videoUrl,
        array $imageUrls,
        string $status
    ): ?array {
        $imageUrlsSql = '{' . implode(',', array_map(fn($u) => '"' . $u . '"', $imageUrls)) . '}';

        $stmt = $this->db->prepare(
            'UPDATE project 
             SET name = :name,
                 description = :description,
                 id_kategori = :id_kategori,
                 video_url = :video_url,
                 image_url = :image_url,
                 status = :status
             WHERE id = :id
             RETURNING id, name, description, id_kategori, video_url, image_url, status, created_at'
        );
        $stmt->execute([
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'id_kategori' => $idKategori,
            'video_url'   => $videoUrl,
            'image_url'   => $imageUrlsSql,
            'status'      => $status,
        ]);

        $result = $stmt->fetch();
        if ($result === false) {
            return null;
        }

        return $this->parseImageUrl($result);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM project WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function findByStatus(string $status): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, description, id_kategori, video_url, image_url, status, created_at 
             FROM project 
             WHERE LOWER(status) = LOWER(:status)
             ORDER BY created_at DESC'
        );
        $stmt->execute(['status' => $status]);
        $results = $stmt->fetchAll();
        return array_map([$this, 'parseImageUrl'], $results);
    }

    private function parseImageUrl(array $row): array
    {
        if (isset($row['image_url'])) {
            $imageUrl = $row['image_url'];
            
            if (is_string($imageUrl)) {
                if (strpos($imageUrl, '{') === 0 && strpos($imageUrl, '}') === strlen($imageUrl) - 1) {
                    $imageUrl = trim($imageUrl, '{}');
                    if ($imageUrl === '') {
                        $row['image_url'] = [];
                    } else {
                        $row['image_url'] = array_map('trim', explode(',', $imageUrl));
                        $row['image_url'] = array_map(function($url) {
                            return trim($url, '"');
                        }, $row['image_url']);
                    }
                } else {
                    $row['image_url'] = [];
                }
            } elseif (!is_array($imageUrl)) {
                $row['image_url'] = [];
            }
        } else {
            $row['image_url'] = [];
        }

        // Transform URLs to include base URL
        return $this->transformUrls($row);
    }

    private function transformUrls(array $row): array
    {
        // Transform video_url
        if (isset($row['video_url'])) {
            $row['video_url'] = FileUploadHelper::getFullUrl($row['video_url']);
        }

        // Transform image_url array
        if (isset($row['image_url']) && is_array($row['image_url'])) {
            $row['image_url'] = FileUploadHelper::getFullUrls($row['image_url']);
        }

        return $row;
    }
}
