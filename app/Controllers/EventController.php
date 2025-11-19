<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Models\Event;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Event')]
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
        summary: 'List semua event dengan pagination',
        tags: ['Event'],
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
                description: 'Daftar event dengan pagination',
                content: new OA\JsonContent(ref: '#/components/schemas/EventPaginatedResponse')
            ),
            new OA\Response(response: 400, description: 'Parameter tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengambil data event')
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

            $total = $this->event->count();

            if ($limit === null) {
                $data = $this->event->all();
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->event->paginate($page, $limit);

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
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/EventCreateRequest')
            )
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
        // Get form data
        try {
            $formData = Request::getFormData();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }
        
        $judul = trim($formData['judul'] ?? '');
        $description = trim($formData['description'] ?? '');

        if ($judul === '') {
            Response::json(['message' => 'Field judul wajib diisi'], 422);
            return;
        }

        if ($this->event->judulExists($judul)) {
            Response::json(['message' => 'Event dengan judul tersebut sudah ada'], 409);
            return;
        }

        // Handle file upload
        $imageUrl = null;
        if (Request::hasFile('image')) {
            try {
                $file = Request::getFile('image');
                $imageUrl = FileUploadHelper::uploadImage($file, 'uploads/event');
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                return;
            }
        }

        try {
            $event = $this->event->create($judul, $description ?: null, $imageUrl);
        } catch (PDOException $exception) {
            // Rollback: hapus file jika gagal insert ke database
            if ($imageUrl) {
                FileUploadHelper::deleteFile($imageUrl);
            }
            Response::json(['message' => 'Gagal membuat event', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($event, 201);
    }

    // UPDATE

    #[OA\Post(
        path: '/event/{id}',
        summary: 'Perbarui event',
        tags: ['Event'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/EventUpdateRequest')
            )
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
        $existing = $this->event->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Event tidak ditemukan'], 404);
            return;
        }

        $formData = Request::getFormData();

        $judul = isset($formData['judul']) ? trim($formData['judul']) : $existing['judul'];
        $description = isset($formData['description']) ? trim($formData['description']) : $existing['description'];
        $imageUrl = $existing['image_url'];

        $newImageUrl = null;
        if (Request::hasFile('image')) {
            try {
                $file = Request::getFile('image');
                $newImageUrl = FileUploadHelper::uploadImage($file, 'uploads/event');
                if ($imageUrl) {
                    FileUploadHelper::deleteFile($imageUrl);
                }
                $imageUrl = $newImageUrl;
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                return;
            }
        }

        $judul = trim($judul);
        $description = $description ? trim($description) : null;

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
            if ($newImageUrl) {
                FileUploadHelper::deleteFile($newImageUrl);
            }
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
            $event = $this->event->find($id);
            if ($event === null) {
                Response::json(['message' => 'Event tidak ditemukan'], 404);
                return;
            }

            $deleted = $this->event->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Event tidak ditemukan'], 404);
                return;
            }

            if ($event['image_url']) {
                FileUploadHelper::deleteFile($event['image_url']);
            }
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus event', 'error' => $exception->getMessage()], 500);
            return;
        }
        Response::empty(204);
    }
}