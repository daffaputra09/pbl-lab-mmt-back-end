<?php

declare(strict_types=1);

namespace App\Http;

class FileUploadHelper
{
    private const MAX_FILE_SIZE = 104857600; // 100MB in bytes
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'];
    private const ALLOWED_LOTTIE_TYPES = ['application/json', 'text/json', 'application/octet-stream'];
    
    public static function uploadImage(array $file, string $directory): string
    {
        return self::upload($file, $directory, self::ALLOWED_IMAGE_TYPES);
    }

    public static function uploadVideo(array $file, string $directory): string
    {
        return self::upload($file, $directory, self::ALLOWED_VIDEO_TYPES);
    }

    public static function uploadGaleriFile(array $file, string $type, string $directory): string
    {
        $typeLower = strtolower($type);
        if ($typeLower === 'foto') {
            return self::uploadImage($file, $directory);
        } elseif ($typeLower === 'video') {
            return self::uploadVideo($file, $directory);
        } elseif ($typeLower === 'lottie') {
            return self::uploadLottie($file, $directory);
        } else {
            throw new \InvalidArgumentException('Type harus "foto", "video", atau "lottie"');
        }
    }

    public static function uploadLottie(array $file, string $directory): string
    {
        return self::upload($file, $directory, self::ALLOWED_LOTTIE_TYPES);
    }

    private static function upload(
        array $file, 
        string $directory, 
        array $allowedTypes, 
        int $maxSize = self::MAX_FILE_SIZE
    ): string {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \InvalidArgumentException('No file uploaded');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \InvalidArgumentException('File size exceeds limit');
            default:
                throw new \InvalidArgumentException('Unknown file upload error');
        }

        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            throw new \InvalidArgumentException("File size exceeds {$maxSizeMB}MB limit");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $originalExtension = self::getExtensionFromFilename($file['name'] ?? '');
        
        if (!in_array($mimeType, $allowedTypes)) {
            if ($mimeType === 'application/octet-stream' && $originalExtension === 'json') {
                $mimeType = 'application/json';
            } else {
                throw new \InvalidArgumentException('Invalid file type: ' . $mimeType);
            }
        }

        $extension = self::getExtensionFromMimeType($mimeType);
        
        if ($originalExtension === 'lottie') {
            $extension = 'json';
        }
        
        $filename = self::generateUniqueFilename($extension);
        
        $uploadPath = dirname(__DIR__, 2) . '/public/' . $directory;
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                throw new \InvalidArgumentException('Failed to create upload directory');
            }
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('File is not a valid uploaded file');
        }

        $destination = $uploadPath . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \InvalidArgumentException('Failed to move uploaded file');
        }

        return $directory . '/' . $filename;
    }

    public static function deleteFile(?string $filePath): bool
    {
        if ($filePath === null || $filePath === '') {
            return true;
        }

        if (preg_match('/^https?:\/\//', $filePath)) {
            $parsed = parse_url($filePath);
            $filePath = $parsed['path'] ?? '';
            
            if (strpos($filePath, '/uploads/') !== false) {
                $filePath = substr($filePath, strpos($filePath, '/uploads/'));
            }
        }

        $filePath = str_replace('\\', '/', $filePath);
        $filePath = ltrim($filePath, '/');
        
        $fullPath = dirname(__DIR__, 2) . '/public/' . $filePath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return true;
    }

    private static function generateUniqueFilename(string $extension): string
    {
        return uniqid('file_', true) . '_' . time() . '.' . $extension;
    }

    private static function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/octet-stream' => 'json', 
        ];

        return $mimeMap[$mimeType] ?? 'bin';
    }

    private static function getExtensionFromFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if ($extension === 'lottie') {
            return 'json';
        }
        
        return $extension;
    }

    public static function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        return $filename;
    }

    /**
     * Get base URL (host/domain/IP) 
     */
    public static function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        
        $basePath = str_replace('\\', '/', $basePath);
        
        $basePath = rtrim($basePath, '/');
        
        if ($basePath === '/' || $basePath === '') {
            $basePath = '';
        }
        
        return $protocol . '://' . $host . $basePath;
    }

    public static function getFullUrl(?string $filePath): ?string
    {
        if ($filePath === null || $filePath === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//', $filePath)) {
            return $filePath;
        }

        $filePath = str_replace('\\', '/', $filePath);
        
        if (strpos($filePath, '/') !== 0) {
            $filePath = '/' . $filePath;
        }

        return self::getBaseUrl() . $filePath;
    }

    public static function getFullUrls(array $filePaths): array
    {
        return array_map([self::class, 'getFullUrl'], $filePaths);
    }

    public static function getRelativePath(?string $filePath): ?string
    {
        if ($filePath === null || $filePath === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//', $filePath)) {
            $parsed = parse_url($filePath);
            $filePath = $parsed['path'] ?? '';
        }

        $filePath = str_replace('\\', '/', $filePath);
        
        $filePath = ltrim($filePath, '/');

        return $filePath ?: null;
    }

    public static function getRelativePaths(array $filePaths): array
    {
        return array_map([self::class, 'getRelativePath'], $filePaths);
    }
}

