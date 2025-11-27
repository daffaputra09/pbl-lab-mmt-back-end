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

    public function all(?string $search = null, ?string $status = null): array
    {
        $whereConditions = [];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $whereConditions[] = '(b.judul ILIKE :search OR b.description ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== null && trim($status) !== '') {
            $whereConditions[] = 'LOWER(b.status) = LOWER(:status)';
            $params['status'] = $status;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $stmt = $this->db->prepare(
            "SELECT b.id, b.judul, b.description, b.image_url, b.id_user, b.status, b.created_at,
                    u.id as user_id, u.name as user_name, u.email as user_email
             FROM berita b
             LEFT JOIN \"user\" u ON b.id_user = u.id
             {$whereClause}
             ORDER BY b.created_at DESC"
        );

        $stmt->execute($params);
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformRow'], $results);
    }

    public function paginate(int $page = 1, int $perPage = 10, ?string $search = null, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        
        $whereConditions = [];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $whereConditions[] = '(b.judul ILIKE :search OR b.description ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== null && trim($status) !== '') {
            $whereConditions[] = 'LOWER(b.status) = LOWER(:status)';
            $params['status'] = $status;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $stmt = $this->db->prepare(
            "SELECT b.id, b.judul, b.description, b.image_url, b.id_user, b.status, b.created_at,
                    u.id as user_id, u.name as user_name, u.email as user_email
             FROM berita b
             LEFT JOIN \"user\" u ON b.id_user = u.id
             {$whereClause}
             ORDER BY b.created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformRow'], $results);
    }

    public function count(?string $search = null, ?string $status = null): int
    {
        $whereConditions = [];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $whereConditions[] = '(judul ILIKE :search OR description ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== null && trim($status) !== '') {
            $whereConditions[] = 'LOWER(status) = LOWER(:status)';
            $params['status'] = $status;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM berita {$whereClause}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT b.id, b.judul, b.description, b.image_url, b.id_user, b.status, b.created_at,
                    u.id as user_id, u.name as user_name, u.email as user_email
             FROM berita b
             LEFT JOIN "user" u ON b.id_user = u.id
             WHERE b.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformRow($result);
    }

    public function create(string $judul, string $description, string $imageUrl, ?int $idUser = null, string $status = 'published'): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO berita (judul, description, image_url, id_user, status)
             VALUES (:judul, :description, :image_url, :id_user, :status)
             RETURNING id'
        );
        $stmt->execute([
            'judul' => $judul,
            'description' => $description,
            'image_url' => $imageUrl,
            'id_user' => $idUser,
            'status' => $status,
        ]);

        $result = $stmt->fetch();
        $id = $result['id'];
        
        // Fetch the complete record with user info
        return $this->find($id);
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
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'judul' => $judul,
            'description' => $description,
            'image_url' => $imageUrl,
            'id_user' => $idUser,
            'status' => $status,
        ]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        // Fetch the complete record with user info
        return $this->find($id);
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
            'SELECT b.id, b.judul, b.description, b.image_url, b.id_user, b.status, b.created_at,
                    u.id as user_id, u.name as user_name, u.email as user_email
             FROM berita b
             LEFT JOIN "user" u ON b.id_user = u.id
             WHERE LOWER(b.status) = LOWER(:status)
             ORDER BY b.created_at DESC'
        );
        $stmt->execute(['status' => $status]);
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformRow'], $results);
    }

    private function transformRow(array $row): array
    {
        // Transform image URL to full URL
        if (isset($row['image_url'])) {
            $row['image_url'] = FileUploadHelper::getFullUrl($row['image_url']);
        }
        
        // Transform 'judul' to 'title'
        if (isset($row['judul'])) {
            $row['title'] = $row['judul'];
            unset($row['judul']);
        }
        
        // Add user object if user information exists
        if (isset($row['user_id'])) {
            $row['user'] = [
                'id' => $row['user_id'],
                'name' => $row['user_name'],
                'email' => $row['user_email']
            ];
            unset($row['user_id'], $row['user_name'], $row['user_email']);
        } else {
            $row['user'] = null;
        }
        
        return $row;
    }
}
