<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Models\Project;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Project')]
class ProjectController
{
    private Project $project;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->project = new Project($connection);
    }

    #[OA\Get(
        path: '/project',
        summary: 'List semua entri proyek (dapat difilter berdasarkan id_kategori)',
        tags: ['Project'],
        parameters: [
            new OA\Parameter(
                name: 'id_kategori',
                in: 'query',
                required: false,
                description: 'Filter proyek berdasarkan id_kategori',
                schema: new OA\Schema(type: 'integer', example: 3)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar entri proyek',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Project')
                )
            )
        ]
    )]
    
    public function index(): void
    {
        try {
            $idKategori = isset($_GET['id_kategori']) ? (int) $_GET['id_kategori'] : null; // konversi ke integer
            $projects = $this->project->all($idKategori);
            Response::json($projects);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data proyek', 'error' => $exception->getMessage()], 500);
        }
    }


    #[OA\Get(
        path: '/project/{id}',
        summary: 'Detail entri proyek berdasarkan ID',
        tags: ['Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail entri proyek',
                content: new OA\JsonContent(ref: '#/components/schemas/Project')
            ),
            new OA\Response(response: 404, description: 'Entri proyek tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        try {
            $entry = $this->project->find($id);

            if ($entry === null) {
                Response::json(['message' => 'Entri proyek tidak ditemukan'], 404);
                return;
            }

            Response::json($entry);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data proyek', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/project',
        summary: 'Buat entri proyek baru',
        tags: ['Project'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/ProjectCreateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entri proyek berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Project')
            ),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat entri proyek')
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

            $name = trim($formData['name'] ?? '');
            $description = trim($formData['description'] ?? '');
            $idKategori = isset($formData['id_kategori']) ? (int) $formData['id_kategori'] : 0;
            $status = $formData['status'] ?? 'active';

            if ($name === '') {
                Response::json(['message' => 'Kolom name wajib diisi.'], 422);
                return;
            }

            if ($idKategori === 0) {
                Response::json(['message' => 'Kolom id_kategori wajib diisi.'], 422);
                return;
            }

            $videoUrl = null;
            if (Request::hasFile('video')) {
                try {
                    $file = Request::getFile('video');
                    if ($file === null) {
                        Response::json(['message' => 'File video tidak ditemukan.'], 400);
                        return;
                    }
                    $videoUrl = FileUploadHelper::uploadVideo($file, 'uploads/project');
                } catch (InvalidArgumentException $exception) {
                    Response::json(['message' => 'Gagal upload video: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            $imageUrls = [];
            if (Request::hasFilesArray('images')) {
                try {
                    $files = Request::getFilesArray('images');
                    if (empty($files)) {
                        Response::json(['message' => 'Tidak ada file gambar yang valid untuk diupload. Pastikan file yang diupload valid dan tidak melebihi ukuran maksimum.'], 400);
                        return;
                    }
                    foreach ($files as $file) {
                        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                            continue;
                        }
                        try {
                            $imageUrl = FileUploadHelper::uploadImage($file, 'uploads/project');
                            $imageUrls[] = $imageUrl;
                        } catch (InvalidArgumentException $e) {
                            foreach ($imageUrls as $imageUrl) {
                                FileUploadHelper::deleteFile($imageUrl);
                            }
                            if ($videoUrl) {
                                FileUploadHelper::deleteFile($videoUrl);
                            }
                            Response::json(['message' => 'Gagal upload gambar: ' . $e->getMessage()], 400);
                            return;
                        }
                    }
                } catch (InvalidArgumentException $exception) {
                    foreach ($imageUrls as $imageUrl) {
                        FileUploadHelper::deleteFile($imageUrl);
                    }
                    if ($videoUrl) {
                        FileUploadHelper::deleteFile($videoUrl);
                    }
                    Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            try {
                $entry = $this->project->create(
                    $name,
                    $description ?: null,
                    $idKategori,
                    $videoUrl,
                    $imageUrls,
                    $status
                );
            } catch (PDOException $exception) {
                foreach ($imageUrls as $imageUrl) {
                    FileUploadHelper::deleteFile($imageUrl);
                }
                if ($videoUrl) {
                    FileUploadHelper::deleteFile($videoUrl);
                }
                Response::json(['message' => 'Gagal membuat entri proyek', 'error' => $exception->getMessage()], 500);
                return;
            }

            Response::json($entry, 201);
            return;
        }

        try {
            $payload = Request::json();
            $data = $this->validatePayload($payload);
        } catch (InvalidArgumentException $ex) {
            Response::json(['message' => $ex->getMessage()], 400);
            return;
        }

        try {
            $entry = $this->project->create(
                $data['name'],
                $data['description'],
                $data['id_kategori'],
                $data['video_url'] ?? null,
                $data['image_url'] ?? [],
                $data['status'] ?? 'active'
            );
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal membuat entri proyek', 'error' => $ex->getMessage()], 500);
            return;
        }

        Response::json($entry, 201);
    }

    #[OA\Put(
        path: '/project/{id}',
        summary: 'Perbarui entri proyek',
        tags: ['Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProjectUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entri proyek berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Project')
            ),
            new OA\Response(response: 404, description: 'Entri proyek tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/project/{id}',
        summary: 'Perbarui entri proyek (dengan upload file)',
        tags: ['Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/ProjectUpdateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entri proyek berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Project')
            ),
            new OA\Response(response: 404, description: 'Entri proyek tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
        ]
    )]
    public function update(int $id): void
    {
        $existing = $this->project->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Entri proyek tidak ditemukan'], 404);
            return;
        }

        if (Request::isMultipart()) {
            try {
                $formData = Request::getFormData();
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => $exception->getMessage()], 400);
                return;
            }

            $name = isset($formData['name']) ? trim($formData['name']) : $existing['name'];
            $description = isset($formData['description']) ? trim($formData['description']) : $existing['description'];
            $idKategori = isset($formData['id_kategori']) ? (int) $formData['id_kategori'] : $existing['id_kategori'];
            $status = isset($formData['status']) ? $formData['status'] : $existing['status'];
            $videoUrl = $existing['video_url'];
            $imageUrls = is_array($existing['image_url']) ? $existing['image_url'] : [];

            $newVideoUrl = null;
            if (Request::hasFile('video')) {
                try {
                    $file = Request::getFile('video');
                    if ($file === null) {
                        Response::json(['message' => 'File video tidak ditemukan.'], 400);
                        return;
                    }
                    $newVideoUrl = FileUploadHelper::uploadVideo($file, 'uploads/project');
                    if ($videoUrl) {
                        FileUploadHelper::deleteFile($videoUrl);
                    }
                    $videoUrl = $newVideoUrl;
                } catch (InvalidArgumentException $exception) {
                    Response::json(['message' => 'Gagal upload video: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            $newImageUrls = [];
            if (Request::hasFilesArray('images')) {
                try {
                    $files = Request::getFilesArray('images');
                    foreach ($files as $file) {
                        $imageUrl = FileUploadHelper::uploadImage($file, 'uploads/project');
                        $newImageUrls[] = $imageUrl;
                    }
                    foreach ($imageUrls as $oldImageUrl) {
                        FileUploadHelper::deleteFile($oldImageUrl);
                    }
                    $imageUrls = $newImageUrls;
                } catch (InvalidArgumentException $exception) {
                    foreach ($newImageUrls as $imageUrl) {
                        FileUploadHelper::deleteFile($imageUrl);
                    }
                    if ($newVideoUrl) {
                        FileUploadHelper::deleteFile($newVideoUrl);
                    }
                    Response::json(['message' => 'Gagal upload gambar: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            try {
                $entry = $this->project->update(
                    $id,
                    $name,
                    $description ?: null,
                    $idKategori,
                    $videoUrl,
                    $imageUrls,
                    $status
                );
            } catch (PDOException $exception) {
                foreach ($newImageUrls as $imageUrl) {
                    FileUploadHelper::deleteFile($imageUrl);
                }
                if ($newVideoUrl) {
                    FileUploadHelper::deleteFile($newVideoUrl);
                }
                Response::json(['message' => 'Gagal memperbarui entri proyek', 'error' => $exception->getMessage()], 500);
                return;
            }

            if ($entry === null) {
                Response::json(['message' => 'Entri proyek tidak ditemukan saat update'], 404);
                return;
            }

            Response::json($entry);
            return;
        }

        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $ex) {
            Response::json(['message' => $ex->getMessage()], 400);
            return;
        }

        $merged = array_merge($existing, $payload);

        try {
            $data = $this->validatePayload($merged, true);
        } catch (InvalidArgumentException $ex) {
            Response::json(['message' => $ex->getMessage()], 400);
            return;
        }

        try {
            $entry = $this->project->update(
                $id,
                $data['name'] ?? $existing['name'],
                $data['description'] ?? $existing['description'],
                $data['id_kategori'] ?? $existing['id_kategori'],
                $data['video_url'] ?? $existing['video_url'],
                $data['image_url'] ?? $existing['image_url'],
                $data['status'] ?? $existing['status']
            );
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal memperbarui entri proyek', 'error' => $ex->getMessage()], 500);
            return;
        }

        if ($entry === null) {
            Response::json(['message' => 'Entri proyek tidak ditemukan saat update'], 404);
            return;
        }

        Response::json($entry);
    }

    #[OA\Delete(
        path: '/project/{id}',
        summary: 'Hapus entri proyek berdasarkan ID',
        tags: ['Project'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Entri proyek berhasil dihapus'),
            new OA\Response(response: 404, description: 'Entri proyek tidak ditemukan')
        ]
    )]
    public function destroy(int $id): void
    {
        try {
            $entry = $this->project->find($id);
            if ($entry === null) {
                Response::json(['message' => 'Entri proyek tidak ditemukan'], 404);
                return;
            }

            $deleted = $this->project->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Entri proyek tidak ditemukan'], 404);
                return;
            }

            if (!empty($entry['video_url'])) {
                FileUploadHelper::deleteFile($entry['video_url']);
            }

            if (!empty($entry['image_url']) && is_array($entry['image_url'])) {
                foreach ($entry['image_url'] as $imageUrl) {
                    FileUploadHelper::deleteFile($imageUrl);
                }
            }
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal menghapus entri proyek', 'error' => $ex->getMessage()], 500);
            return;
        }

        Response::empty(204);
    }

    private function validatePayload(array $payload, bool $isUpdate = false): array
    {
        if (!$isUpdate) {
            if (empty($payload['name'])) {
                throw new InvalidArgumentException('Kolom name wajib diisi.');
            }
            if (empty($payload['id_kategori'])) {
                throw new InvalidArgumentException('Kolom id_kategori wajib diisi.');
            }
        }

        // Optional fields: description, video_url, image_url, status

        return [
            'name'       => $payload['name'] ?? null,
            'description'=> $payload['description'] ?? null,
            'id_kategori'=> (int) ($payload['id_kategori'] ?? 0),
            'video_url'  => $payload['video_url'] ?? null,
            'image_url'  => $payload['image_url'] ?? [],
            'status'     => $payload['status'] ?? 'active',
        ];
    }
}
