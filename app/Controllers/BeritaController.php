<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Models\Berita;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Berita')]
class BeritaController
{
    private Berita $berita;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->berita = new Berita($connection);
    }
    
    #[OA\Get(
        path: '/berita',
        summary: 'List semua entri berita',
        tags: ['Berita'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar entri berita',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Berita')
                )
            )
        ]
    )]
    public function index(): void
    {
        try {
            Response::json($this->berita->all());
        } catch (PDOException $exception) {
            Response::json([
                'message' => 'Gagal mengambil data berita',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    // CREATE
    #[OA\Get(
        path: '/berita/{id}',
        summary: 'Detail entri berita berdasarkan ID',
        tags: ['Berita'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail entri berita',
                content: new OA\JsonContent(ref: '#/components/schemas/Berita')
            ),
            new OA\Response(response: 404, description: 'Entri berita tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        try {
            $entry = $this->berita->find($id);

            if ($entry === null) {
                Response::json(['message' => 'Entri berita tidak ditemukan'], 404);
                return;
            }

            Response::json($entry);
        } catch (PDOException $exception) {
            Response::json([
                'message' => 'Gagal mengambil data berita',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/berita',
        summary: 'Buat entri berita baru',
        tags: ['Berita'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/BeritaCreateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entri berita berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Berita')
            ),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat entri berita')
        ]
    )]
    public function store(): void
    {
        if (Request::isMultipart()) {
            try {
                $formData = Request::getFormData();
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => $exception->getMessage()], 400);
                return;
            }

            $judul = trim($formData['judul'] ?? '');
            $description = trim($formData['description'] ?? '');
            $idUser = isset($formData['id_user']) && $formData['id_user'] !== ''
                ? (int) $formData['id_user']
                : null;
            $status = $formData['status'] ?? 'published';

            if ($judul === '' || $description === '') {
                Response::json(['message' => 'Kolom judul dan description wajib diisi.'], 422);
                return;
            }

            if (!Request::hasFile('image')) {
                Response::json(['message' => 'Field image wajib diisi.'], 422);
                return;
            }

            try {
                $file = Request::getFile('image');
                if ($file === null) {
                    Response::json(['message' => 'File image tidak ditemukan.'], 400);
                    return;
                }
                $imageUrl = FileUploadHelper::uploadImage($file, 'uploads/berita');
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                return;
            }

            try {
                $entry = $this->berita->create(
                    $judul,
                    $description,
                    $imageUrl,
                    $idUser,
                    $status
                );
            } catch (PDOException $exception) {
                // Rollback file jika gagal insert ke database
                FileUploadHelper::deleteFile($imageUrl);
                Response::json(['message' => 'Gagal membuat entri berita', 'error' => $exception->getMessage()], 500);
                return;
            }

            Response::json($entry, 201);
            return;
        }

        try {
            $payload = Request::json();
            $data = $this->validatePayload($payload);
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        try {
            $entry = $this->berita->create(
                $data['judul'],
                $data['description'],
                $data['image_url'],
                $data['id_user'] ?? null,
                $data['status'] ?? 'published'
            );
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal membuat entri berita', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($entry, 201);
    }

    // UPDATE
    #[OA\Put(
        path: '/berita/{id}',
        summary: 'Perbarui entri berita',
        tags: ['Berita'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BeritaUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entri berita berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Berita')
            ),
            new OA\Response(response: 404, description: 'Entri berita tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/berita/{id}',
        summary: 'Perbarui entri berita (dengan upload gambar)',
        tags: ['Berita'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/BeritaUpdateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entri berita berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Berita')
            ),
            new OA\Response(response: 404, description: 'Entri berita tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
        ]
    )]
    public function update(int $id): void
    {
        $existing = $this->berita->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Entri berita tidak ditemukan'], 404);
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

            $judul = isset($formData['judul']) ? trim($formData['judul']) : $existing['judul'];
            $description = isset($formData['description']) ? trim($formData['description']) : $existing['description'];
            $idUser = isset($formData['id_user']) && $formData['id_user'] !== ''
                ? (int) $formData['id_user']
                : $existing['id_user'];
            $status = $formData['status'] ?? $existing['status'];
            $imageUrl = $existing['image_url'];

            $newImageUrl = null;
            if (Request::hasFile('image')) {
                try {
                    $file = Request::getFile('image');
                    if ($file === null) {
                        Response::json(['message' => 'File image tidak ditemukan.'], 400);
                        return;
                    }
                    $newImageUrl = FileUploadHelper::uploadImage($file, 'uploads/berita');
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
            $description = $description !== null ? trim($description) : null;

            if ($judul === '' || $description === null || $imageUrl === null) {
                Response::json(['message' => 'Judul, description, dan gambar tidak boleh kosong.'], 422);
                return;
            }

            try {
                $entry = $this->berita->update(
                    $id,
                    $judul,
                    $description,
                    $imageUrl,
                    $idUser,
                    $status
                );
            } catch (PDOException $exception) {
                if ($newImageUrl) {
                    FileUploadHelper::deleteFile($newImageUrl);
                }
                Response::json(['message' => 'Gagal memperbarui entri berita', 'error' => $exception->getMessage()], 500);
                return;
            }

            if ($entry === null) {
                Response::json(['message' => 'Entri berita tidak ditemukan saat update'], 404);
                return;
            }

            Response::json($entry);
            return;
        }

        // Fallback: dukung format JSON lama (image_url string)
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

        $judul = $data['judul'] ?? $existing['judul'];
        $description = $data['description'] ?? $existing['description'];
        $imageUrl = $data['image_url'] ?? $existing['image_url'];
        $idUser = $data['id_user'] ?? $existing['id_user'];
        $status = $data['status'] ?? $existing['status'];

        if ($judul === null || $imageUrl === null) {
            Response::json(['message' => 'Judul dan URL gambar tidak boleh kosong.'], 400);
            return;
        }

        try {
            $entry = $this->berita->update(
                $id,
                $judul,
                $description,
                $imageUrl,
                $idUser,
                $status
            );
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui entri berita', 'error' => $exception->getMessage()], 500);
            return;
        }

        if ($entry === null) {
            Response::json(['message' => 'Entri berita tidak ditemukan saat update'], 404);
            return;
        }

        Response::json($entry);
    }

    // DELETE
    #[OA\Delete(
        path: '/berita/{id}',
        summary: 'Hapus entri berita',
        tags: ['Berita'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Entri berita berhasil dihapus'),
            new OA\Response(response: 404, description: 'Entri berita tidak ditemukan')
        ]
    )]
    public function destroy(int $id): void
    {
        try {
            // Ambil data berita terlebih dahulu untuk mendapatkan image_url
            $entry = $this->berita->find($id);
            if ($entry === null) {
                Response::json(['message' => 'Entri berita tidak ditemukan'], 404);
                return;
            }

            $deleted = $this->berita->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Entri berita tidak ditemukan'], 404);
                return;
            }

            // Hapus file gambar jika ada
            if (!empty($entry['image_url'])) {
                FileUploadHelper::deleteFile($entry['image_url']);
            }
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus entri berita', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::empty(204);
    }

    // VALIDASI PAYLOAD
    private function validatePayload(array $payload, bool $isUpdate = false): array
    {
        $required = ['judul', 'description', 'image_url'];
        foreach ($required as $field) {
            if (!$isUpdate && empty($payload[$field])) {
                throw new InvalidArgumentException("Kolom {$field} wajib diisi.");
            }
        }

        return [
            'judul' => $payload['judul'] ?? null,
            'description' => $payload['description'] ?? null,
            'image_url' => $payload['image_url'] ?? null,
            'id_user' => $payload['id_user'] ?? null,
            'status' => $payload['status'] ?? 'published',
        ];
    }
}