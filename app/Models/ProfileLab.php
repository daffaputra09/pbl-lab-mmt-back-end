<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class ProfileLab
{
    public function __construct(private PDO $db)
    {
    }

    public function find(): ?array
    {
        $stmt = $this->db->query(
            'SELECT id, misi, visi, sejarah, nilai_inti 
             FROM profile_lab 
             ORDER BY id DESC 
             LIMIT 1'
        );
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        return $this->transformRow($result);
    }

    public function create(array $misi, string $visi, string $sejarah, string $nilaiInti): array
    {
        $misiArray = $this->arrayToPostgresArray($misi);
        
        $stmt = $this->db->prepare(
            'INSERT INTO profile_lab (misi, visi, sejarah, nilai_inti) 
             VALUES (:misi, :visi, :sejarah, :nilai_inti) 
             RETURNING id, misi, visi, sejarah, nilai_inti'
        );
        $stmt->execute([
            'misi' => $misiArray,
            'visi' => $visi,
            'sejarah' => $sejarah,
            'nilai_inti' => $nilaiInti,
        ]);

        $result = $stmt->fetch();
        return $this->transformRow($result);
    }

    public function update(int $id, array $misi, string $visi, string $sejarah, string $nilaiInti): ?array
    {
        $misiArray = $this->arrayToPostgresArray($misi);
        
        $stmt = $this->db->prepare(
            'UPDATE profile_lab 
             SET misi = :misi, visi = :visi, sejarah = :sejarah, nilai_inti = :nilai_inti 
             WHERE id = :id 
             RETURNING id, misi, visi, sejarah, nilai_inti'
        );
        $stmt->execute([
            'id' => $id,
            'misi' => $misiArray,
            'visi' => $visi,
            'sejarah' => $sejarah,
            'nilai_inti' => $nilaiInti,
        ]);

        $result = $stmt->fetch();
        
        if ($result === false) {
            return null;
        }

        return $this->transformRow($result);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM profile_lab WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function transformRow(array $row): array
    {
        // Parse PostgreSQL array for misi
        if (isset($row['misi']) && $row['misi'] !== null) {
            $row['misi'] = $this->parsePostgresArray($row['misi']);
        }
        
        return $row;
    }

    private function arrayToPostgresArray(?array $array): ?string
    {
        if ($array === null || empty($array)) {
            return null;
        }
        
        $escaped = array_map(function($item) {
            $escaped = str_replace('"', '\\"', (string) $item);
            return '"' . $escaped . '"';
        }, $array);
        
        return '{' . implode(',', $escaped) . '}';
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

