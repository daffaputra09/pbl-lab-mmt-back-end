<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Berita',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Pemberitaan Baru Tentang Sekolah'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi lengkap tentang berita...'),
        new OA\Property(property: 'image_url', type: 'string', example: '/uploads/berita/file_6546f7a8e1234_1699012345.jpg'),
        new OA\Property(property: 'id_user', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'status', type: 'string', example: 'published', enum: ['published','draft','archived']),
        new OA\Property(
            property: 'user',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 5),
                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'email', type: 'string', example: 'john@example.com')
            ]
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-12T08:00:00Z')
    ]
)]
class BeritaSchema
{
}

#[OA\Schema(
    schema: 'BeritaCreateRequest',
    type: 'object',
    required: ['title','description'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Pemberitaan Baru Tentang Sekolah'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi lengkap tentang berita...'),
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File gambar berita (JPG, PNG, GIF, WEBP, max 5MB). Wajib diisi untuk flow upload file.'
        ),
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
        new OA\Property(property: 'title', type: 'string', example: 'Pembaruan Judul Berita'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi yang diperbarui...'),
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File gambar berita (JPG, PNG, GIF, WEBP, max 5MB). Opsional - kirim hanya jika ingin mengubah gambar.'
        ),
        new OA\Property(property: 'status', type: 'string', example: 'draft', enum: ['published','draft','archived'])
    ]
)]
class BeritaUpdateRequest
{
}

#[OA\Schema(
    schema: 'BeritaPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Berita')
        ),
        new OA\Property(
            property: 'pagination',
            type: 'object',
            nullable: true,
            description: 'Informasi pagination. Null jika limit tidak diisi (mengembalikan semua data)',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'limit', type: 'integer', example: 10),
                new OA\Property(property: 'total', type: 'integer', example: 100),
                new OA\Property(property: 'total_pages', type: 'integer', example: 10),
                new OA\Property(property: 'has_next', type: 'boolean', example: true),
                new OA\Property(property: 'has_prev', type: 'boolean', example: false)
            ]
        )
    ]
)]
class BeritaPaginatedResponse
{
}
