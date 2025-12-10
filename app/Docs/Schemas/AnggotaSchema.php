<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Anggota',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nama', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'role', type: 'string', nullable: true, example: 'Full Stack Developer'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/uploads/anggota/file_123.jpg'),
        new OA\Property(
            property: 'skills',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'string'),
            example: ['PHP', 'JavaScript', 'React', 'Node.js']
        ),
        new OA\Property(
            property: 'media_social',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'url', type: 'string', example: 'https://github.com/johndoe'),
                    new OA\Property(property: 'type', type: 'string', example: 'github', description: 'Jenis media social (contoh: github, linkedin, twitter, instagram, dll)')
                ],
                required: ['url', 'type']
            ),
            example: [
                ['url' => 'https://github.com/johndoe', 'type' => 'github'],
                ['url' => 'https://linkedin.com/in/johndoe', 'type' => 'linkedin']
            ]
        )
    ]
)]
class AnggotaSchema
{
}

#[OA\Schema(
    schema: 'AnggotaCreateRequest',
    type: 'object',
    required: ['nama'],
    properties: [
        new OA\Property(property: 'nama', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'role', type: 'string', nullable: true, example: 'Full Stack Developer'),
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File gambar anggota (JPG, PNG, GIF, WEBP, max 5MB)'
        ),
        new OA\Property(
            property: 'skills',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'string'),
            example: ['PHP', 'JavaScript', 'React'],
            description: 'Array of skills. Can be sent as JSON string or array in multipart/form-data'
        ),
        new OA\Property(
            property: 'media_social',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'url', type: 'string', example: 'https://github.com/johndoe'),
                    new OA\Property(property: 'type', type: 'string', example: 'github', description: 'Jenis media social (contoh: github, linkedin, twitter, instagram, dll)')
                ],
                required: ['url', 'type']
            ),
            example: [
                ['url' => 'https://github.com/johndoe', 'type' => 'github'],
                ['url' => 'https://linkedin.com/in/johndoe', 'type' => 'linkedin']
            ],
            description: 'Array of social media objects. Each object must have url and type. Can be sent as JSON string or array in multipart/form-data'
        )
    ]
)]
class AnggotaCreateRequest
{
}

#[OA\Schema(
    schema: 'AnggotaUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'nama', type: 'string', example: 'John Doe Updated'),
        new OA\Property(property: 'role', type: 'string', nullable: true, example: 'Senior Full Stack Developer'),
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File gambar anggota (JPG, PNG, GIF, WEBP, max 5MB). Opsional - hanya upload jika ingin mengubah gambar'
        ),
        new OA\Property(
            property: 'image_url',
            type: 'string',
            nullable: true,
            example: '/uploads/anggota/file_123.jpg',
            description: 'URL gambar anggota. Digunakan untuk JSON (PUT)'
        ),
        new OA\Property(
            property: 'skills',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'string'),
            example: ['PHP', 'JavaScript', 'React', 'Node.js', 'TypeScript']
        ),
        new OA\Property(
            property: 'media_social',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'url', type: 'string', example: 'https://github.com/johndoe'),
                    new OA\Property(property: 'type', type: 'string', example: 'github', description: 'Jenis media social (contoh: github, linkedin, twitter, instagram, dll)')
                ],
                required: ['url', 'type']
            ),
            example: [
                ['url' => 'https://github.com/johndoe', 'type' => 'github'],
                ['url' => 'https://linkedin.com/in/johndoe', 'type' => 'linkedin'],
                ['url' => 'https://twitter.com/johndoe', 'type' => 'twitter']
            ]
        )
    ]
)]
class AnggotaUpdateRequest
{
}

#[OA\Schema(
    schema: 'AnggotaPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Anggota')
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
class AnggotaPaginatedResponse
{
}

