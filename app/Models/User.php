<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    public function __construct(private PDO $db)
    {
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }

    public function create(string $name, string $email, string $passwordHash): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO "user" (name, email, password)
             VALUES (:name, :email, :password)
             RETURNING id, name, email'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
        ]);

        return $stmt->fetch();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM "user" WHERE LOWER(email) = LOWER(:email)'
        );
        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password FROM "user" WHERE LOWER(email) = LOWER(:email)'
        );
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();

        return $result ?: null;
    }
}


