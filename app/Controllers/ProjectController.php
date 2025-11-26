<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Models\Project;
use App\Models\Tag;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Project')]
class ProjectController
{
    private Project $project;
    private Tag $tag;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->project = new Project($connection);
        $this->tag = new Tag($connection);
    }

    #[OA\Get(
        path: '/project',
        summary: 'List semua entri proyek dengan pagination (dapat difilter berdasarkan id_kategori, tag_names, dan search by name)',
        tags: ['Project'],
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
                name: 'id_kategori',
                in: 'query',
                required: false,
                description: 'Filter proyek berdasarkan id_kategori',
                schema: new OA\Schema(type: 'integer', example: 3)
            ),
            new OA\Parameter(
                name: 'tag_names',
                in: 'query',
                required: false,
                description: 'Filter proyek berdasarkan tag names (multiple, OR logic). Bisa berupa array atau string yang dipisahkan koma. Contoh: "tag_names[]=Web Development&tag_names[]=PHP" atau "tag_names=Web Development,PHP"',
                schema: new OA\Schema(
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['Web Development', 'PHP']
                ),
                style: 'form',
                explode: true
            ),
            new OA\Parameter(
                name: 'search',
                in: 'query',
                required: false,
                description: 'Search project berdasarkan nama (case-insensitive, partial match)',
                schema: new OA\Schema(type: 'string', example: 'Website')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar entri proyek dengan pagination',
                content: new OA\JsonContent(ref: '#/components/schemas/ProjectPaginatedResponse')
            ),
            new OA\Response(response: 400, description: 'Parameter tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengambil data proyek')
        ]
    )]
    
    public function index(): void
    {
        try {
            $page = (int) Request::getQuery('page', 1);
            $limitParam = Request::getQuery('limit', null);
            $limit = $limitParam !== null ? (int) $limitParam : null;
            $idKategori = isset($_GET['id_kategori']) ? (int) $_GET['id_kategori'] : null;
            $searchName = Request::getQuery('search', null);
            if ($searchName !== null && $searchName !== '') {
                $searchName = trim($searchName);
            } else {
                $searchName = null;
            }
            
            $tagNames = [];

            if (isset($_SERVER['QUERY_STRING'])) {
                $queryString = $_SERVER['QUERY_STRING'];
                preg_match_all('/tag_names(?:\[\])?=([^&]*)/', $queryString, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $match) {
                        $decoded = urldecode($match);
                        if ($decoded !== '') {
                            $values = explode(',', $decoded);
                            foreach ($values as $value) {
                                $trimmed = trim($value);
                                if ($trimmed !== '') {
                                    $tagNames[] = $trimmed;
                                }
                            }
                        }
                    }
                }
            }
            
            if (empty($tagNames) && isset($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $queryParams);
                if (isset($queryParams['tag_names'])) {
                    if (is_array($queryParams['tag_names'])) {
                        $tagNames = array_map('trim', $queryParams['tag_names']);
                        $tagNames = array_filter($tagNames);
                    } elseif (is_string($queryParams['tag_names']) && $queryParams['tag_names'] !== '') {
                        $tagNames = array_map('trim', explode(',', $queryParams['tag_names']));
                        $tagNames = array_filter($tagNames);
                    }
                }
            }
            
            if (empty($tagNames) && isset($_GET['tag_names'])) {
                if (is_array($_GET['tag_names'])) {
                    $tagNames = array_map('trim', $_GET['tag_names']);
                    $tagNames = array_filter($tagNames);
                } elseif (is_string($_GET['tag_names']) && $_GET['tag_names'] !== '') {
                    $tagNames = array_map('trim', explode(',', $_GET['tag_names']));
                    $tagNames = array_filter($tagNames);
                }
            }
            
            $tagNames = array_values(array_unique($tagNames)); 

            if ($page < 1) {
                Response::json(['message' => 'Parameter page harus lebih besar dari 0'], 400);
                return;
            }

            if ($limit !== null && $limit < 1) {
                Response::json(['message' => 'Parameter limit harus lebih besar dari 0'], 400);
                return;
            }

            $total = $this->project->count($idKategori, !empty($tagNames) ? $tagNames : null, $searchName);

            if ($limit === null) {
                $data = $this->project->all($idKategori, !empty($tagNames) ? $tagNames : null, $searchName);
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->project->paginate($page, $limit, $idKategori, !empty($tagNames) ? $tagNames : null, $searchName);

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
            $status = $formData['status'] ?? 'on_progress';
            
            $tagIds = [];
            if (isset($formData['tag_ids'])) {
                if (is_array($formData['tag_ids'])) {
                    $tagIds = array_map('intval', $formData['tag_ids']);
                } elseif (is_string($formData['tag_ids'])) {
                    $tagIds = array_map('intval', explode(',', $formData['tag_ids']));
                }
            }

            if ($name === '') {
                Response::json(['message' => 'Kolom name wajib diisi.'], 422);
                return;
            }

            if ($idKategori === 0) {
                Response::json(['message' => 'Kolom id_kategori wajib diisi.'], 422);
                return;
            }

            if (!in_array($status, ['on_progress', 'completed'], true)) {
                Response::json(['message' => 'Status harus "on_progress" atau "completed".'], 422);
                return;
            }

            if (empty($tagIds)) {
                Response::json(['message' => 'Kolom tag_ids wajib diisi dan harus berupa array tag id.'], 422);
                return;
            }

            $invalidTagIds = $this->validateTagIds($tagIds);
            if (!empty($invalidTagIds)) {
                Response::json(['message' => 'Tag dengan id berikut tidak ditemukan: ' . implode(', ', $invalidTagIds)], 422);
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
                    $videoUrl = ltrim($videoUrl, '/');
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
                            $imageUrls[] = ltrim($imageUrl, '/');
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
                    $status,
                    $tagIds
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

            $createdEntry = $this->project->find($entry['id']);
            Response::json($createdEntry ?: $entry, 201);
            return;
        }

        try {
            $payload = Request::json();
            $data = $this->validatePayload($payload);
        } catch (InvalidArgumentException $ex) {
            Response::json(['message' => $ex->getMessage()], 400);
            return;
        }

        $tagIds = $data['tag_ids'] ?? [];
        if (empty($tagIds)) {
            Response::json(['message' => 'Kolom tag_ids wajib diisi dan harus berupa array tag id.'], 422);
            return;
        }

        $invalidTagIds = $this->validateTagIds($tagIds);
        if (!empty($invalidTagIds)) {
            Response::json(['message' => 'Tag dengan id berikut tidak ditemukan: ' . implode(', ', $invalidTagIds)], 422);
            return;
        }

        try {
            $videoUrl = isset($data['video_url']) 
                ? FileUploadHelper::getRelativePath($data['video_url']) 
                : null;
            $imageUrls = [];
            if (isset($data['image_url']) && is_array($data['image_url'])) {
                $imageUrls = FileUploadHelper::getRelativePaths($data['image_url']);
            }
            
            $entry = $this->project->create(
                $data['name'],
                $data['description'],
                $data['id_kategori'],
                $videoUrl,
                $imageUrls,
                $data['status'] ?? 'on_progress',
                $tagIds
            );
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal membuat entri proyek', 'error' => $ex->getMessage()], 500);
            return;
        }

        $createdEntry = $this->project->find($entry['id']);
        Response::json($createdEntry ?: $entry, 201);
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
        $existing = $this->project->findRaw($id);
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
            $videoUrl = $existing['video_url'] ? ltrim($existing['video_url'], '/') : null;
            
            $existingImageUrls = is_array($existing['image_url']) 
                ? array_map(function($url) {
                    return ltrim($url, '/');
                }, $existing['image_url']) 
                : [];
            
            $imageUrlsToKeep = [];
            $imageUrlProvided = false;
            if (isset($formData['image_url'])) {
                if (is_array($formData['image_url']) && !empty($formData['image_url'])) {
                    $imageUrlsToKeep = FileUploadHelper::getRelativePaths($formData['image_url']);
                    $imageUrlsToKeep = array_filter($imageUrlsToKeep);
                    if (!empty($imageUrlsToKeep)) {
                        $imageUrlProvided = true;
                    }
                } elseif (is_string($formData['image_url']) && trim($formData['image_url']) !== '') {
                    $urls = array_map('trim', explode(',', $formData['image_url']));
                    $imageUrlsToKeep = FileUploadHelper::getRelativePaths($urls);
                    $imageUrlsToKeep = array_filter($imageUrlsToKeep);
                    if (!empty($imageUrlsToKeep)) {
                        $imageUrlProvided = true;
                    }
                }
            }
            
            $tagIds = [];
            if (isset($formData['tag_ids'])) {
                if (is_array($formData['tag_ids'])) {
                    $tagIds = array_map('intval', $formData['tag_ids']);
                } elseif (is_string($formData['tag_ids'])) {
                    $tagIds = array_map('intval', explode(',', $formData['tag_ids']));
                }
            }

            if (empty($tagIds)) {
                Response::json(['message' => 'Kolom tag_ids wajib diisi dan harus berupa array tag id.'], 422);
                return;
            }

            $invalidTagIds = $this->validateTagIds($tagIds);
            if (!empty($invalidTagIds)) {
                Response::json(['message' => 'Tag dengan id berikut tidak ditemukan: ' . implode(', ', $invalidTagIds)], 422);
                return;
            }

            if (!in_array($status, ['on_progress', 'completed'], true)) {
                Response::json(['message' => 'Status harus "on_progress" atau "completed".'], 422);
                return;
            }

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
                    $videoUrl = ltrim($newVideoUrl, '/');
                } catch (InvalidArgumentException $exception) { 
                    Response::json(['message' => 'Gagal upload video: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            $newImageUrls = [];
            if (Request::hasFilesArray('images')) {
                try {
                    $files = Request::getFilesArray('images');
                    if (empty($files)) {
                        Response::json(['message' => 'Tidak ada file gambar yang valid untuk diupload. Pastikan file yang diupload valid dan tidak melebihi ukuran maksimum.'], 400);
                        return;
                    }
                    foreach ($files as $file) {
                        $imageUrl = FileUploadHelper::uploadImage($file, 'uploads/project');
                        $newImageUrls[] = ltrim($imageUrl, '/');
                    }
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

            if (!$imageUrlProvided && empty($newImageUrls)) {
                Response::json(['message' => 'Jika image_url kosong atau tidak dikirim, maka images wajib diisi untuk mengupload foto baru.'], 422);
                return;
            }

            if ($imageUrlProvided) {
                $imageUrls = array_merge($imageUrlsToKeep, $newImageUrls);
            } else {
                $imageUrls = $newImageUrls;
            }

            $imageUrls = array_unique($imageUrls);
            $imageUrls = array_map(function($url) {
                return ltrim($url, '/');
            }, $imageUrls);
            $imageUrls = array_values(array_filter($imageUrls)); 

            foreach ($existingImageUrls as $oldImageUrl) {
                $oldImageUrl = ltrim($oldImageUrl, '/');
                if (!in_array($oldImageUrl, $imageUrls, true)) {
                    FileUploadHelper::deleteFile($oldImageUrl);
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
                    $status,
                    $tagIds
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

            $updatedEntry = $this->project->find($id);
            Response::json($updatedEntry ?: $entry);
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

        $tagIds = $data['tag_ids'] ?? [];
        if (empty($tagIds)) {
            Response::json(['message' => 'Kolom tag_ids wajib diisi dan harus berupa array tag id.'], 422);
            return;
        }

        $invalidTagIds = $this->validateTagIds($tagIds);
        if (!empty($invalidTagIds)) {
            Response::json(['message' => 'Tag dengan id berikut tidak ditemukan: ' . implode(', ', $invalidTagIds)], 422);
            return;
        }

        try {
            $videoUrl = isset($data['video_url']) 
                ? ltrim(FileUploadHelper::getRelativePath($data['video_url']) ?? '', '/')
                : ltrim(FileUploadHelper::getRelativePath($existing['video_url']) ?? '', '/');
            
            $imageUrls = [];
            if (isset($data['image_url'])) {
                if (is_array($data['image_url'])) {
                    $imageUrls = FileUploadHelper::getRelativePaths($data['image_url']);
                    $imageUrls = array_map(function($url) {
                        return ltrim($url, '/');
                    }, $imageUrls);
                    $imageUrls = array_values(array_filter($imageUrls)); 
                }
            } else {
                $existingUrls = is_array($existing['image_url']) ? $existing['image_url'] : [];
                $imageUrls = array_map(function($url) {
                    return ltrim(FileUploadHelper::getRelativePath($url) ?? '', '/');
                }, $existingUrls);
                $imageUrls = array_values(array_filter($imageUrls)); 
            }
            
            $existingUrls = is_array($existing['image_url']) ? $existing['image_url'] : [];
            foreach ($existingUrls as $oldImageUrl) {
                $oldImageUrlRelative = ltrim(FileUploadHelper::getRelativePath($oldImageUrl) ?? '', '/');
                if (!in_array($oldImageUrlRelative, $imageUrls, true)) {
                    FileUploadHelper::deleteFile($oldImageUrlRelative);
                }
            }
            
            $entry = $this->project->update(
                $id,
                $data['name'] ?? $existing['name'],
                $data['description'] ?? $existing['description'],
                $data['id_kategori'] ?? $existing['id_kategori'],
                $videoUrl ?: null,
                $imageUrls,
                $data['status'] ?? $existing['status'],
                $tagIds
            );
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal memperbarui entri proyek', 'error' => $ex->getMessage()], 500);
            return;
        }

        if ($entry === null) {
            Response::json(['message' => 'Entri proyek tidak ditemukan saat update'], 404);
            return;
        }

        $updatedEntry = $this->project->find($id);
        Response::json($updatedEntry ?: $entry);
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

        $tagIds = [];
        if (isset($payload['tag_ids'])) {
            if (is_array($payload['tag_ids'])) {
                $tagIds = array_map('intval', $payload['tag_ids']);
            }
        }

        $status = $payload['status'] ?? 'on_progress';
        if (!in_array($status, ['on_progress', 'completed'], true)) {
            throw new InvalidArgumentException('Status harus "on_progress" atau "completed".');
        }


        return [
            'name'       => $payload['name'] ?? null,
            'description'=> $payload['description'] ?? null,
            'id_kategori'=> (int) ($payload['id_kategori'] ?? 0),
            'video_url'  => $payload['video_url'] ?? null,
            'image_url'  => $payload['image_url'] ?? [],
            'status'     => $status,
            'tag_ids'    => $tagIds,
        ];
    }

    private function validateTagIds(array $tagIds): array
    {
        $invalidTagIds = [];
        foreach ($tagIds as $tagId) {
            $tag = $this->tag->find((int) $tagId);
            if ($tag === null) {
                $invalidTagIds[] = $tagId;
            }
        }
        return $invalidTagIds;
    }
}
