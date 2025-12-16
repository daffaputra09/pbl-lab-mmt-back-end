<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Middleware\AuthMiddleware;
use App\Models\PartnerLab;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Partner Lab')]
class PartnerLabController
{
    private PartnerLab $partnerLab;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->partnerLab = new PartnerLab($connection);
    }

    #[OA\Get(
        path: '/partner-lab',
        summary: 'List semua partner lab dengan pagination',
        tags: ['Partner Lab'],
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
                description: 'Search partner lab berdasarkan nama atau deskripsi (case-insensitive, partial match)',
                schema: new OA\Schema(type: 'string', example: 'Partner')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar partner lab dengan pagination',
                content: new OA\JsonContent(ref: '#/components/schemas/PartnerLabPaginatedResponse')
            ),
            new OA\Response(response: 400, description: 'Parameter tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengambil data partner lab')
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

            $total = $this->partnerLab->count($search);

            if ($limit === null) {
                $data = $this->partnerLab->all($search);
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->partnerLab->paginate($page, $limit, $search);

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
            Response::json(['message' => 'Gagal mengambil data partner lab', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/partner-lab/{id}',
        summary: 'Detail partner lab',
        tags: ['Partner Lab'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail partner lab',
                content: new OA\JsonContent(ref: '#/components/schemas/PartnerLab')
            ),
            new OA\Response(response: 404, description: 'Partner lab tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        try {
            $partnerLab = $this->partnerLab->find($id);

            if ($partnerLab === null) {
                Response::json(['message' => 'Partner lab tidak ditemukan'], 404);
                return;
            }

            Response::json($partnerLab);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data partner lab', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/partner-lab',
        summary: 'Buat partner lab baru (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Partner Lab'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/PartnerLabCreateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Partner lab berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/PartnerLab')
            ),
            new OA\Response(response: 422, description: 'Nama wajib diisi'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat partner lab')
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
        $deskripsi = isset($formData['deskripsi']) && $formData['deskripsi'] !== '' ? trim($formData['deskripsi']) : null;

        if ($nama === '') {
            Response::json(['message' => 'Field nama wajib diisi'], 422);
            return;
        }

        // Handle file upload
        $imageUrl = null;
        if (Request::hasFile('image')) {
            try {
                $file = Request::getFile('image');
                $imageUrl = FileUploadHelper::uploadImage($file, 'uploads/partner-lab');
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                return;
            }
        }

        try {
            $partnerLab = $this->partnerLab->create($nama, $deskripsi, $imageUrl);
        } catch (PDOException $exception) {
            // Rollback: hapus file jika gagal insert ke database
            if ($imageUrl) {
                FileUploadHelper::deleteFile($imageUrl);
            }
            Response::json(['message' => 'Gagal membuat partner lab', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($partnerLab, 201);
    }

    #[OA\Put(
        path: '/partner-lab/{id}',
        summary: 'Perbarui partner lab (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Partner Lab'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PartnerLabUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Partner lab berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/PartnerLab')
            ),
            new OA\Response(response: 404, description: 'Partner lab tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/partner-lab/{id}',
        summary: 'Perbarui partner lab (dengan upload gambar, requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Partner Lab'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/PartnerLabUpdateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Partner lab berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/PartnerLab')
            ),
            new OA\Response(response: 404, description: 'Partner lab tidak ditemukan'),
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

        $existing = $this->partnerLab->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Partner lab tidak ditemukan'], 404);
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
            $deskripsi = isset($formData['deskripsi']) && $formData['deskripsi'] !== '' ? trim($formData['deskripsi']) : $existing['deskripsi'];
            $imageUrl = FileUploadHelper::getRelativePath($existing['image_url']);

            // Handle file upload jika ada file baru
            if (Request::hasFile('image')) {
                try {
                    $file = Request::getFile('image');
                    $newImageUrl = FileUploadHelper::uploadImage($file, 'uploads/partner-lab');
                    
                    // Hapus file lama jika ada
                    if ($imageUrl) {
                        FileUploadHelper::deleteFile($imageUrl);
                    }
                    
                    $imageUrl = $newImageUrl;
                } catch (InvalidArgumentException $exception) {
                    Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            try {
                $partnerLab = $this->partnerLab->update($id, $nama, $deskripsi, $imageUrl);
            } catch (PDOException $exception) {
                // Rollback: hapus file baru jika gagal update
                if (Request::hasFile('image') && $imageUrl) {
                    FileUploadHelper::deleteFile($imageUrl);
                }
                Response::json(['message' => 'Gagal memperbarui partner lab', 'error' => $exception->getMessage()], 500);
                return;
            }

            Response::json($partnerLab);
            return;
        }

        // Jika request JSON
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $nama = isset($payload['nama']) ? trim($payload['nama']) : $existing['nama'];
        $deskripsi = isset($payload['deskripsi']) && $payload['deskripsi'] !== '' ? trim($payload['deskripsi']) : $existing['deskripsi'];
        $imageUrl = isset($payload['image_url']) ? trim($payload['image_url']) : FileUploadHelper::getRelativePath($existing['image_url']);

        try {
            $partnerLab = $this->partnerLab->update($id, $nama, $deskripsi, $imageUrl);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui partner lab', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($partnerLab);
    }

    #[OA\Delete(
        path: '/partner-lab/{id}',
        summary: 'Hapus partner lab (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Partner Lab'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Partner lab berhasil dihapus'),
            new OA\Response(response: 404, description: 'Partner lab tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid'),
            new OA\Response(response: 500, description: 'Gagal menghapus partner lab')
        ]
    )]
    public function destroy(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return;
        }

        $existing = $this->partnerLab->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Partner lab tidak ditemukan'], 404);
            return;
        }

        try {
            $deleted = $this->partnerLab->delete($id);
            
            if (!$deleted) {
                Response::json(['message' => 'Partner lab tidak ditemukan'], 404);
                return;
            }

            // Hapus file gambar jika ada
            if (isset($existing['image_url'])) {
                $imagePath = FileUploadHelper::getRelativePath($existing['image_url']);
                if ($imagePath) {
                    FileUploadHelper::deleteFile($imagePath);
                }
            }

            Response::empty(204);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus partner lab', 'error' => $exception->getMessage()], 500);
        }
    }
}

