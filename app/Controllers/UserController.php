<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Middleware\AuthMiddleware;
use App\Models\User;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'User')]
class UserController
{
    private User $user;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->user = new User($connection);
    }

    #[OA\Post(
        path: '/user',
        summary: 'Tambah user baru',
        tags: ['User'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(
                response: 400,
                description: 'Payload tidak valid'
            ),
            new OA\Response(
                response: 409,
                description: 'Email sudah terdaftar'
            ),
            new OA\Response(
                response: 422,
                description: 'Validasi gagal'
            )
        ]
    )]
    public function store(): void
    {
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $name = trim($payload['name'] ?? '');
        $email = trim($payload['email'] ?? '');
        $password = $payload['password'] ?? '';

        if ($name === '') {
            Response::json(['message' => 'Field name wajib diisi'], 422);
            return;
        }

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

        if ($this->user->emailExists($email)) {
            Response::json(['message' => 'Email sudah terdaftar'], 409);
            return;
        }

        $passwordHash = md5($password);

        try {
            $user = $this->user->create($name, $email, $passwordHash);
        } catch (PDOException $exception) {
            Response::json(
                ['message' => 'Gagal membuat user', 'error' => $exception->getMessage()],
                500
            );
            return;
        }

        Response::json($user, 201);
    }

    #[OA\Get(
        path: '/user/profile',
        summary: 'Get user profile (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['User'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Token tidak valid atau tidak ditemukan'
            )
        ]
    )]
    public function profile(): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return; // Response sudah dikirim oleh middleware
        }

        $userId = $tokenData['user_id'] ?? null;
        if ($userId === null) {
            Response::json(['message' => 'User ID tidak ditemukan dalam token'], 401);
            return;
        }

        $stmt = $this->user->getConnection()->prepare(
            'SELECT id, name, email FROM "user" WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::json(['message' => 'User tidak ditemukan'], 404);
            return;
        }

        Response::json($user);
    }
}


