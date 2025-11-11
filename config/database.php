<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $host = $this->env('DB_HOST', 'localhost');
        $port = $this->env('DB_PORT', '5433');
        $database = $this->env('DB_DATABASE');
        $username = $this->env('DB_USERNAME');
        $password = $this->env('DB_PASSWORD');
        $sslmode = $this->env('DB_SSLMODE');

        if ($database === null || $username === null || $password === null) {
            throw new \RuntimeException('Database configuration is incomplete. Please set DB_DATABASE, DB_USERNAME, and DB_PASSWORD.');
        }

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
        if ($sslmode !== null) {
            $dsn .= ';sslmode=' . $sslmode;
        }

        try {
            $this->conn = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return $this->conn;
    }

    private function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}