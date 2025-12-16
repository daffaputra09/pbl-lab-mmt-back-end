<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\FileUploadHelper;
use PDO;
use PDOException;

class PartnerLab
{
    public function __construct(private PDO $db)
    {
    }

    public function all(?string $search = null): array
    {
        $query = 'SELECT id, nama, deskripsi, image_url, created_at FROM partner_lab';
        $params = [];
        
        if ($search !== null && $search !== '') {
            $query .= ' WHERE LOWER(nama) LIKE LOWER(:search) OR LOWER(deskripsi) LIKE LOWER(:search)';
            $params['search'] = "%{$search}%";
        }
        
        $query .= ' ORDER BY id ASC';
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->db->query($query);
        }
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformRow'], $results);
    }

    public function paginate(int $page = 1, int $limit = 10, ?string $search = null): array
    {
        $offset = ($page - 1) * $limit;
        
        $query = 'SELECT id, nama, deskripsi, image_url, created_at FROM partner_lab';
        $params = [];
        
        if ($search !== null && $search !== '') {
            $query .= ' WHERE LOWER(nama) LIKE LOWER(:search) OR LOWER(deskripsi) LIKE LOWER(:search)';
            $params['search'] = "%{$search}%";
        }
        
        $query .= ' ORDER BY id ASC LIMIT :limit OFFSET :offset';
        
        $stmt = $this->db->prepare($query);
        
        if (isset($params['search'])) {
            $stmt->bindValue(':search', $params['search'], \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformRow'], $results);
    }

    public function count(?string $search = null): int
    {
        $query = 'SELECT COUNT(*) FROM partner_lab';
        $params = [];
        
        if ($search !== null && $search !== '') {
            $query .= ' WHERE LOWER(nama) LIKE LOWER(:search) OR LOWER(deskripsi) LIKE LOWER(:search)';
            $params['search'] = "%{$search}%";
        }
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->db->query($query);
        }
        
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nama, deskripsi, image_url, created_at 
             FROM partner_lab 
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformRow($result);
    }

    public function create(
        string $nama,
        ?string $deskripsi = null,
        ?string $imageUrl = null
    ): array {
        $stmt = $this->db->prepare(
            'INSERT INTO partner_lab (nama, deskripsi, image_url) 
             VALUES (:nama, :deskripsi, :image_url) 
             RETURNING id, nama, deskripsi, image_url, created_at'
        );
        $stmt->execute([
            'nama' => $nama,
            'deskripsi' => $deskripsi,
            'image_url' => $imageUrl,
        ]);

        $result = $stmt->fetch();
        return $this->transformRow($result);
    }

    public function update(
        int $id,
        string $nama,
        ?string $deskripsi = null,
        ?string $imageUrl = null
    ): ?array {
        $stmt = $this->db->prepare(
            'UPDATE partner_lab 
             SET nama = :nama, deskripsi = :deskripsi, image_url = :image_url 
             WHERE id = :id 
             RETURNING id, nama, deskripsi, image_url, created_at'
        );
        $stmt->execute([
            'id' => $id,
            'nama' => $nama,
            'deskripsi' => $deskripsi,
            'image_url' => $imageUrl,
        ]);

        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformRow($result);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM partner_lab WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function transformRow(array $row): array
    {
        if (isset($row['image_url'])) {
            $row['image_url'] = FileUploadHelper::getFullUrl($row['image_url']);
        }
        return $row;
    }
}

