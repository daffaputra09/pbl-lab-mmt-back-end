<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
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
        summary: 'List semua kategori',
        tags: ['Kategori'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar kategori',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Kategori')
                )
            )
        ]
    )]
    public function index(): void
    {
        Response::json($this->kategori->all());
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
        summary: 'Buat kategori baru',
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
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
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
        summary: 'Perbarui kategori',
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
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan')
        ]
    )]
    public function update(int $id): void
    {
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
        summary: 'Hapus kategori',
        tags: ['Kategori'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Kategori berhasil dihapus'),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan')
        ]
    )]
    public function destroy(int $id): void
    {
        $deleted = $this->kategori->delete($id);

        if (!$deleted) {
            Response::json(['message' => 'Kategori tidak ditemukan'], 404);
            return;
        }

        Response::empty(204);
    }
}

