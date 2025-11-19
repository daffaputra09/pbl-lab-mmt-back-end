<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Kategori',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Robotika'),
    ]
)]
class KategoriSchema
{
}

#[OA\Schema(
    schema: 'KategoriCreateRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Robotika'),
    ]
)]
class KategoriCreateRequest
{
}

#[OA\Schema(
    schema: 'KategoriUpdateRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Robotika Modern'),
    ]
)]
class KategoriUpdateRequest
{
}

#[OA\Schema(
    schema: 'KategoriPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Kategori')
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
class KategoriPaginatedResponse
{
}

