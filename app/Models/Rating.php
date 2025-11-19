<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class Rating
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, id_project, name, rating, comment, created_at
             FROM rating
             ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public function paginate(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare(
            'SELECT id, id_project, name, rating, comment, created_at
             FROM rating
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM rating');
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, id_project, name, rating, comment, created_at
             FROM rating WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function create(int $idProject, string $name, int $ratingValue, ?string $comment = null): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO rating (id_project, name, rating, comment)
             VALUES (:id_project, :name, :rating, :comment)
             RETURNING id, id_project, name, rating, comment, created_at'
        );
        $stmt->execute([
            'id_project' => $idProject,
            'name'       => $name,
            'rating'     => $ratingValue,
            'comment'    => $comment,
        ]);

        return $stmt->fetch();
    }

    public function update(int $id, int $idProject, string $name, int $ratingValue, ?string $comment = null): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE rating
             SET id_project = :id_project,
                 name       = :name,
                 rating     = :rating,
                 comment    = :comment
             WHERE id = :id
             RETURNING id, id_project, name, rating, comment, created_at'
        );
        $stmt->execute([
            'id'         => $id,
            'id_project' => $idProject,
            'name'       => $name,
            'rating'     => $ratingValue,
            'comment'    => $comment,
        ]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM rating WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function findByProject(int $idProject): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, id_project, name, rating, comment, created_at
             FROM rating 
             WHERE id_project = :id_project
             ORDER BY created_at DESC'
        );
        $stmt->execute(['id_project' => $idProject]);
        return $stmt->fetchAll();
    }
}
