<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Kategori;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Kategori')]
class KategoriController
{
    private Kategori $kategori;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->kategori = new Kategori($connection);
    }

    #[OA\Get(
        path: '/kategori',
        summary: 'List semua kategori dengan pagination',
        tags: ['Kategori'],
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
                description: 'Daftar kategori dengan pagination',
                content: new OA\JsonContent(ref: '#/components/schemas/KategoriPaginatedResponse')
            ),
            new OA\Response(response: 400, description: 'Parameter tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengambil data kategori')
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

            $total = $this->kategori->count();

            if ($limit === null) {
                $data = $this->kategori->all();
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->kategori->paginate($page, $limit);

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
            Response::json(['message' => 'Gagal mengambil data kategori', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/kategori/{id}',
        summary: 'Detail kategori',
        tags: ['Kategori'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail kategori',
                content: new OA\JsonContent(ref: '#/components/schemas/Kategori')
            ),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        $kategori = $this->kategori->find($id);

        if ($kategori === null) {
            Response::json(['message' => 'Kategori tidak ditemukan'], 404);
            return;
        }

        Response::json($kategori);
    }

    #[OA\Post(
        path: '/kategori',
        summary: 'Buat kategori baru (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Kategori'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KategoriCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kategori berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Kategori')
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

        if ($this->kategori->nameExists($name)) {
            Response::json(['message' => 'Kategori dengan nama tersebut sudah ada'], 409);
            return;
        }

        try {
            $kategori = $this->kategori->create($name);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal membuat kategori', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($kategori, 201);
    }

    #[OA\Put(
        path: '/kategori/{id}',
        summary: 'Perbarui kategori (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Kategori'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KategoriUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Kategori berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Kategori')
            ),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan'),
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

        $existing = $this->kategori->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Kategori tidak ditemukan'], 404);
            return;
        }

        $name = trim($payload['name'] ?? $existing['name']);

        if ($name === '') {
            Response::json(['message' => 'Field name wajib diisi'], 422);
            return;
        }

        if ($this->kategori->nameExists($name, $id)) {
            Response::json(['message' => 'Kategori dengan nama tersebut sudah ada'], 409);
            return;
        }

        try {
            $kategori = $this->kategori->update($id, $name);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui kategori', 'error' => $exception->getMessage()], 500);
            return;
        }

        if ($kategori === null) {
            Response::json(['message' => 'Kategori tidak ditemukan'], 404);
            return;
        }

        Response::json($kategori);
    }

    #[OA\Delete(
        path: '/kategori/{id}',
        summary: 'Hapus kategori (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Kategori'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Kategori berhasil dihapus'),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    public function destroy(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return;
        }
        $deleted = $this->kategori->delete($id);

        if (!$deleted) {
            Response::json(['message' => 'Kategori tidak ditemukan'], 404);
            return;
        }

        Response::empty(204);
    }
}

