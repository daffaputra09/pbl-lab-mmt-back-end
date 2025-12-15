<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StatsResponse',
    required: ['project', 'gallery', 'berita', 'event'],
    properties: [
        new OA\Property(property: 'project', type: 'integer', format: 'int64', example: 15, description: 'Jumlah total data project'),
        new OA\Property(property: 'gallery', type: 'integer', format: 'int64', example: 42, description: 'Jumlah total data gallery'),
        new OA\Property(property: 'berita', type: 'integer', format: 'int64', example: 28, description: 'Jumlah total data berita'),
        new OA\Property(property: 'event', type: 'integer', format: 'int64', example: 10, description: 'Jumlah total data event')
    ]
)]
class StatsSchema
{
}

