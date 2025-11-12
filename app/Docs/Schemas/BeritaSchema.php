<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Berita',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'judul', type: 'string', example: 'Pemberitaan Baru Tentang Sekolah'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi lengkap tentang berita...'),
        new OA\Property(property: 'image_url', type: 'string', example: 'https://cdn.example.com/images/berita123.jpg'),
        new OA\Property(property: 'id_user', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'status', type: 'string', example: 'published', enum: ['published','draft','archived']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-12T08:00:00Z')
    ]
)]
class BeritaSchema
{
}

#[OA\Schema(
    schema: 'BeritaCreateRequest',
    type: 'object',
    required: ['judul','description','image_url'],
    properties: [
        new OA\Property(property: 'judul', type: 'string', example: 'Pemberitaan Baru Tentang Sekolah'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi lengkap tentang berita...'),
        new OA\Property(property: 'image_url', type: 'string', example: 'https://cdn.example.com/images/berita123.jpg'),
        new OA\Property(property: 'id_user', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'status', type: 'string', example: 'published', enum: ['published','draft','archived'])
    ]
)]
class BeritaCreateRequest
{
}

#[OA\Schema(
    schema: 'BeritaUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'judul', type: 'string', example: 'Pembaruan Judul Berita'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi yang diperbarui...'),
        new OA\Property(property: 'image_url', type: 'string', example: 'https://cdn.example.com/images/berita456.jpg'),
        new OA\Property(property: 'id_user', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'status', type: 'string', example: 'draft', enum: ['published','draft','archived'])
    ]
)]
class BeritaUpdateRequest
{
}
