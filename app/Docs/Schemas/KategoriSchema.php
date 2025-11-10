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

