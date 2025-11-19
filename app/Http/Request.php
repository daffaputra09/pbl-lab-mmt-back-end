<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    public static function json(): array
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            return [];
        }

        $decoded = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload: ' . json_last_error_msg());
        }

        return $decoded;
    }

    public static function getFormData(): array
    {
        if (empty($_POST) && empty($_FILES) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            $postMaxSize = self::parseSize(ini_get('post_max_size'));
            
            if ($contentLength > $postMaxSize) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'File terlalu besar. Ukuran file: %s, maksimum yang diizinkan: %s. Silakan update post_max_size dan upload_max_filesize di php.ini.',
                        self::formatBytes($contentLength),
                        self::formatBytes($postMaxSize)
                    )
                );
            }
        }
        
        return $_POST;
    }
    
    private static function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }
    
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public static function getFile(string $key): ?array
    {
        if (!isset($_FILES[$key])) {
            return null;
        }

        if (!isset($_FILES[$key]['error']) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $_FILES[$key];
    }

    public static function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public static function getFilesArray(string $key): array
    {
        $files = [];

        if (isset($_FILES[$key])) {
            $file = $_FILES[$key];

            if (!is_array($file['name'])) {
                if (isset($file['error']) && 
                    $file['error'] === UPLOAD_ERR_OK &&
                    isset($file['tmp_name']) &&
                    !empty($file['tmp_name']) &&
                    file_exists($file['tmp_name'])) {
                    $files[] = $file;
                }
            } else {
                $count = count($file['name']);
                
                for ($i = 0; $i < $count; $i++) {
                    if (isset($file['error'][$i]) && 
                        $file['error'][$i] === UPLOAD_ERR_OK &&
                        isset($file['tmp_name'][$i]) &&
                        !empty($file['tmp_name'][$i]) &&
                        isset($file['name'][$i]) &&
                        !empty($file['name'][$i]) &&
                        file_exists($file['tmp_name'][$i])) {
                        $files[] = [
                            'name' => $file['name'][$i],
                            'type' => $file['type'][$i] ?? '',
                            'tmp_name' => $file['tmp_name'][$i],
                            'error' => $file['error'][$i],
                            'size' => $file['size'][$i] ?? 0,
                        ];
                    }
                }
            }
        }

        return $files;
    }

    public static function hasFilesArray(string $key): bool
    {
        $files = self::getFilesArray($key);
        return !empty($files);
    }

    public static function getAllFiles(): array
    {
        return $_FILES;
    }

    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function contentType(): string
    {
        return $_SERVER['CONTENT_TYPE'] ?? '';
    }

    public static function isMultipart(): bool
    {
        $contentType = self::contentType();
        return strpos($contentType, 'multipart/form-data') !== false;
    }

    public static function getQuery(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function getAllQuery(): array
    {
        return $_GET;
    }
}

