<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\FileUploadHelper;
use PDO;
use PDOException;

class Anggota
{
    public function __construct(private PDO $db)
    {
    }

    public function all(?string $search = null): array
    {
        $query = 'SELECT id, nama, role, image_url, skills, media_social 
                  FROM anggota';
        $params = [];
        
        if ($search !== null && $search !== '') {
            $query .= ' WHERE LOWER(nama) LIKE LOWER(:search)';
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
        
        $query = 'SELECT id, nama, role, image_url, skills, media_social 
                  FROM anggota';
        $params = [];
        
        if ($search !== null && $search !== '') {
            $query .= ' WHERE LOWER(nama) LIKE LOWER(:search)';
            $params['search'] = "%{$search}%";
        }
        
        $query .= ' ORDER BY id ASC LIMIT :limit OFFSET :offset';
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

    public function count(?string $search = null): int
    {
        $query = 'SELECT COUNT(*) FROM anggota';
        $params = [];
        
        if ($search !== null && $search !== '') {
            $query .= ' WHERE LOWER(nama) LIKE LOWER(:search)';
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
            'SELECT id, nama, role, image_url, skills, media_social 
             FROM anggota 
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
        ?string $role = null,
        ?string $imageUrl = null,
        ?array $skills = null,
        ?array $mediaSocial = null
    ): array {
        $skillsSql = $this->arrayToPostgresArray($skills);
        $mediaSocialSql = $this->mediaSocialToPostgresArray($mediaSocial);

        $stmt = $this->db->prepare(
            'INSERT INTO anggota (nama, role, image_url, skills, media_social) 
             VALUES (:nama, :role, :image_url, :skills, :media_social) 
             RETURNING id, nama, role, image_url, skills, media_social'
        );
        $stmt->execute([
            'nama' => $nama,
            'role' => $role,
            'image_url' => $imageUrl,
            'skills' => $skillsSql,
            'media_social' => $mediaSocialSql,
        ]);

        $result = $stmt->fetch();
        return $this->transformRow($result);
    }

    public function update(
        int $id,
        string $nama,
        ?string $role = null,
        ?string $imageUrl = null,
        ?array $skills = null,
        ?array $mediaSocial = null
    ): ?array {
        $skillsSql = $this->arrayToPostgresArray($skills);
        $mediaSocialSql = $this->mediaSocialToPostgresArray($mediaSocial);

        $stmt = $this->db->prepare(
            'UPDATE anggota 
             SET nama = :nama, role = :role, image_url = :image_url, skills = :skills, media_social = :media_social 
             WHERE id = :id 
             RETURNING id, nama, role, image_url, skills, media_social'
        );
        $stmt->execute([
            'id' => $id,
            'nama' => $nama,
            'role' => $role,
            'image_url' => $imageUrl,
            'skills' => $skillsSql,
            'media_social' => $mediaSocialSql,
        ]);

        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformRow($result);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM anggota WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function getProjects(int $idAnggota): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.name, p.description, p.id_kategori, p.video_url, p.image_url, p.status, p.created_at
             FROM project p
             INNER JOIN project_anggota pa ON p.id = pa.id_project
             WHERE pa.id_anggota = :id_anggota
             ORDER BY p.created_at DESC'
        );
        $stmt->execute(['id_anggota' => $idAnggota]);
        return $stmt->fetchAll();
    }

    public function syncProjects(int $idAnggota, array $projectIds): void
    {
        $stmt = $this->db->prepare('DELETE FROM project_anggota WHERE id_anggota = :id_anggota');
        $stmt->execute(['id_anggota' => $idAnggota]);

        if (!empty($projectIds)) {
            $stmt = $this->db->prepare(
                'INSERT INTO project_anggota (id_project, id_anggota) VALUES (:id_project, :id_anggota)'
            );
            foreach ($projectIds as $projectId) {
                $stmt->execute([
                    'id_project' => (int) $projectId,
                    'id_anggota' => $idAnggota,
                ]);
            }
        }
    }

    private function transformRow(array $row): array
    {
        // Transform image URL to full URL
        if (isset($row['image_url'])) {
            $row['image_url'] = FileUploadHelper::getFullUrl($row['image_url']);
        }
        
        // Parse PostgreSQL arrays
        if (isset($row['skills'])) {
            $row['skills'] = $this->parsePostgresArray($row['skills']);
        }
        
        if (isset($row['media_social'])) {
            $row['media_social'] = $this->parseMediaSocialArray($row['media_social']);
        }
        
        return $row;
    }

    private function arrayToPostgresArray(?array $array): ?string
    {
        if ($array === null || empty($array)) {
            return null;
        }
        
        // Escape and quote each element
        $escaped = array_map(function($item) {
            $item = str_replace('"', '\\"', $item);
            return '"' . $item . '"';
        }, $array);
        
        return '{' . implode(',', $escaped) . '}';
    }

    private function mediaSocialToPostgresArray(?array $mediaSocial): ?string
    {
        if ($mediaSocial === null || empty($mediaSocial)) {
            return null;
        }
        
        // Convert each object to JSON string and escape for PostgreSQL array
        $escaped = array_map(function($item) {
            // If it's already an object/array, encode to JSON
            if (is_array($item)) {
                $json = json_encode($item);
            } else {
                $json = $item; // Assume it's already a JSON string
            }
            
            // Escape quotes and wrap in quotes
            $json = str_replace('"', '\\"', $json);
            return '"' . $json . '"';
        }, $mediaSocial);
        
        return '{' . implode(',', $escaped) . '}';
    }

    private function parseMediaSocialArray(?string $pgArray): ?array
    {
        if ($pgArray === null || $pgArray === '') {
            return null;
        }
        
        // Remove curly braces
        $pgArray = trim($pgArray, '{}');
        
        if ($pgArray === '') {
            return [];
        }
        
        // Split by comma, but handle quoted JSON strings
        $result = [];
        $current = '';
        $inQuotes = false;
        
        for ($i = 0; $i < strlen($pgArray); $i++) {
            $char = $pgArray[$i];
            
            if ($char === '"' && ($i === 0 || $pgArray[$i - 1] !== '\\')) {
                $inQuotes = !$inQuotes;
            } elseif ($char === ',' && !$inQuotes) {
                if ($current !== '') {
                    $jsonString = $this->unescapeString($current);
                    $decoded = json_decode($jsonString, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $result[] = $decoded;
                    } else {
                        // Fallback: treat as simple string (backward compatibility)
                        $result[] = ['url' => $jsonString, 'type' => 'other'];
                    }
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }
        
        if ($current !== '') {
            $jsonString = $this->unescapeString($current);
            $decoded = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result[] = $decoded;
            } else {
                // Fallback: treat as simple string (backward compatibility)
                $result[] = ['url' => $jsonString, 'type' => 'other'];
            }
        }
        
        return $result;
    }

    private function parsePostgresArray(?string $pgArray): ?array
    {
        if ($pgArray === null || $pgArray === '') {
            return null;
        }
        
        // Remove curly braces
        $pgArray = trim($pgArray, '{}');
        
        if ($pgArray === '') {
            return [];
        }
        
        // Split by comma, but handle quoted strings
        $result = [];
        $current = '';
        $inQuotes = false;
        
        for ($i = 0; $i < strlen($pgArray); $i++) {
            $char = $pgArray[$i];
            
            if ($char === '"' && ($i === 0 || $pgArray[$i - 1] !== '\\')) {
                $inQuotes = !$inQuotes;
            } elseif ($char === ',' && !$inQuotes) {
                if ($current !== '') {
                    $result[] = $this->unescapeString($current);
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }
        
        if ($current !== '') {
            $result[] = $this->unescapeString($current);
        }
        
        return $result;
    }

    private function unescapeString(string $str): string
    {
        // Remove surrounding quotes if present
        $str = trim($str, '"');
        // Unescape escaped quotes
        $str = str_replace('\\"', '"', $str);
        return $str;
    }
}

