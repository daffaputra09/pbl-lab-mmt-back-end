<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Rating',
    type: 'object',
    description: 'Schema untuk entri rating',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'ID unik dari rating', example: 1),
        new OA\Property(property: 'id_project', type: 'integer', description: 'ID project yang diberi rating', example: 1),
        new OA\Property(property: 'name', type: 'string', description: 'Nama pemberi rating', example: 'John Doe'),
        new OA\Property(property: 'rating', type: 'integer', description: 'Nilai rating (1-5)', minimum: 1, maximum: 5, example: 4),
        new OA\Property(property: 'comment', type: 'string', nullable: true, description: 'Komentar dari pemberi rating', example: 'Great project!'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', description: 'Waktu rating dibuat', example: '2025-11-12T08:00:00Z')
    ]
)]
class RatingSchema
{
}

#[OA\Schema(
    schema: 'RatingCreateRequest',
    type: 'object',
    description: 'Schema untuk request membuat rating baru',
    required: ['id_project','name','rating'],
    properties: [
        new OA\Property(property: 'id_project', type: 'integer', description: 'ID project yang akan diberi rating', example: 1),
        new OA\Property(property: 'name', type: 'string', description: 'Nama pemberi rating', example: 'John Doe'),
        new OA\Property(property: 'rating', type: 'integer', description: 'Nilai rating (1-5)', minimum: 1, maximum: 5, example: 5),
        new OA\Property(property: 'comment', type: 'string', nullable: true, description: 'Komentar dari pemberi rating (opsional)', example: 'Really enjoyed this project')
    ]
)]
class RatingCreateRequest
{
}

#[OA\Schema(
    schema: 'RatingUpdateRequest',
    type: 'object',
    description: 'Schema untuk request update rating. Semua field bersifat opsional.',
    properties: [
        new OA\Property(property: 'id_project', type: 'integer', description: 'ID project yang diberi rating', example: 2),
        new OA\Property(property: 'name', type: 'string', description: 'Nama pemberi rating', example: 'Jane Doe'),
        new OA\Property(property: 'rating', type: 'integer', description: 'Nilai rating (1-5)', minimum: 1, maximum: 5, example: 3),
        new OA\Property(property: 'comment', type: 'string', nullable: true, description: 'Komentar dari pemberi rating', example: 'Updated comment')
    ]
)]
class RatingUpdateRequest
{
}

#[OA\Schema(
    schema: 'RatingPaginatedResponse',
    type: 'object',
    description: 'Schema untuk response list rating dengan pagination',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            description: 'Array dari entri rating',
            items: new OA\Items(ref: '#/components/schemas/Rating')
        ),
        new OA\Property(
            property: 'pagination',
            type: 'object',
            nullable: true,
            description: 'Informasi pagination. Null jika limit tidak diisi (mengembalikan semua data)',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', description: 'Halaman saat ini', example: 1),
                new OA\Property(property: 'limit', type: 'integer', description: 'Jumlah item per halaman', example: 10),
                new OA\Property(property: 'total', type: 'integer', description: 'Total jumlah rating', example: 100),
                new OA\Property(property: 'total_pages', type: 'integer', description: 'Total jumlah halaman', example: 10),
                new OA\Property(property: 'has_next', type: 'boolean', description: 'Apakah ada halaman berikutnya', example: true),
                new OA\Property(property: 'has_prev', type: 'boolean', description: 'Apakah ada halaman sebelumnya', example: false)
            ]
        )
    ]
)]
class RatingPaginatedResponse
{
}
