<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Middleware\AuthMiddleware;
use App\Models\Anggota;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Anggota')]
class AnggotaController
{
    private Anggota $anggota;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->anggota = new Anggota($connection);
    }

    #[OA\Get(
        path: '/anggota',
        summary: 'List semua anggota dengan pagination',
        tags: ['Anggota'],
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
                name: 'search',
                in: 'query',
                required: false,
                description: 'Search anggota berdasarkan nama (case-insensitive, partial match)',
                schema: new OA\Schema(type: 'string', example: 'John')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar anggota dengan pagination',
                content: new OA\JsonContent(ref: '#/components/schemas/AnggotaPaginatedResponse')
            ),
            new OA\Response(response: 400, description: 'Parameter tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengambil data anggota')
        ]
    )]
    public function index(): void
    {
        try {
            $page = (int) Request::getQuery('page', 1);
            $limitParam = Request::getQuery('limit', null);
            $limit = $limitParam !== null ? (int) $limitParam : null;
            $search = Request::getQuery('search', null);
            if ($search !== null && $search !== '') {
                $search = trim($search);
            } else {
                $search = null;
            }

            if ($page < 1) {
                Response::json(['message' => 'Parameter page harus lebih besar dari 0'], 400);
                return;
            }

            if ($limit !== null && $limit < 1) {
                Response::json(['message' => 'Parameter limit harus lebih besar dari 0'], 400);
                return;
            }

            $total = $this->anggota->count($search);

            if ($limit === null) {
                $data = $this->anggota->all($search);
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->anggota->paginate($page, $limit, $search);

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
            Response::json(['message' => 'Gagal mengambil data anggota', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/anggota/{id}',
        summary: 'Detail anggota',
        tags: ['Anggota'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail anggota',
                content: new OA\JsonContent(ref: '#/components/schemas/Anggota')
            ),
            new OA\Response(response: 404, description: 'Anggota tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        try {
            $anggota = $this->anggota->find($id);

            if ($anggota === null) {
                Response::json(['message' => 'Anggota tidak ditemukan'], 404);
                return;
            }

            Response::json($anggota);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data anggota', 'error' => $exception->getMessage()], 500);
        }
    }

    // CREATE

    #[OA\Post(
        path: '/anggota',
        summary: 'Buat anggota baru (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Anggota'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/AnggotaCreateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Anggota berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Anggota')
            ),
            new OA\Response(response: 422, description: 'Nama wajib diisi'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat anggota')
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

        $nama = trim($formData['nama'] ?? '');
        $role = isset($formData['role']) && $formData['role'] !== '' ? trim($formData['role']) : null;
        
        // Parse skills from JSON string or array
        $skills = null;
        if (isset($formData['skills'])) {
            if (is_string($formData['skills'])) {
                $skills = json_decode($formData['skills'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $skills = [$formData['skills']]; // Fallback to single item array
                }
            } elseif (is_array($formData['skills'])) {
                $skills = $formData['skills'];
            }
        }
        
        // Parse media_social from JSON string or array (array of objects with url and type)
        $mediaSocial = null;
        if (isset($formData['media_social'])) {
            if (is_string($formData['media_social'])) {
                $mediaSocial = json_decode($formData['media_social'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Response::json(['message' => 'Format media_social tidak valid. Harus berupa array of objects dengan url dan type.'], 422);
                    return;
                }
            } elseif (is_array($formData['media_social'])) {
                $mediaSocial = $formData['media_social'];
            }
            
            // Validate structure: each item must be an object with url and type
            if ($mediaSocial !== null) {
                foreach ($mediaSocial as $index => $item) {
                    if (!is_array($item) || !isset($item['url']) || !isset($item['type'])) {
                        Response::json(['message' => "media_social item pada index {$index} harus berupa object dengan property 'url' dan 'type'"], 422);
                        return;
                    }
                }
            }
        }

        if ($nama === '') {
            Response::json(['message' => 'Field nama wajib diisi'], 422);
            return;
        }

        // Handle file upload
        $imageUrl = null;
        if (Request::hasFile('image')) {
            try {
                $file = Request::getFile('image');
                $imageUrl = FileUploadHelper::uploadImage($file, 'uploads/anggota');
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                return;
            }
        }

        try {
            $anggota = $this->anggota->create($nama, $role, $imageUrl, $skills, $mediaSocial);
        } catch (PDOException $exception) {
            // Rollback: hapus file jika gagal insert ke database
            if ($imageUrl) {
                FileUploadHelper::deleteFile($imageUrl);
            }
            Response::json(['message' => 'Gagal membuat anggota', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($anggota, 201);
    }

    // UPDATE

    #[OA\Put(
        path: '/anggota/{id}',
        summary: 'Perbarui anggota (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Anggota'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AnggotaUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Anggota berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Anggota')
            ),
            new OA\Response(response: 404, description: 'Anggota tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/anggota/{id}',
        summary: 'Perbarui anggota (dengan upload gambar, requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Anggota'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/AnggotaUpdateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Anggota berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Anggota')
            ),
            new OA\Response(response: 404, description: 'Anggota tidak ditemukan'),
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

        $existing = $this->anggota->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Anggota tidak ditemukan'], 404);
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

            $nama = isset($formData['nama']) ? trim($formData['nama']) : $existing['nama'];
            $role = isset($formData['role']) && $formData['role'] !== '' ? trim($formData['role']) : $existing['role'];
            $imageUrl = FileUploadHelper::getRelativePath($existing['image_url']);

            // Parse skills
            $skills = $existing['skills'];
            if (isset($formData['skills'])) {
                if (is_string($formData['skills'])) {
                    $skills = json_decode($formData['skills'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $skills = [$formData['skills']];
                    }
                } elseif (is_array($formData['skills'])) {
                    $skills = $formData['skills'];
                }
            }

            // Parse media_social
            $mediaSocial = $existing['media_social'];
            if (isset($formData['media_social'])) {
                if (is_string($formData['media_social'])) {
                    $mediaSocial = json_decode($formData['media_social'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $mediaSocial = [$formData['media_social']];
                    }
                } elseif (is_array($formData['media_social'])) {
                    $mediaSocial = $formData['media_social'];
                }
            }

            $newImageUrl = null;
            if (Request::hasFile('image')) {
                try {
                    $file = Request::getFile('image');
                    if ($file === null) {
                        Response::json(['message' => 'File image tidak ditemukan.'], 400);
                        return;
                    }
                    $newImageUrl = FileUploadHelper::uploadImage($file, 'uploads/anggota');
                    if ($imageUrl) {
                        FileUploadHelper::deleteFile($imageUrl);
                    }
                    $imageUrl = $newImageUrl;
                } catch (InvalidArgumentException $exception) {
                    Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            $nama = trim($nama);

            if ($nama === '') {
                Response::json(['message' => 'Field nama wajib diisi'], 422);
                return;
            }

            try {
                $anggota = $this->anggota->update($id, $nama, $role, $imageUrl, $skills, $mediaSocial);
            } catch (PDOException $exception) {
                if ($newImageUrl) {
                    FileUploadHelper::deleteFile($newImageUrl);
                }
                Response::json(['message' => 'Gagal memperbarui anggota', 'error' => $exception->getMessage()], 500);
                return;
            }

            if ($anggota === null) {
                Response::json(['message' => 'Anggota tidak ditemukan saat update'], 404);
                return;
            }

            Response::json($anggota);
            return;
        }

        // Fallback: dukung format JSON
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $mergedPayload = array_merge($existing, $payload);

        $nama = trim($mergedPayload['nama'] ?? $existing['nama']);
        $role = isset($mergedPayload['role']) && $mergedPayload['role'] !== '' ? trim($mergedPayload['role']) : $existing['role'];
        $imageUrl = isset($mergedPayload['image_url']) 
            ? FileUploadHelper::getRelativePath($mergedPayload['image_url']) 
            : FileUploadHelper::getRelativePath($existing['image_url']);
        $skills = $mergedPayload['skills'] ?? $existing['skills'];
        $mediaSocial = $mergedPayload['media_social'] ?? $existing['media_social'];

        // Validate media_social structure if provided
        if (isset($mergedPayload['media_social']) && $mediaSocial !== null) {
            foreach ($mediaSocial as $index => $item) {
                if (!is_array($item) || !isset($item['url']) || !isset($item['type'])) {
                    Response::json(['message' => "media_social item pada index {$index} harus berupa object dengan property 'url' dan 'type'"], 422);
                    return;
                }
            }
        }

        if ($nama === null || $nama === '') {
            Response::json(['message' => 'Nama tidak boleh kosong.'], 400);
            return;
        }

        try {
            $anggota = $this->anggota->update($id, $nama, $role, $imageUrl, $skills, $mediaSocial);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui anggota', 'error' => $exception->getMessage()], 500);
            return;
        }

        if ($anggota === null) {
            Response::json(['message' => 'Anggota tidak ditemukan saat update'], 404);
            return;
        }

        Response::json($anggota);
    }

    // DELETE

    #[OA\Delete(
        path: '/anggota/{id}',
        summary: 'Hapus anggota (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Anggota'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Anggota berhasil dihapus'),
            new OA\Response(response: 404, description: 'Anggota tidak ditemukan'),
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
            $anggota = $this->anggota->find($id);
            if ($anggota === null) {
                Response::json(['message' => 'Anggota tidak ditemukan'], 404);
                return;
            }

            $deleted = $this->anggota->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Anggota tidak ditemukan'], 404);
                return;
            }

            if ($anggota['image_url']) {
                FileUploadHelper::deleteFile($anggota['image_url']);
            }
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus anggota', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::empty(204);
    }
}

