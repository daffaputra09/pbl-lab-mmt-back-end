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
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/image.jpg')
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
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/webinar.jpg')
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
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/webinar-updated.jpg')
    ]
)]
class EventUpdateRequest
{
}

