<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PartnerLab',
    required: ['id', 'nama'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'nama', type: 'string', example: 'PT. Teknologi Indonesia'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Perusahaan teknologi terkemuka di Indonesia'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: '/uploads/partner-lab/logo_6546f7a8e1234_1699012345.png'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z')
    ]
)]
class PartnerLabSchema
{
}

#[OA\Schema(
    schema: 'PartnerLabCreateRequest',
    required: ['nama'],
    properties: [
        new OA\Property(property: 'nama', type: 'string', example: 'PT. Teknologi Indonesia', description: 'Nama partner lab'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Perusahaan teknologi terkemuka di Indonesia', description: 'Deskripsi partner lab'),
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'Gambar logo partner (JPG, PNG, GIF, WEBP, max 100MB). Wajib diisi untuk flow upload file.'
        )
    ]
)]
class PartnerLabCreateRequest
{
}

#[OA\Schema(
    schema: 'PartnerLabUpdateRequest',
    properties: [
        new OA\Property(property: 'nama', type: 'string', example: 'PT. Teknologi Indonesia', description: 'Nama partner lab'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Perusahaan teknologi terkemuka di Indonesia', description: 'Deskripsi partner lab'),
        new OA\Property(
            property: 'image',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'Gambar logo partner (JPG, PNG, GIF, WEBP, max 100MB). Opsional - kirim hanya jika ingin mengubah gambar. Untuk multipart/form-data.'
        ),
        new OA\Property(
            property: 'image_url',
            type: 'string',
            nullable: true,
            example: '/uploads/partner-lab/logo_6546f7a8e1234_1699012345.png',
            description: 'URL gambar yang sudah ada. Untuk JSON request.'
        )
    ]
)]
class PartnerLabUpdateRequest
{
}

#[OA\Schema(
    schema: 'PartnerLabPaginatedResponse',
    required: ['data'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PartnerLab')
        ),
        new OA\Property(
            property: 'pagination',
            type: 'object',
            nullable: true,
            description: 'Informasi pagination. Null jika limit tidak diisi (mengembalikan semua data)',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'limit', type: 'integer', example: 10),
                new OA\Property(property: 'total', type: 'integer', example: 20),
                new OA\Property(property: 'total_pages', type: 'integer', example: 2),
                new OA\Property(property: 'has_next', type: 'boolean', example: true),
                new OA\Property(property: 'has_prev', type: 'boolean', example: false)
            ]
        )
    ]
)]
class PartnerLabPaginatedResponse
{
}

