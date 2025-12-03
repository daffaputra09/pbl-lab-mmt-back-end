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

    public function all(?bool $sortByDate = null, ?string $search = null, ?string $status = null): array
    {
        $query = 'SELECT id, image_url, judul, description, tanggal_event FROM event';
        $params = [];
        $whereConditions = [];
        
        if ($search !== null && $search !== '') {
            $whereConditions[] = '(LOWER(judul) LIKE LOWER(:search) OR LOWER(description) LIKE LOWER(:search))';
            $params['search'] = "%{$search}%";
        }
        
        // Filter by status
        if ($status !== null && $status !== '') {
            $statusLower = strtolower($status);
            if ($statusLower === 'terlewat') {
                $whereConditions[] = 'tanggal_event < CURRENT_DATE';
            } elseif ($statusLower === 'akan_datang') {
                $whereConditions[] = 'tanggal_event >= CURRENT_DATE';
            } elseif ($statusLower === 'tidak_ditentukan') {
                $whereConditions[] = 'tanggal_event IS NULL';
            }
        }
        
        if (!empty($whereConditions)) {
            $query .= ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        if ($sortByDate === true) {
            $query .= ' ORDER BY 
                CASE 
                    WHEN tanggal_event IS NULL THEN 2
                    WHEN tanggal_event >= CURRENT_DATE THEN 0
                    ELSE 1
                END,
                CASE WHEN tanggal_event >= CURRENT_DATE THEN tanggal_event END ASC,
                CASE WHEN tanggal_event < CURRENT_DATE THEN tanggal_event END DESC,
                id DESC';
        } elseif ($sortByDate === false) {
            $query .= ' ORDER BY id DESC';
        } else {
            $query .= ' ORDER BY id DESC';
        }
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        } else {
            $stmt = $this->db->query($query);
        }
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformRow'], $results);
    }

    public function paginate(int $page = 1, int $limit = 10, ?bool $sortByDate = null, ?string $search = null, ?string $status = null): array
    {
        $offset = ($page - 1) * $limit;
        
        $query = 'SELECT id, image_url, judul, description, tanggal_event 
                  FROM event';
        $params = [];
        $whereConditions = [];
        
        if ($search !== null && $search !== '') {
            $whereConditions[] = '(LOWER(judul) LIKE LOWER(:search) OR LOWER(description) LIKE LOWER(:search))';
            $params['search'] = "%{$search}%";
        }
        
        // Filter by status
        if ($status !== null && $status !== '') {
            $statusLower = strtolower($status);
            if ($statusLower === 'terlewat') {
                $whereConditions[] = 'tanggal_event < CURRENT_DATE';
            } elseif ($statusLower === 'akan_datang') {
                $whereConditions[] = 'tanggal_event >= CURRENT_DATE';
            } elseif ($statusLower === 'tidak_ditentukan') {
                $whereConditions[] = 'tanggal_event IS NULL';
            }
        }
        
        if (!empty($whereConditions)) {
            $query .= ' WHERE ' . implode(' AND ', $whereConditions);
        }
        
        if ($sortByDate === true) {
            // Urutkan: belum terlewat dulu (prioritas 0), terlewat setelahnya (prioritas 1), NULL di akhir (prioritas 2)
            // Untuk yang belum terlewat: urutkan berdasarkan tanggal terdekat (ASC)
            // Untuk yang terlewat: urutkan berdasarkan tanggal terbaru terlewat (DESC)
            $query .= ' ORDER BY 
                CASE 
                    WHEN tanggal_event IS NULL THEN 2
                    WHEN tanggal_event >= CURRENT_DATE THEN 0
                    ELSE 1
                END,
                CASE WHEN tanggal_event >= CURRENT_DATE THEN tanggal_event END ASC,
                CASE WHEN tanggal_event < CURRENT_DATE THEN tanggal_event END DESC,
                id DESC';
        } elseif ($sortByDate === false) {
            $query .= ' ORDER BY id DESC';
        } else {
            $query .= ' ORDER BY id DESC';
        }
        
        $query .= ' LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        if (isset($params['search'])) {
            $stmt->bindValue(':search', $params['search'], \PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        return array_map([$this, 'transformRow'], $results);
    }

    public function count(?string $search = null, ?bool $sortByDate = null, ?string $status = null): int
    {
        $query = 'SELECT COUNT(*) FROM event';
        $params = [];
        $whereConditions = [];
        
        if ($search !== null && $search !== '') {
            $whereConditions[] = '(LOWER(judul) LIKE LOWER(:search) OR LOWER(description) LIKE LOWER(:search))';
            $params['search'] = "%{$search}%";
        }
        
        // Filter by status
        if ($status !== null && $status !== '') {
            $statusLower = strtolower($status);
            if ($statusLower === 'terlewat') {
                $whereConditions[] = 'tanggal_event < CURRENT_DATE';
            } elseif ($statusLower === 'akan_datang') {
                $whereConditions[] = 'tanggal_event >= CURRENT_DATE';
            } elseif ($statusLower === 'tidak_ditentukan') {
                $whereConditions[] = 'tanggal_event IS NULL';
            }
        }
        
        if (!empty($whereConditions)) {
            $query .= ' WHERE ' . implode(' AND ', $whereConditions);
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
        $stmt = $this->db->prepare('SELECT id, image_url, judul, description, tanggal_event FROM event WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformRow($result);
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
        return $this->transformRow($result);
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

        return $this->transformRow($result);
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
        return array_map([$this, 'transformRow'], $results);
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
        
        return $row;
    }

    public function addEventStatus(array $row): array
    {
        // Row sudah di-transform oleh transformRow sebelumnya, jadi langsung tambahkan status
        if ($row['tanggal_event'] === null) {
            $row['status'] = 'tidak_ditentukan';
        } else {
            $tanggalEvent = new \DateTime($row['tanggal_event']);
            $today = new \DateTime('today');
            
            if ($tanggalEvent < $today) {
                $row['status'] = 'terlewat';
            } else {
                $row['status'] = 'akan_datang';
            }
        }
        return $row;
    }
}