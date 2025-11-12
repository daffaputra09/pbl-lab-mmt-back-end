<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Galeri',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'Foto', enum: ['Foto', 'Video']),
        new OA\Property(property: 'file_url', type: 'string', example: 'https://cdn.example.com/media/123.jpg'),
        new OA\Property(property: 'tanggal_kegiatan', type: 'string', format: 'date', nullable: true, example: '2024-10-25'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-11-11T14:00:00Z')
    ]
)]
class GaleriSchema
{
}

#[OA\Schema(
    schema: 'GaleriCreateRequest',
    type: 'object',
    required: ['type', 'file_url'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'Foto', enum: ['Foto', 'Video']),
        new OA\Property(property: 'file_url', type: 'string', example: 'https://cdn.example.com/media/123.jpg'),
        new OA\Property(property: 'tanggal_kegiatan', type: 'string', format: 'date', nullable: true, example: '2024-10-25'),
    ]
)]
class GaleriCreateRequest
{
}

#[OA\Schema(
    schema: 'GaleriUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'Foto', enum: ['Foto', 'Video']),
        new OA\Property(property: 'file_url', type: 'string', example: 'https://cdn.example.com/media/123.jpg'),
        new OA\Property(property: 'tanggal_kegiatan', type: 'string', format: 'date', nullable: true, example: '2024-12-25'),
    ]
)]
class GaleriUpdateRequest
{
}
