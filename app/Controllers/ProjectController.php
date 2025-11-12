<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
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
            content: new OA\JsonContent(ref: '#/components/schemas/ProjectCreateRequest')
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
    public function update(int $id): void
    {
        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $ex) {
            Response::json(['message' => $ex->getMessage()], 400);
            return;
        }

        $existing = $this->project->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Entri proyek tidak ditemukan'], 404);
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
            $deleted = $this->project->delete($id);

            if (!$deleted) {
                Response::json(['message' => 'Entri proyek tidak ditemukan'], 404);
                return;
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
