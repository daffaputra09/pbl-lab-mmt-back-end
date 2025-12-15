<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Middleware\AuthMiddleware;
use App\Models\ProfileLab;
use Config\Database;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Profile Lab')]
class ProfileLabController
{
    private ProfileLab $profileLab;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->profileLab = new ProfileLab($connection);
    }

    #[OA\Get(
        path: '/profile-lab',
        summary: 'Mendapatkan informasi profile lab',
        tags: ['Profile Lab'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data informasi tentang lab',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfileLab')
            ),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
            new OA\Response(response: 500, description: 'Gagal mengambil data')
        ]
    )]
    public function show(): void
    {
        try {
            $data = $this->profileLab->find();
            
            if ($data === null) {
                Response::json(['message' => 'Data profile lab tidak ditemukan'], 404);
                return;
            }
            
            Response::json($data);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data profile lab', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Post(
        path: '/profile-lab',
        summary: 'Membuat atau mengupdate data informasi profile lab (requires authentication). Jika belum ada data, akan membuat data baru. Jika sudah ada, akan mengupdate data yang ada.',
        security: [['bearerAuth' => []]],
        tags: ['Profile Lab'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProfileLabCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Data profile lab berhasil dibuat (data baru)',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfileLab')
            ),
            new OA\Response(
                response: 200,
                description: 'Data profile lab berhasil diupdate (data sudah ada)',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfileLab')
            ),
            new OA\Response(response: 400, description: 'Data tidak valid'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid'),
            new OA\Response(response: 500, description: 'Gagal membuat atau mengupdate data profile lab')
        ]
    )]
    public function store(): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return;
        }

        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        try {
            if (!isset($payload['misi']) || !is_array($payload['misi'])) {
                Response::json(['message' => 'Field misi harus berupa array'], 422);
                return;
            }
            
            if (empty($payload['misi'])) {
                Response::json(['message' => 'Field misi tidak boleh kosong'], 422);
                return;
            }
            
            if (!isset($payload['visi']) || empty(trim($payload['visi']))) {
                Response::json(['message' => 'Field visi wajib diisi'], 422);
                return;
            }
            
            if (!isset($payload['sejarah']) || empty(trim($payload['sejarah']))) {
                Response::json(['message' => 'Field sejarah wajib diisi'], 422);
                return;
            }
            
            if (!isset($payload['nilai_inti']) || empty(trim($payload['nilai_inti']))) {
                Response::json(['message' => 'Field nilai_inti wajib diisi'], 422);
                return;
            }
            
            $misi = array_map('trim', $payload['misi']);
            $misi = array_filter($misi, function($item) {
                return !empty($item);
            });
            
            if (empty($misi)) {
                Response::json(['message' => 'Field misi harus memiliki minimal satu item'], 422);
                return;
            }
            
            // Cek apakah sudah ada data
            $existing = $this->profileLab->find();
            
            if ($existing === null) {
                // Belum ada data, lakukan create
                $result = $this->profileLab->create(
                    array_values($misi),
                    trim($payload['visi']),
                    trim($payload['sejarah']),
                    trim($payload['nilai_inti'])
                );
                
                Response::json($result, 201);
            } else {
                // Sudah ada data, lakukan update
                $result = $this->profileLab->update(
                    $existing['id'],
                    array_values($misi),
                    trim($payload['visi']),
                    trim($payload['sejarah']),
                    trim($payload['nilai_inti'])
                );
                
                if ($result === null) {
                    Response::json(['message' => 'Gagal mengupdate data profile lab'], 500);
                    return;
                }
                
                Response::json($result, 200);
            }
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal membuat atau mengupdate data profile lab', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Put(
        path: '/profile-lab/{id}',
        summary: 'Mengupdate data informasi profile lab (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Profile Lab'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProfileLabUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data profile lab berhasil diupdate',
                content: new OA\JsonContent(ref: '#/components/schemas/ProfileLab')
            ),
            new OA\Response(response: 400, description: 'Data tidak valid'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid'),
            new OA\Response(response: 500, description: 'Gagal mengupdate data tentang lab')
        ]
    )]
    public function update(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return;
        }

        try {
            $payload = Request::json();
        } catch (InvalidArgumentException $exception) {
            Response::json(['message' => $exception->getMessage()], 400);
            return;
        }

        try {
            if (!isset($payload['misi']) || !is_array($payload['misi'])) {
                Response::json(['message' => 'Field misi harus berupa array'], 422);
                return;
            }
            
            if (empty($payload['misi'])) {
                Response::json(['message' => 'Field misi tidak boleh kosong'], 422);
                return;
            }
            
            if (!isset($payload['visi']) || empty(trim($payload['visi']))) {
                Response::json(['message' => 'Field visi wajib diisi'], 422);
                return;
            }
            
            if (!isset($payload['sejarah']) || empty(trim($payload['sejarah']))) {
                Response::json(['message' => 'Field sejarah wajib diisi'], 422);
                return;
            }
            
            if (!isset($payload['nilai_inti']) || empty(trim($payload['nilai_inti']))) {
                Response::json(['message' => 'Field nilai_inti wajib diisi'], 422);
                return;
            }
            
            $misi = array_map('trim', $payload['misi']);
            $misi = array_filter($misi, function($item) {
                return !empty($item);
            });
            
            if (empty($misi)) {
                Response::json(['message' => 'Field misi harus memiliki minimal satu item'], 422);
                return;
            }
            
            $result = $this->profileLab->update(
                $id,
                array_values($misi),
                trim($payload['visi']),
                trim($payload['sejarah']),
                trim($payload['nilai_inti'])
            );
            
            if ($result === null) {
                Response::json(['message' => 'Data profile lab tidak ditemukan'], 404);
                return;
            }
            
            Response::json($result);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengupdate data profile lab', 'error' => $exception->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/profile-lab/{id}',
        summary: 'Menghapus data informasi profile lab (requires authentication)',
        security: [['bearerAuth' => []]],
        tags: ['Profile Lab'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Data profile lab berhasil dihapus'),
            new OA\Response(response: 404, description: 'Data tidak ditemukan'),
            new OA\Response(response: 401, description: 'Unauthorized - Token tidak valid'),
            new OA\Response(response: 500, description: 'Gagal menghapus data tentang lab')
        ]
    )]
    public function destroy(int $id): void
    {
        $tokenData = AuthMiddleware::verify();
        if ($tokenData === null) {
            return;
        }

        try {
            $deleted = $this->profileLab->delete($id);
            
            if (!$deleted) {
                Response::json(['message' => 'Data profile lab tidak ditemukan'], 404);
                return;
            }
            
            Response::empty(204);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal menghapus data profile lab', 'error' => $exception->getMessage()], 500);
        }
    }
}

