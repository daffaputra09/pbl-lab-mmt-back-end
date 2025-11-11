<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Models\Event;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Event(name: 'Event')]
class EventController
{
    private Event $event;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->event = new Event($connection);
    }

    #[OA\Get(
        path: '/event',
        summary: 'List semua event',
        tags: ['Event'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar event',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Event')
                )
            )
        ]
    )]
    public function index(): void
    {
        try {
            Response::json($this->event->all());
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data event', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/event/{id}',
        summary: 'Detail event',
        tags: ['Event'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail event',
                content: new OA\JsonContent(ref: '#/components/schemas/Event')
            ),
            new OA\Response(response: 404, description: 'Event tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        try {
            $event = $this->event->find($id);

            if ($event === null) {
                Response::json(['message' => 'Event tidak ditemukan'], 404);
                return;
            }

            Response::json($event);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data event', 'error' => $exception->getMessage()], 500);
        }
    }
    
    #[OA\Get(
        path: '/event/recent/{limit}',
        summary: 'Daftar event terbaru',
        tags: ['Event'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'path', required: false, schema: new OA\Schema(type: 'integer', default: 5))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar event terbaru',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Event')
                )
            )
        ]
    )]
    public function recent(int $limit = 5): void
    {
        try {
            Response::json($this->event->recent($limit));
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data event terbaru', 'error' => $exception->getMessage()], 500);
        }
    }

    // CREATE

    #[OA\Post(
        path: '/event',
        summary: 'Buat event baru',
        tags: ['Event'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/EventCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Event berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Event')
            ),
            new OA\Response(response: 422, description: 'Judul wajib diisi'),
            new OA\Response(response: 409, description: 'Event dengan judul tersebut sudah ada'),
            new OA\Response(response: 500, description: 'Gagal membuat event')
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

        $judul = trim($payload['judul'] ?? '');
        $description = trim($payload['description'] ?? null);
        $imageUrl = trim($payload['image_url'] ?? null);

        if ($judul === '') {
            Response::json(['message' => 'Field judul wajib diisi'], 422);
            return;
        }

        if ($this->event->judulExists($judul)) {
            Response::json(['message' => 'Event dengan judul tersebut sudah ada'], 409);
            return;
        }

        try {
            $event = $this->event->create($judul, $description ?: null, $imageUrl ?: null);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal membuat event', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($event, 201);
    }

    // UPDATE

    #[OA\Put(
        path: '/event/{id}',
        summary: 'Perbarui event',
        tags: ['Event'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/EventUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Event')
            ),
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
            new OA\Response(response: 422, description: 'Judul wajib diisi'),
            new OA\Response(response: 409, description: 'Event dengan judul tersebut sudah ada')
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

        $existing = $this->event->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Event tidak ditemukan'], 404);
            return;
        }

        // Ambil data dari payload atau gunakan data lama
        $judul = trim($payload['judul'] ?? $existing['judul']);
        $description = array_key_exists('description', $payload) ? $payload['description'] : $existing['description'];
        $imageUrl = array_key_exists('image_url', $payload) ? $payload['image_url'] : $existing['image_url'];

        // Trim string dan konversi ke null jika kosong
        $judul = trim($judul);
        $description = $description ? trim($description) : null;
        $imageUrl = $imageUrl ? trim($imageUrl) : null;

        if ($judul === '') {
            Response::json(['message' => 'Field judul wajib diisi'], 422);
            return;
        }

        if ($this->event->judulExists($judul, $id)) {
            Response::json(['message' => 'Event dengan judul tersebut sudah ada'], 409);
            return;
        }

        try {
            $event = $this->event->update($id, $judul, $description, $imageUrl);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui event', 'error' => $exception->getMessage()], 500);
            return;
        }

        if ($event === null) {
            Response::json(['message' => 'Gagal memperbarui event'], 500); 
            return;
        }

        Response::json($event);
    }

    // DELETE

    #[OA\Delete(
        path: '/event/{id}',
        summary: 'Hapus event',
        tags: ['Event'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Event berhasil dihapus'),
            new OA\Response(response: 404, description: 'Event tidak ditemukan')
        ]
    )]
    public function destroy(int $id): void
    {
        try {
            $deleted = $this->event->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Event tidak ditemukan'], 404);
                return;
            }
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus event', 'error' => $exception->getMessage()], 500);
            return;
        }
        Response::empty(204);
    }
}