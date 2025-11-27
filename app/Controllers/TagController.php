<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Tag;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Tag')]
class TagController
{
    private Tag $tag;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->tag = new Tag($connection);
    }

    #[OA\Get(
        path: '/tag',
        summary: 'List semua tag dengan pagination',
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Nomor halaman (default: 1, hanya digunakan jika limit diisi)',
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1, example: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Jumlah item per halaman. Jika tidak diisi, akan mengembalikan semua data.',
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar tag dengan pagination',
                content: new OA\JsonContent(ref: '#/components/schemas/TagPaginatedResponse')
            ),
            new OA\Response(response: 400, description: 'Parameter tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengambil data tag')
        ]
    )]
    public function index(): void
    {
        try {
            $page = (int) Request::getQuery('page', 1);
            $limitParam = Request::getQuery('limit', null);
            $limit = $limitParam !== null ? (int) $limitParam : null;

            if ($page < 1) {
                Response::json(['message' => 'Parameter page harus lebih besar dari 0'], 400);
                return;
            }

            if ($limit !== null && $limit < 1) {
                Response::json(['message' => 'Parameter limit harus lebih besar dari 0'], 400);
                return;
            }

            $total = $this->tag->count();

            if ($limit === null) {
                $data = $this->tag->all();
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->tag->paginate($page, $limit);

            Response::json([
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data tag', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/tag/{id}',
        summary: 'Detail tag',
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail tag',
                content: new OA\JsonContent(ref: '#/components/schemas/Tag')
            ),
            new OA\Response(response: 404, description: 'Tag tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        $tag = $this->tag->find($id);

        if ($tag === null) {
            Response::json(['message' => 'Tag tidak ditemukan'], 404);
            return;
        }

        Response::json($tag);
    }

    #[OA\Post(
        path: '/tag',
        summary: 'Buat tag baru (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TagCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tag berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Tag')
            ),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    public function store(): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return; 
        }
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $name = trim($payload['name'] ?? '');

        if ($name === '') {
            Response::json(['message' => 'Field name wajib diisi'], 422);
            return;
        }

        if ($this->tag->nameExists($name)) {
            Response::json(['message' => 'tag dengan nama tersebut sudah ada'], 409);
            return;
        }

        try {
            $tag = $this->tag->create($name);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal membuat tag', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($tag, 201);
    }

    #[OA\Put(
        path: '/tag/{id}',
        summary: 'Perbarui tag (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TagUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Tag')
            ),
            new OA\Response(response: 404, description: 'Tag tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    public function update(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return; 
        }
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $existing = $this->tag->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Tag tidak ditemukan'], 404);
            return;
        }

        $name = trim($payload['name'] ?? $existing['name']);

        if ($name === '') {
            Response::json(['message' => 'Field name wajib diisi'], 422);
            return;
        }

        if ($this->tag->nameExists($name, $id)) {
            Response::json(['message' => 'Tag dengan nama tersebut sudah ada'], 409);
            return;
        }

        try {
            $tag = $this->tag->update($id, $name);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui tag', 'error' => $exception->getMessage()], 500);
            return;
        }

        if ($tag === null) {
            Response::json(['message' => 'Tag tidak ditemukan'], 404);
            return;
        }

        Response::json($tag);
    }

    #[OA\Delete(
        path: '/tag/{id}',
        summary: 'Hapus tag (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Tag berhasil dihapus'),
            new OA\Response(response: 404, description: 'Tag tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    public function destroy(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return; 
        }
        $deleted = $this->tag->delete($id);

        if (!$deleted) {
            Response::json(['message' => 'Tag tidak ditemukan'], 404);
            return;
        }

        Response::empty(204);
    }
}