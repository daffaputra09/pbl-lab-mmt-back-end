<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Rating;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Rating')]
class RatingController
{
    private Rating $rating;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->rating = new Rating($connection);
    }

    // HELPER: validasi payload
    private function validatePayload(array $payload, bool $isUpdate = false): array
    {
        $idProject = $payload['id_project'] ?? null;
        $name      = trim($payload['name'] ?? '');
        $ratingVal = $payload['rating'] ?? null;
        $comment   = trim($payload['comment'] ?? null);

        if (!$isUpdate) {
            if (empty($idProject)) {
                throw new InvalidArgumentException('Kolom id_project wajib diisi.');
            }
            if ($name === '') {
                throw new InvalidArgumentException('Kolom name wajib diisi.');
            }
            if (!is_int($ratingVal) && !ctype_digit((string)$ratingVal)) {
                throw new InvalidArgumentException('Kolom rating wajib diisi dengan angka.');
            }
        }

        $ratingInt = (int)$ratingVal;
        if ($ratingInt < 1 || $ratingInt > 5) {
            throw new InvalidArgumentException('Nilai rating harus antara 1 dan 5.');
        }

        return [
            'id_project' => (int)$idProject,
            'name'       => $name,
            'rating'     => $ratingInt,
            'comment'    => $comment !== '' ? $comment : null,
        ];
    }

    #[OA\Get(
        path: '/rating',
        summary: 'List semua entri rating dengan pagination (dapat difilter berdasarkan project_id)',
        tags: ['Rating'],
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
                name: 'project_id',
                in: 'query',
                required: false,
                description: 'Filter rating berdasarkan project_id. Jika diisi, hanya akan mengembalikan rating untuk project dengan id yang sesuai.',
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar entri rating dengan pagination',
                content: new OA\JsonContent(ref: '#/components/schemas/RatingPaginatedResponse')
            ),
            new OA\Response(response: 400, description: 'Parameter tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengambil data rating')
        ]
    )]
    public function index(): void
    {
        try {
            $page = (int) Request::getQuery('page', 1);
            $limitParam = Request::getQuery('limit', null);
            $limit = $limitParam !== null ? (int) $limitParam : null;
            $projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;

            if ($page < 1) {
                Response::json(['message' => 'Parameter page harus lebih besar dari 0'], 400);
                return;
            }

            if ($limit !== null && $limit < 1) {
                Response::json(['message' => 'Parameter limit harus lebih besar dari 0'], 400);
                return;
            }

            if ($projectId !== null && $projectId < 1) {
                Response::json(['message' => 'Parameter project_id harus lebih besar dari 0'], 400);
                return;
            }

            $total = $this->rating->count($projectId);

            if ($limit === null) {
                $data = $this->rating->all($projectId);
                Response::json([
                    'data' => $data,
                    'pagination' => null
                ]);
                return;
            }

            $totalPages = (int) ceil($total / $limit);
            $data = $this->rating->paginate($page, $limit, $projectId);

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
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal mengambil data rating', 'error' => $ex->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/rating/{id}',
        summary: 'Detail entri rating',
        tags: ['Rating'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail entri rating',
                content: new OA\JsonContent(ref: '#/components/schemas/Rating')
            ),
            new OA\Response(response: 404, description: 'Entri rating tidak ditemukan')
        ]
    )]
    public function show(int $id): void
    {
        try {
            $entry = $this->rating->find($id);
            if ($entry === null) {
                Response::json(['message' => 'Entri rating tidak ditemukan'], 404);
                return;
            }
            Response::json($entry);
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal mengambil data rating', 'error' => $ex->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/rating',
        summary: 'Buat entri rating baru',
        tags: ['Rating'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RatingCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entri rating berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/Rating')
            ),
            new OA\Response(response: 400, description: 'Permintaan tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat entri rating')
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
            $entry = $this->rating->create(
                $data['id_project'],
                $data['name'],
                $data['rating'],
                $data['comment']
            );
            Response::json($entry, 201);
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal membuat entri rating', 'error' => $ex->getMessage()], 500);
        }
    }

    #[OA\Put(
        path: '/rating/{id}',
        summary: 'Perbarui entri rating',
        tags: ['Rating'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RatingUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entri rating berhasil diperbarui',
                content: new OA\JsonContent(ref: '#/components/schemas/Rating')
            ),
            new OA\Response(response: 404, description: 'Entri rating tidak ditemukan'),
            new OA\Response(response: 400, description: 'Permintaan tidak valid')
        ]
    )]
    public function update(int $id): void
    {
        try {
            $payload = Request::json();
            $data = $this->validatePayload($payload, true);
        } catch (InvalidArgumentException $ex) {
            Response::json(['message' => $ex->getMessage()], 400);
            return;
        }

        $existing = $this->rating->find($id);
        if ($existing === null) {
            Response::json(['message' => 'Entri rating tidak ditemukan'], 404);
            return;
        }

        try {
            $entry = $this->rating->update(
                $id,
                $data['id_project'],
                $data['name'],
                $data['rating'],
                $data['comment']
            );
            if ($entry === null) {
                Response::json(['message' => 'Gagal memperbarui entri rating'], 404);
                return;
            }
            Response::json($entry);
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal memperbarui entri rating', 'error' => $ex->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/rating/{id}',
        summary: 'Hapus entri rating (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Rating'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Entri rating berhasil dihapus'),
            new OA\Response(response: 404, description: 'Entri rating tidak ditemukan'),
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
            $deleted = $this->rating->delete($id);
            if (!$deleted) {
                Response::json(['message' => 'Entri rating tidak ditemukan'], 404);
                return;
            }
            Response::empty(204);
        } catch (PDOException $ex) {
            Response::json(['message' => 'Gagal menghapus entri rating', 'error' => $ex->getMessage()], 500);
        }
    }
}
