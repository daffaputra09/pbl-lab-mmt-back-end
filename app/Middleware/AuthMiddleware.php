<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthMiddleware
{
    public static function verify(): ?array
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if ($authHeader === '') {
            Response::json(['message' => 'Token tidak ditemukan'], 401);
            return null;
        }

        // Format: "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            Response::json(['message' => 'Format token tidak valid'], 401);
            return null;
        }

        $token = $matches[1];
        $secretKey = self::env('JWT_SECRET', 'default-secret-key-change-in-production');

        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            Response::json(['message' => 'Token tidak valid atau kadaluarsa'], 401);
            return null;
        }
    }

    private static function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

