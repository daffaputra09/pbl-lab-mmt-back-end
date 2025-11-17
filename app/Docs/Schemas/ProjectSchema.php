<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Project',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Website Sekolah Baru'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi detail proyek pengembangan website sekolah…'),
        new OA\Property(property: 'id_kategori', type: 'integer', example: 3),
        new OA\Property(property: 'video_url', type: 'string', nullable: true, example: '/uploads/project/file_6546f7a8e1234_1699012345.mp4'),
        new OA\Property(property: 'image_url', type: 'array', items: new OA\Items(type: 'string', example: '/uploads/project/file_6546f7a8e1234_1699012345.jpg')),
        new OA\Property(property: 'status', type: 'string', example: 'active', enum: ['active','inactive','completed']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-12T09:30:00Z')
    ]
)]
class ProjectSchema
{
}

#[OA\Schema(
    schema: 'ProjectCreateRequest',
    type: 'object',
    required: ['name','id_kategori'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Website Sekolah Baru'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi detail proyek pengembangan website sekolah…'),
        new OA\Property(property: 'id_kategori', type: 'integer', example: 3),
        new OA\Property(
            property: 'video',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File video project (MP4, MPEG, MOV, AVI, max 100MB). Opsional - upload video project.'
        ),
        new OA\Property(
            property: 'images',
            type: 'array',
            items: new OA\Items(
                type: 'string',
                format: 'binary'
            ),
            nullable: true,
            description: 'Array of image files (JPG, PNG, GIF, WEBP, max 100MB each). Opsional - upload multiple images untuk project. Gunakan field name "images[]" saat upload.'
        ),
        new OA\Property(property: 'status', type: 'string', example: 'active', enum: ['active','inactive','completed'])
    ]
)]
class ProjectCreateRequest
{
}

#[OA\Schema(
    schema: 'ProjectUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Website Sekolah Baru – Versi 2'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi yang diperbarui…'),
        new OA\Property(property: 'id_kategori', type: 'integer', example: 4),
        new OA\Property(
            property: 'video',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File video project (MP4, MPEG, MOV, AVI, max 100MB). Opsional - kirim hanya jika ingin mengubah video. Video lama akan otomatis terhapus.'
        ),
        new OA\Property(
            property: 'images',
            type: 'array',
            items: new OA\Items(
                type: 'string',
                format: 'binary'
            ),
            nullable: true,
            description: 'Array of image files (JPG, PNG, GIF, WEBP, max 100MB each). Opsional - kirim hanya jika ingin mengubah images. Semua images lama akan otomatis terhapus. Gunakan field name "images[]" saat upload.'
        ),
        new OA\Property(property: 'status', type: 'string', example: 'completed', enum: ['active','inactive','completed'])
    ]
)]
class ProjectUpdateRequest
{
}
