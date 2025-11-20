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

    public function all(?int $idKategori = null, ?array $tagNames = null, ?string $searchName = null): array
    {
        $query = 'SELECT DISTINCT p.id, p.name, p.description, p.id_kategori, p.video_url, p.image_url, p.status, p.created_at 
                  FROM project p';
        $conditions = [];
        $params = [];

        if ($idKategori) {
            $conditions[] = 'p.id_kategori = :id_kategori';
            $params['id_kategori'] = $idKategori;
        }

        if (!empty($tagNames)) {
            $query .= ' INNER JOIN project_tag pt ON p.id = pt.id_project 
                       INNER JOIN tag t ON pt.id_tag = t.id';
            $placeholders = [];
            foreach ($tagNames as $index => $tagName) {
                $key = 'tag_name_' . $index;
                $placeholders[] = 'LOWER(t.name) = LOWER(:' . $key . ')';
                $params[$key] = trim($tagName);
            }
            $conditions[] = '(' . implode(' OR ', $placeholders) . ')';
        }

        if ($searchName !== null && $searchName !== '') {
            $conditions[] = 'LOWER(p.name) LIKE LOWER(:search_name)';
            $params['search_name'] = '%' . trim($searchName) . '%';
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY p.created_at DESC';

        if (empty($params)) {
            $stmt = $this->db->query($query);
        } else {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        }

        $results = $stmt->fetchAll();
        $results = array_map([$this, 'parseImageUrl'], $results);
        return array_map(function($row) {
            $row['tags'] = $this->getTags($row['id']);
            $kategori = $this->getKategori($row['id_kategori']);
            $row['kategori'] = $kategori ?: ['id' => $row['id_kategori'], 'name' => null];
            return $row;
        }, $results);
    }

    public function paginate(int $page = 1, int $limit = 10, ?int $idKategori = null, ?array $tagNames = null, ?string $searchName = null): array
    {
        $offset = ($page - 1) * $limit;
        
        $query = 'SELECT DISTINCT p.id, p.name, p.description, p.id_kategori, p.video_url, p.image_url, p.status, p.created_at 
                  FROM project p';
        $conditions = [];
        $params = [];

        if ($idKategori) {
            $conditions[] = 'p.id_kategori = :id_kategori';
            $params['id_kategori'] = $idKategori;
        }

        if (!empty($tagNames)) {
            $query .= ' INNER JOIN project_tag pt ON p.id = pt.id_project 
                       INNER JOIN tag t ON pt.id_tag = t.id';
            $placeholders = [];
            foreach ($tagNames as $index => $tagName) {
                $key = 'tag_name_' . $index;
                $placeholders[] = 'LOWER(t.name) = LOWER(:' . $key . ')';
                $params[$key] = trim($tagName);
            }
            $conditions[] = '(' . implode(' OR ', $placeholders) . ')';
        }

        if ($searchName !== null && $searchName !== '') {
            $conditions[] = 'LOWER(p.name) LIKE LOWER(:search_name)';
            $params['search_name'] = '%' . trim($searchName) . '%';
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset';
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, \PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        $stmt->execute();

        $results = $stmt->fetchAll();
        $results = array_map([$this, 'parseImageUrl'], $results);
        return array_map(function($row) {
            $row['tags'] = $this->getTags($row['id']);
            $kategori = $this->getKategori($row['id_kategori']);
            $row['kategori'] = $kategori ?: ['id' => $row['id_kategori'], 'name' => null];
            return $row;
        }, $results);
    }

    public function count(?int $idKategori = null, ?array $tagNames = null, ?string $searchName = null): int
    {
        $query = 'SELECT COUNT(DISTINCT p.id) FROM project p';
        $conditions = [];
        $params = [];

        if ($idKategori) {
            $conditions[] = 'p.id_kategori = :id_kategori';
            $params['id_kategori'] = $idKategori;
        }

        if (!empty($tagNames)) {
            $query .= ' INNER JOIN project_tag pt ON p.id = pt.id_project 
                       INNER JOIN tag t ON pt.id_tag = t.id';
            $placeholders = [];
            foreach ($tagNames as $index => $tagName) {
                $key = 'tag_name_' . $index;
                $placeholders[] = 'LOWER(t.name) = LOWER(:' . $key . ')';
                $params[$key] = trim($tagName);
            }
            $conditions[] = '(' . implode(' OR ', $placeholders) . ')';
        }

        if ($searchName !== null && $searchName !== '') {
            $conditions[] = 'LOWER(p.name) LIKE LOWER(:search_name)';
            $params['search_name'] = '%' . trim($searchName) . '%';
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if (empty($params)) {
            $stmt = $this->db->query($query);
        } else {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
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

        $result = $this->parseImageUrl($result);
        $result['tags'] = $this->getTags($id);
        $kategori = $this->getKategori($result['id_kategori']);
        $result['kategori'] = $kategori ?: ['id' => $result['id_kategori'], 'name' => null];
        return $result;
    }

    public function getTags(int $projectId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.name 
             FROM tag t 
             INNER JOIN project_tag pt ON t.id = pt.id_tag 
             WHERE pt.id_project = :project_id 
             ORDER BY t.name'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    public function getKategori(int $kategoriId): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name FROM kategori WHERE id = :id');
        $stmt->execute(['id' => $kategoriId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function syncTags(int $projectId, array $tagIds): void
    {
        $stmt = $this->db->prepare('DELETE FROM project_tag WHERE id_project = :project_id');
        $stmt->execute(['project_id' => $projectId]);

        if (!empty($tagIds)) {
            $stmt = $this->db->prepare(
                'INSERT INTO project_tag (id_project, id_tag) VALUES (:project_id, :tag_id)'
            );
            foreach ($tagIds as $tagId) {
                $stmt->execute([
                    'project_id' => $projectId,
                    'tag_id' => (int) $tagId,
                ]);
            }
        }
    }

    public function create(
        string $name,
        ?string $description,
        int $idKategori,
        ?string $videoUrl,
        array $imageUrls,
        string $status = 'on_progress',
        array $tagIds = []
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
        $result = $this->parseImageUrl($result);
        
        $this->syncTags($result['id'], $tagIds);
        $result['tags'] = $this->getTags($result['id']);
        
        $kategori = $this->getKategori($result['id_kategori']);
        $result['kategori'] = $kategori ?: ['id' => $result['id_kategori'], 'name' => null];
        
        return $result;
    }

    public function update(
        int $id,
        string $name,
        ?string $description,
        int $idKategori,
        ?string $videoUrl,
        array $imageUrls,
        string $status,
        array $tagIds = []
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

        $result = $this->parseImageUrl($result);
        
        $this->syncTags($id, $tagIds);
        $result['tags'] = $this->getTags($id);
        
        $kategori = $this->getKategori($result['id_kategori']);
        $result['kategori'] = $kategori ?: ['id' => $result['id_kategori'], 'name' => null];
        
        return $result;
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
        $results = array_map([$this, 'parseImageUrl'], $results);
        return array_map(function($row) {
            $row['tags'] = $this->getTags($row['id']);
            $kategori = $this->getKategori($row['id_kategori']);
            $row['kategori'] = $kategori ?: ['id' => $row['id_kategori'], 'name' => null];
            return $row;
        }, $results);
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
