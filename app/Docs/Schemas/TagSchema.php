<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Tag',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Robotika'),
    ]
)]
class TagSchema
{
}

#[OA\Schema(
    schema: 'TagCreateRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Robotika'),
    ]
)]
class TagCreateRequest
{
}

#[OA\Schema(
    schema: 'TagUpdateRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Robotika Modern'),
    ]
)]
class TagUpdateRequest
{
}

