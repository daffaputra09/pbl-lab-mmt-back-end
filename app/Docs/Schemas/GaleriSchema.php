<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Galeri',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'foto', enum: ['foto', 'video', 'lottie']),
        new OA\Property(property: 'file_url', type: 'string', example: '/uploads/galeri/file_6546f7a8e1234_1699012345.jpg'),
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
    required: ['type'],
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'foto', enum: ['foto', 'video', 'lottie'], description: 'Tipe file: "foto" untuk gambar (JPG, PNG, GIF, WEBP), "video" untuk video (MP4, MPEG, MOV, AVI), atau "lottie" untuk file animasi Lottie (JSON, max 100MB)'),
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File yang akan diupload. Untuk type "foto": JPG, PNG, GIF, WEBP (max 100MB). Untuk type "video": MP4, MPEG, MOV, AVI (max 100MB). Untuk type "lottie": JSON/Lottie files (max 100MB). Wajib diisi untuk flow upload file.'
        ),
        new OA\Property(property: 'tanggal_kegiatan', type: 'string', format: 'date', nullable: true, example: '2024-10-25', description: 'Tanggal kegiatan dalam format YYYY-MM-DD'),
    ]
)]
class GaleriCreateRequest
{
}

#[OA\Schema(
    schema: 'GaleriUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'foto', enum: ['foto', 'video', 'lottie'], description: 'Tipe file: "foto" untuk gambar, "video" untuk video, atau "lottie" untuk file animasi Lottie'),
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            nullable: true,
            description: 'File yang akan diupload. Untuk type "foto": JPG, PNG, GIF, WEBP (max 100MB). Untuk type "video": MP4, MPEG, MOV, AVI (max 100MB). Untuk type "lottie": JSON/Lottie files (max 100MB). Opsional - kirim hanya jika ingin mengubah file.'
        ),
        new OA\Property(property: 'tanggal_kegiatan', type: 'string', format: 'date', nullable: true, example: '2024-12-25', description: 'Tanggal kegiatan dalam format YYYY-MM-DD'),
    ]
)]
class GaleriUpdateRequest
{
}
