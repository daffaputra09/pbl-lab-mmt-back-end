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
        new OA\Property(property: 'id_kategori', type: 'integer', example: 3, description: 'ID kategori (deprecated, gunakan kategori object)'),
        new OA\Property(
            property: 'kategori',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 3),
                new OA\Property(property: 'name', type: 'string', example: 'Robotika')
            ],
            description: 'Object kategori yang berisi id dan name'
        ),
        new OA\Property(property: 'video_url', type: 'string', nullable: true, example: '/uploads/project/file_6546f7a8e1234_1699012345.mp4'),
        new OA\Property(property: 'image_url', type: 'array', items: new OA\Items(type: 'string', example: '/uploads/project/file_6546f7a8e1234_1699012345.jpg')),
        new OA\Property(property: 'status', type: 'string', example: 'on_progress', enum: ['on_progress','completed']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-12T09:30:00Z'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Tag'),
            description: 'Daftar tag yang terkait dengan project ini (berisi id dan name)'
        )
    ]
)]
class ProjectSchema
{
}

#[OA\Schema(
    schema: 'ProjectCreateRequest',
    type: 'object',
    required: ['name','id_kategori','tag_ids'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Website Sekolah Baru'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi detail proyek pengembangan website sekolah…'),
        new OA\Property(property: 'id_kategori', type: 'integer', example: 3),
        new OA\Property(
            property: 'tag_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of tag IDs yang wajib diisi. Minimal 1 tag harus dipilih. Untuk multipart/form-data, gunakan field name "tag_ids[]" atau kirim sebagai array.',
            example: [1, 2, 3]
        ),
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
        new OA\Property(property: 'status', type: 'string', example: 'on_progress', enum: ['on_progress','completed'])
    ]
)]
class ProjectCreateRequest
{
}

#[OA\Schema(
    schema: 'ProjectUpdateRequest',
    type: 'object',
    required: ['tag_ids'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Website Sekolah Baru – Versi 2'),
        new OA\Property(property: 'description', type: 'string', example: 'Deskripsi yang diperbarui…'),
        new OA\Property(property: 'id_kategori', type: 'integer', example: 4),
        new OA\Property(
            property: 'tag_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of tag IDs yang wajib diisi. Minimal 1 tag harus dipilih. Untuk multipart/form-data, gunakan field name "tag_ids[]" atau kirim sebagai array.',
            example: [1, 2, 3]
        ),
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
        new OA\Property(property: 'status', type: 'string', example: 'completed', enum: ['on_progress','completed'])
    ]
)]
class ProjectUpdateRequest
{
}

#[OA\Schema(
    schema: 'ProjectPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Project')
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
class ProjectPaginatedResponse
{
}
