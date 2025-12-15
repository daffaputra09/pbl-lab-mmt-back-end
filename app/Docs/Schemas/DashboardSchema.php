<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DashboardResponse',
    required: ['summary', 'average_rating', 'recent', 'charts'],
    properties: [
        new OA\Property(
            property: 'summary',
            type: 'object',
            description: 'Ringkasan statistik total data',
            properties: [
                new OA\Property(property: 'project', type: 'integer', example: 15),
                new OA\Property(property: 'gallery', type: 'integer', example: 42),
                new OA\Property(property: 'berita', type: 'integer', example: 28),
                new OA\Property(property: 'event', type: 'integer', example: 10),
                new OA\Property(property: 'rating', type: 'integer', example: 35),
                new OA\Property(property: 'anggota', type: 'integer', example: 8),
                new OA\Property(property: 'kategori', type: 'integer', example: 5),
            ]
        ),
        new OA\Property(property: 'average_rating', type: 'number', format: 'float', nullable: true, example: 4.5, description: 'Rata-rata rating dari semua rating'),
        new OA\Property(
            property: 'recent',
            type: 'object',
            description: 'Data terkini (5 terbaru)',
            properties: [
                new OA\Property(
                    property: 'projects',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'status', type: 'string'),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'berita',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'status', type: 'string'),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'events',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'tanggal_event', type: 'string', format: 'date', nullable: true),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'gallery',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'type', type: 'string'),
                            new OA\Property(property: 'tanggal_kegiatan', type: 'string', format: 'date', nullable: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'ratings',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'id_project', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'rating', type: 'integer'),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        ]
                    )
                ),
            ]
        ),
        new OA\Property(
            property: 'charts',
            type: 'object',
            description: 'Data untuk chart (recharts)',
            properties: [
                new OA\Property(
                    property: 'project_by_status',
                    type: 'array',
                    description: 'Data untuk Pie/Bar chart: Project berdasarkan status',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'on_progress'),
                            new OA\Property(property: 'value', type: 'integer', example: 10),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'berita_by_status',
                    type: 'array',
                    description: 'Data untuk Pie chart: Berita berdasarkan status',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'published'),
                            new OA\Property(property: 'value', type: 'integer', example: 25),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'event_by_status',
                    type: 'array',
                    description: 'Data untuk Pie chart: Event berdasarkan status (akan_datang, terlewat, tidak_ditentukan)',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'akan_datang'),
                            new OA\Property(property: 'value', type: 'integer', example: 5),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'gallery_by_type',
                    type: 'array',
                    description: 'Data untuk Pie chart: Gallery berdasarkan type (foto, video, lottie)',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'foto'),
                            new OA\Property(property: 'value', type: 'integer', example: 30),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'project_by_kategori',
                    type: 'array',
                    description: 'Data untuk Bar/Pie chart: Project berdasarkan kategori',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'Web Development'),
                            new OA\Property(property: 'value', type: 'integer', example: 8),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'rating_distribution',
                    type: 'array',
                    description: 'Data untuk Bar chart: Distribusi rating (1-5 stars)',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'name', type: 'integer', example: 5),
                            new OA\Property(property: 'value', type: 'integer', example: 20),
                        ]
                    )
                ),
                new OA\Property(
                    property: 'activity_timeline',
                    type: 'array',
                    description: 'Data untuk Line/Area chart: Timeline aktivitas 12 bulan terakhir',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'month', type: 'string', example: '2024-01'),
                            new OA\Property(property: 'project', type: 'integer', example: 3),
                            new OA\Property(property: 'berita', type: 'integer', example: 5),
                            new OA\Property(property: 'event', type: 'integer', example: 2),
                        ]
                    )
                ),
            ]
        ),
    ]
)]
class DashboardSchema
{
}

