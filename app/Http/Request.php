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
        return $_POST;
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
}

