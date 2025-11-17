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
        new OA\Property(property: 'image_url', type: 'string', example: '/uploads/berita/file_6546f7a8e1234_1699012345.jpg'),
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
    required: ['judul','description'],
    properties: [
        new OA\Property(property: 'judul', type: 'string', example: 'Pemberitaan Baru Tentang Sekolah'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi lengkap tentang berita...'),
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File gambar berita (JPG, PNG, GIF, WEBP, max 5MB). Wajib diisi untuk flow upload file.'
        ),
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
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File gambar berita (JPG, PNG, GIF, WEBP, max 5MB). Opsional - kirim hanya jika ingin mengubah gambar.'
        ),
        new OA\Property(property: 'id_user', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'status', type: 'string', example: 'draft', enum: ['published','draft','archived'])
    ]
)]
class BeritaUpdateRequest
{
}
