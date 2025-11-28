<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Middleware\AuthMiddleware;
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
            ),
            new OA\Parameter(
                name: 'sort_by_date',
                in: 'query',
                required: false,
                description: 'Jika true, akan menampilkan semua event dengan prioritas: event yang belum terlewat tampil di atas (diurutkan berdasarkan tanggal terdekat), kemudian event yang terlewat. Jika false, akan mengurutkan berdasarkan data terbaru (id DESC). Jika tidak diisi, default adalah data terbaru.',
                schema: new OA\Schema(type: 'boolean', example: true)
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Mencari event berdasarkan title atau description (case insensitive)',
                schema: new OA\Schema(type: 'string', example: 'workshop')
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
            $sortByDateParam = Request::getQuery('sort_by_date', null);
            $sortByDate = $sortByDateParam !== null ? filter_var($sortByDateParam, FILTER_VALIDATE_BOOLEAN) : null;
            $search = Request::getQuery('search', null);
            $search = $search !== null && $search !== '' ? trim($search) : null;

            if ($page < 1) {
                Response::json(['message' => 'Parameter page harus lebih besar dari 0'], 400);
                return;
            }

            if ($limit !== null && $limit < 1) {
                Response::json(['message' => 'Parameter limit harus lebih besar dari 0'], 400);
                return;
            }

            $total = $this->event->count($search, $sortByDate);

            if ($limit === null) {
                $data = $this->event->all($sortByDate, $search);
                $data = array_map([$this->event, 'addEventStatus'], $data);
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->event->paginate($page, $limit, $sortByDate, $search);
            $data = array_map([$this->event, 'addEventStatus'], $data);

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

            // Tambahkan status ke event
            $event = $this->event->addEventStatus($event);

            Response::json($event);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data event', 'error' => $exception->getMessage()], 500);
        }
    }
    
    #[OA\Get(
        path: '/event/recent',
        summary: 'Daftar event yang akan datang (belum dilaksanakan)',
        tags: ['Event'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar semua event yang tanggal_event nya di atas hari ini',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Event')
                )
            )
        ]
    )]
    public function recent(): void
    {
        try {
            $data = $this->event->recent(null);
            $data = array_map([$this->event, 'addEventStatus'], $data);
            Response::json($data);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data event yang akan datang', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/event/recent/{limit}',
        summary: 'Daftar event yang akan datang dengan limit',
        tags: ['Event'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 5))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar event yang tanggal_event nya di atas hari ini (dibatasi jumlahnya)',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Event')
                )
            )
        ]
    )]
    public function recentWithLimit(int $limit): void
    {
        try {
            if ($limit < 1) {
                Response::json(['message' => 'Parameter limit harus lebih besar dari 0'], 400);
                return;
            }
            $data = $this->event->recent($limit);
            $data = array_map([$this->event, 'addEventStatus'], $data);
            Response::json($data);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data event yang akan datang', 'error' => $exception->getMessage()], 500);
        }
    }

    // CREATE

    #[OA\Post(
        path: '/event',
        summary: 'Buat event baru (requires authentication)',
        security: [['bearerAuth' => []]],
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
            new OA\Response(response: 422, description: 'Title wajib diisi'),
            new OA\Response(response: 409, description: 'Event dengan title tersebut sudah ada'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat event')
        ]
    )]
    public function store(): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return; 
        }
        // Get form data
        try {
            $formData = Request::getFormData();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }
        
        // Support both 'title' and 'judul' field names, prefer 'title'
        $judul = trim($formData['title'] ?? $formData['judul'] ?? '');
        $description = trim($formData['description'] ?? '');
        $tanggalEvent = isset($formData['tanggal_event']) && $formData['tanggal_event'] !== '' ? trim($formData['tanggal_event']) : null;

        if ($judul === '') {
            Response::json(['message' => 'Field title wajib diisi'], 422);
            return;
        }

        if ($this->event->judulExists($judul)) {
            Response::json(['message' => 'Event dengan title tersebut sudah ada'], 409);
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
            $event = $this->event->create($judul, $description ?: null, $imageUrl, $tanggalEvent);
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

    #[OA\Put(
        path: '/event/{id}',
        summary: 'Perbarui event (requires authentication)',
        security: [['bearerAuth' => []]],
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
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/event/{id}',
        summary: 'Perbarui event (dengan upload gambar, requires authentication)',
        security: [['bearerAuth' => []]],
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
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    public function update(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return;
        }
        $existing = $this->event->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Event tidak ditemukan'], 404);
            return;
        }

        // Jika request multipart (upload file)
        if (Request::isMultipart()) {
            try {
                $formData = Request::getFormData();
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => $exception->getMessage()], 400);
                return;
            }

            // Support both 'title' and 'judul' field names, prefer 'title'
            // Also handle existing data which now has 'title' instead of 'judul'
            $existingTitle = $existing['title'] ?? $existing['judul'] ?? '';
            $judul = isset($formData['title']) ? trim($formData['title']) : (isset($formData['judul']) ? trim($formData['judul']) : $existingTitle);
            $description = isset($formData['description']) ? trim($formData['description']) : $existing['description'];
            $tanggalEvent = isset($formData['tanggal_event']) && $formData['tanggal_event'] !== '' ? trim($formData['tanggal_event']) : ($existing['tanggal_event'] ?? null);
            $imageUrl = FileUploadHelper::getRelativePath($existing['image_url']);

            $newImageUrl = null;
            if (Request::hasFile('image')) {
                try {
                    $file = Request::getFile('image');
                    if ($file === null) {
                        Response::json(['message' => 'File image tidak ditemukan.'], 400);
                        return;
                    }
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
                Response::json(['message' => 'Field title wajib diisi'], 422);
                return;
            }

            if ($this->event->judulExists($judul, $id)) {
                Response::json(['message' => 'Event dengan title tersebut sudah ada'], 409);
                return;
            }

            try {
                $event = $this->event->update($id, $judul, $description, $imageUrl, $tanggalEvent);
            } catch (PDOException $exception) {
                if ($newImageUrl) {
                    FileUploadHelper::deleteFile($newImageUrl);
                }
                Response::json(['message' => 'Gagal memperbarui event', 'error' => $exception->getMessage()], 500);
                return;
            }

            if ($event === null) {
                Response::json(['message' => 'Event tidak ditemukan saat update'], 404);
                return;
            }

            Response::json($event);
            return;
        }

        // Fallback: dukung format JSON (image_url string)
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $mergedPayload = array_merge($existing, $payload);

        try {
            $data = $this->validatePayload($mergedPayload, true);
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $existingTitle = $existing['title'] ?? $existing['judul'] ?? null;
        $judul = $data['title'] ?? $data['judul'] ?? $existingTitle;
        $description = $data['description'] ?? $existing['description'];
        $imageUrl = isset($data['image_url']) 
            ? FileUploadHelper::getRelativePath($data['image_url']) 
            : FileUploadHelper::getRelativePath($existing['image_url']);
        $tanggalEvent = $data['tanggal_event'] ?? $existing['tanggal_event'] ?? null;

        if ($judul === null || $judul === '') {
            Response::json(['message' => 'Title tidak boleh kosong.'], 400);
            return;
        }

        if ($this->event->judulExists($judul, $id)) {
            Response::json(['message' => 'Event dengan title tersebut sudah ada'], 409);
            return;
        }

        try {
            $event = $this->event->update($id, $judul, $description, $imageUrl, $tanggalEvent);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui event', 'error' => $exception->getMessage()], 500);
            return;
        }

        if ($event === null) {
            Response::json(['message' => 'Event tidak ditemukan saat update'], 404);
            return;
        }

        Response::json($event);
    }

    // DELETE

    #[OA\Delete(
        path: '/event/{id}',
        summary: 'Hapus event (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Event'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Event berhasil dihapus'),
            new OA\Response(response: 404, description: 'Event tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    public function destroy(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return;
        }
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

    // VALIDASI PAYLOAD
    private function validatePayload(array $payload, bool $isUpdate = false): array
    {
        // Support both 'title' and 'judul' field names
        $title = $payload['title'] ?? $payload['judul'] ?? null;
        
        if (!$isUpdate) {
            if (empty($title)) {
                throw new InvalidArgumentException("Kolom title wajib diisi.");
            }
        }

        return [
            'title' => $title,
            'judul' => $title, // Keep for backward compatibility
            'description' => $payload['description'] ?? null,
            'image_url' => $payload['image_url'] ?? null,
            'tanggal_event' => $payload['tanggal_event'] ?? null,
        ];
    }
}