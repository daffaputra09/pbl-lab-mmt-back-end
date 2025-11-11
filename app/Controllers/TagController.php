<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
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
        summary: 'List semua tag',
        tags: ['Tag'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar tag',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Tag')
                )
            )
        ]
    )]
    public function index(): void
    {
        Response::json($this->tag->all());
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
        summary: 'Buat tag baru',
        tags: ['Tag'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TagCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'tag berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Tag')
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
        summary: 'Perbarui tag',
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
            new OA\Response(response: 404, description: 'Tag tidak ditemukan')
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
        summary: 'Hapus tag',
        tags: ['Tag'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Tag berhasil dihapus'),
            new OA\Response(response: 404, description: 'Tag tidak ditemukan')
        ]
    )]
    public function destroy(int $id): void
    {
        $deleted = $this->tag->delete($id);

        if (!$deleted) {
            Response::json(['message' => 'Tag tidak ditemukan'], 404);
            return;
        }

        Response::empty(204);
    }
}