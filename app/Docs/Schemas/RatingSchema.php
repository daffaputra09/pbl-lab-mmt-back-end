<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Rating',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'id_project', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'rating', type: 'integer', example: 4, minimum: 1, maximum: 5),
        new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Great project!'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-12T08:00:00Z')
    ]
)]
class RatingSchema
{
}

#[OA\Schema(
    schema: 'RatingCreateRequest',
    type: 'object',
    required: ['id_project','name','rating'],
    properties: [
        new OA\Property(property: 'id_project', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'rating', type: 'integer', example: 5, minimum: 1, maximum: 5),
        new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Really enjoyed this project')
    ]
)]
class RatingCreateRequest
{
}

#[OA\Schema(
    schema: 'RatingUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'id_project', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'rating', type: 'integer', example: 3, minimum: 1, maximum: 5),
        new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Updated comment')
    ]
)]
class RatingUpdateRequest
{
}
