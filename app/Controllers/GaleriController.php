<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Http\FileUploadHelper;
use App\Models\Galeri;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Galeri')]
class GaleriController
{
    private Galeri $galeri;
    private const ALLOWED_TYPES = ['foto', 'video', 'lottie'];

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->galeri = new Galeri($connection);
    }

    // HELPER

    private function validatePayload(array $payload, bool $isUpdate = false): array
    {
        $type = trim($payload['type'] ?? '');
        $fileUrl = trim($payload['file_url'] ?? '');
        $tanggalKegiatan = trim($payload['tanggal_kegiatan'] ?? null);

        if (!$isUpdate && ($type === '' || $fileUrl === '')) {
            throw new InvalidArgumentException('Field type dan file_url wajib diisi.');
        }

        if ($type !== '' && !in_array(strtolower($type), self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException('Tipe harus "foto", "video", atau "lottie".');
        }

        if ($tanggalKegiatan !== null && $tanggalKegiatan !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalKegiatan)) {
             throw new InvalidArgumentException('Format tanggal_kegiatan tidak valid. Gunakan YYYY-MM-DD.');
        }

        return [
            'type' => $type ?: null,
            'file_url' => $fileUrl ?: null,
            'tanggal_kegiatan' => $tanggalKegiatan ?: null,
        ];
    }

    #[OA\Get(
        path: '/galeri',
        summary: 'List semua entri galeri',
        tags: ['Galeri'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar entri galeri',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Galeri')
                )
            )
        ]
    )]
    public function index(): void
    {
        try {
            Response::json($this->galeri->all());
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data galeri', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/galeri/{id}',
        summary: 'Detail entri galeri',
        tags: ['Galeri'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail entri galeri',
                content: new OA\JsonContent(ref: '#/components/schemas/Galeri')
            ),
            new OA\Response(response: 404, description: 'Entri galeri tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        try {
            $entry = $this->galeri->find($id);

            if ($entry === null) {
                Response::json(['message' => 'Entri galeri tidak ditemukan'], 404);
                return;
            }

            Response::json($entry);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data galeri', 'error' => $exception->getMessage()], 500);
        }
    }
    
    #[OA\Get(
        path: '/galeri/type/{type}',
        summary: 'Filter entri galeri berdasarkan tipe',
        tags: ['Galeri'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['foto', 'video', 'lottie']))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar entri galeri berdasarkan tipe',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Galeri')
                )
            ),
            new OA\Response(response: 400, description: 'Tipe tidak valid')
        ]
    )]
    public function filterByType(string $type): void
    {
        $type = strtolower(trim($type));
        
        if (!in_array($type, self::ALLOWED_TYPES)) {
            Response::json(['message' => 'Tipe galeri tidak valid. Gunakan "foto", "video", atau "lottie".'], 400);
            return;
        }
        
        try {
            Response::json($this->galeri->findByType($type));
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memfilter data galeri', 'error' => $exception->getMessage()], 500);
        }
    }

    // CREATE

    #[OA\Post(
        path: '/galeri',
        summary: 'Buat entri galeri baru',
        tags: ['Galeri'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/GaleriCreateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entri galeri berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Galeri')
            ),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat entri galeri')
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

            $type = trim($formData['type'] ?? '');
            $tanggalKegiatan = trim($formData['tanggal_kegiatan'] ?? '');

            if ($type === '') {
                Response::json(['message' => 'Field type wajib diisi.'], 422);
                return;
            }

            $typeLower = strtolower($type);
            if (!in_array($typeLower, self::ALLOWED_TYPES)) {
                Response::json(['message' => 'Tipe harus "foto", "video", atau "lottie".'], 422);
                return;
            }
            
            $type = $typeLower;

            if ($tanggalKegiatan !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalKegiatan)) {
                Response::json(['message' => 'Format tanggal_kegiatan tidak valid. Gunakan YYYY-MM-DD.'], 422);
                return;
            }

            if (!Request::hasFile('file')) {
                Response::json(['message' => 'Field file wajib diisi.'], 422);
                return;
            }

            try {
                $file = Request::getFile('file');
                if ($file === null) {
                    Response::json(['message' => 'File tidak ditemukan.'], 400);
                    return;
                }

                $fileUrl = FileUploadHelper::uploadGaleriFile($file, $type, 'uploads/galeri');
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => 'Gagal upload file: ' . $exception->getMessage()], 400);
                return;
            }

            try {
                $entry = $this->galeri->create(
                    $type,
                    $fileUrl,
                    $tanggalKegiatan ?: null
                );
            } catch (PDOException $exception) {
                FileUploadHelper::deleteFile($fileUrl);
                Response::json(['message' => 'Gagal membuat entri galeri', 'error' => $exception->getMessage()], 500);
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
            $entry = $this->galeri->create(
                $data['type'],
                $data['file_url'],
                $data['tanggal_kegiatan']
            );
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal membuat entri galeri', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::json($entry, 201);
    }

    // UPDATE

    #[OA\Put(
        path: '/galeri/{id}',
        summary: 'Perbarui entri galeri',
        tags: ['Galeri'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/GaleriUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entri galeri berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Galeri')
            ),
            new OA\Response(response: 404, description: 'Entri galeri tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/galeri/{id}',
        summary: 'Perbarui entri galeri (dengan upload file)',
        tags: ['Galeri'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/GaleriUpdateRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entri galeri berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Galeri')
            ),
            new OA\Response(response: 404, description: 'Entri galeri tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
        ]
    )]
    public function update(int $id): void
    {
        $existing = $this->galeri->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Entri galeri tidak ditemukan'], 404);
            return;
        }

        if (Request::isMultipart()) {
            try {
                $formData = Request::getFormData();
            } catch (InvalidArgumentException $exception) {
                Response::json(['message' => $exception->getMessage()], 400);
                return;
            }

            $type = isset($formData['type']) ? trim($formData['type']) : $existing['type'];
            $tanggalKegiatan = isset($formData['tanggal_kegiatan']) ? trim($formData['tanggal_kegiatan']) : $existing['tanggal_kegiatan'];
            $fileUrl = $existing['file_url'];

            if ($type === '') {
                $type = $existing['type'];
            }

            $typeLower = strtolower($type);
            if (!in_array($typeLower, self::ALLOWED_TYPES)) {
                Response::json(['message' => 'Tipe harus "foto", "video", atau "lottie".'], 422);
                return;
            }
            
            $type = $typeLower;

            if ($tanggalKegiatan !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalKegiatan)) {
                Response::json(['message' => 'Format tanggal_kegiatan tidak valid. Gunakan YYYY-MM-DD.'], 422);
                return;
            }

            $newFileUrl = null;
            if (Request::hasFile('file')) {
                try {
                    $file = Request::getFile('file');
                    if ($file === null) {
                        Response::json(['message' => 'File tidak ditemukan.'], 400);
                        return;
                    }

                    $newFileUrl = FileUploadHelper::uploadGaleriFile($file, $type, 'uploads/galeri');
                    if ($fileUrl) {
                        FileUploadHelper::deleteFile($fileUrl);
                    }
                    $fileUrl = $newFileUrl;
                } catch (InvalidArgumentException $exception) {
                    Response::json(['message' => 'Gagal upload file: ' . $exception->getMessage()], 400);
                    return;
                }
            }

            if ($type === null || $fileUrl === null) {
                Response::json(['message' => 'Tipe dan URL file tidak boleh kosong.'], 400);
                return;
            }

            try {
                $entry = $this->galeri->update(
                    $id,
                    $type,
                    $fileUrl,
                    $tanggalKegiatan ?: null
                );
            } catch (PDOException $exception) {
                if ($newFileUrl) {
                    FileUploadHelper::deleteFile($newFileUrl);
                }
                Response::json(['message' => 'Gagal memperbarui entri galeri', 'error' => $exception->getMessage()], 500);
                return;
            }

            if ($entry === null) {
                Response::json(['message' => 'Entri galeri tidak ditemukan saat update'], 404);
                return;
            }

            Response::json($entry);
            return;
        }

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
        
        $type = $data['type'] ?? $existing['type'];
        $fileUrl = $data['file_url'] ?? $existing['file_url'];
        $tanggalKegiatan = $data['tanggal_kegiatan'] ?? $existing['tanggal_kegiatan'];

        if ($type === null || $fileUrl === null) {
             Response::json(['message' => 'Tipe dan URL file tidak boleh kosong.'], 400);
             return;
        }

        try {
            $entry = $this->galeri->update(
                $id,
                $type,
                $fileUrl,
                $tanggalKegiatan
            );
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal memperbarui entri galeri', 'error' => $exception->getMessage()], 500);
            return;
        }

        if ($entry === null) {
             Response::json(['message' => 'Entri galeri tidak ditemukan saat update'], 404);
             return;
        }

        Response::json($entry);
    }

    // DELETE

    #[OA\Delete(
        path: '/galeri/{id}',
        summary: 'Hapus entri galeri',
        tags: ['Galeri'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Entri galeri berhasil dihapus'),
            new OA\Response(response: 404, description: 'Entri galeri tidak ditemukan')
        ]
    )]
    public function destroy(int $id): void
    {
        try {
            $entry = $this->galeri->find($id);
            if ($entry === null) {
                Response::json(['message' => 'Entri galeri tidak ditemukan'], 404);
                return;
            }

            $deleted = $this->galeri->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Entri galeri tidak ditemukan'], 404);
                return;
            }

            if (!empty($entry['file_url'])) {
                FileUploadHelper::deleteFile($entry['file_url']);
            }
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus entri galeri', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::empty(204);
    }
}