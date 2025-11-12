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
        new OA\Property(property: 'video_url', type: 'string', nullable: true, example: 'https://youtu.be/abc123'),
        new OA\Property(property: 'image_url', type: 'array', items: new OA\Items(type: 'string', example: 'https://cdn.example.com/images/proyek1.jpg')),
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
        new OA\Property(property: 'video_url', type: 'string', nullable:true, example: 'https://youtu.be/abc123'),
        new OA\Property(property: 'image_url', type: 'array', items: new OA\Items(type: 'string', example: 'https://cdn.example.com/images/proyek1.jpg')),
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
        new OA\Property(property: 'video_url', type: 'string', nullable:true, example: 'https://youtu.be/def456'),
        new OA\Property(property: 'image_url', type: 'array', items: new OA\Items(type: 'string', example: 'https://cdn.example.com/images/proyek2.jpg')),
        new OA\Property(property: 'status', type: 'string', example: 'completed', enum: ['active','inactive','completed'])
    ]
)]
class ProjectUpdateRequest
{
}
