<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Models\User;
use Config\Database;
use Firebase\JWT\JWT;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth')]
class AuthController
{
    private User $user;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->user = new User($connection);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Login user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Payload tidak valid'
            ),
            new OA\Response(
                response: 401,
                description: 'Email atau password salah'
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal'
            )
        ]
    )]
    public function login(): void
    {
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $email = trim($payload['email'] ?? '');
        $password = $payload['password'] ?? '';

        if ($email === '') {
            Response::json(['message' => 'Field email wajib diisi'], 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['message' => 'Format email tidak valid'], 422);
            return;
        }

        if (!is_string($password) || $password === '') {
            Response::json(['message' => 'Field password wajib diisi'], 422);
            return;
        }

        $user = $this->user->findByEmail($email);

        if ($user === null) {
            Response::json(['message' => 'Email atau password salah'], 401);
            return;
        }

        $passwordHash = md5($password);

        if ($user['password'] !== $passwordHash) {
            Response::json(['message' => 'Email atau password salah'], 401);
            return;
        }

        // Generate JWT token
        $secretKey = $this->env('JWT_SECRET', 'default-secret-key-change-in-production');
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 60 * 24 * 7); // Token valid selama 7 hari

        $tokenPayload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $user['id'],
            'email' => $user['email'],
        ];

        $token = JWT::encode($tokenPayload, $secretKey, 'HS256');

        // Remove password from response
        unset($user['password']);

        Response::json([
            'user' => $user,
            'token' => $token,
            'exp' => $expirationTime,
        ]);
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

