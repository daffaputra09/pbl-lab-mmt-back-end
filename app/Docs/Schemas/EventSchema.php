<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Event',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'judul', type: 'string', example: 'Workshop PHP'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Deskripsi lengkap event'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/image.jpg'),
        new OA\Property(property: 'tanggal_event', type: 'string', format: 'date', nullable: true, example: '2024-12-31', description: 'Tanggal pelaksanaan event')
    ]
)]
class EventSchema
{
}

#[OA\Schema(
    schema: 'EventCreateRequest',
    type: 'object',
    required: ['judul'],
    properties: [
        new OA\Property(property: 'judul', type: 'string', example: 'Webinar Terbaru'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Pembahasan mendalam tentang framework baru'),
        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true, description: 'File gambar event (JPG, PNG, GIF, WEBP, max 5MB)'),
        new OA\Property(property: 'tanggal_event', type: 'string', format: 'date', nullable: true, example: '2024-12-31', description: 'Tanggal pelaksanaan event (format: YYYY-MM-DD)')
    ]
)]
class EventCreateRequest
{
}

#[OA\Schema(
    schema: 'EventUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'judul', type: 'string', example: 'Webinar Terbaru 2024'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Pembahasan mendalam dan update terkini'),
        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true, description: 'File gambar event (JPG, PNG, GIF, WEBP, max 5MB). Opsional - hanya upload jika ingin mengubah gambar'),
        new OA\Property(property: 'tanggal_event', type: 'string', format: 'date', nullable: true, example: '2024-12-31', description: 'Tanggal pelaksanaan event (format: YYYY-MM-DD). Opsional - hanya isi jika ingin mengubah tanggal')
    ]
)]
class EventUpdateRequest
{
}

#[OA\Schema(
    schema: 'EventPaginatedResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Event')
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
class EventPaginatedResponse
{
}

