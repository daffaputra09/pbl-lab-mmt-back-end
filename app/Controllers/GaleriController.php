<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Models\Galeri;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Galeri(name: 'Galeri')]
class GaleriController
{
    private Galeri $galeri;
    private const ALLOWED_TYPES = ['Foto', 'Video'];

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

        if ($type !== '' && !in_array($type, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException('Tipe harus "Foto" atau "Video".');
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
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['Foto', 'Video']))
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
        $type = ucfirst(strtolower(trim($type)));
        
        if (!in_array($type, self::ALLOWED_TYPES)) {
            Response::json(['message' => 'Tipe galeri tidak valid. Gunakan "Foto" atau "Video".'], 400);
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
            content: new OA\JsonContent(ref: '#/components/schemas/GaleriCreateRequest')
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
    public function update(int $id): void
    {
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        $existing = $this->galeri->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Entri galeri tidak ditemukan'], 404);
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
            $deleted = $this->galeri->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Entri galeri tidak ditemukan'], 404);
                return;
            }
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus entri galeri', 'error' => $exception->getMessage()], 500);
            return;
        }

        Response::empty(204);
    }
}