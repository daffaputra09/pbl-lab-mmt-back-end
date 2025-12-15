<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProfileLab',
    required: ['id', 'misi', 'visi', 'sejarah', 'nilai_inti'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'misi',
            type: 'array',
            description: 'Array of mission statements',
            items: new OA\Items(type: 'string'),
            example: [
                'Menyelenggarakan pendidikan praktikum dan penelitian yang berkualitas di bidang aplikasi teknologi Immersive, mobile, multimedia, serta teknologi interaktif.',
                'Mengembangkan riset dan inovasi berbasis mobile computing, multimedia, VR/AR, dan sensor interaktif untuk mendukung kemajuan ilmu pengetahuan dan teknologi',
                'Menyediakan fasilitas laboratorium yang modern dan relevan dengan perkembangan industri agar mahasiswa dapat menguasai keterampilan yang aplikatif'
            ]
        ),
        new OA\Property(
            property: 'visi',
            type: 'string',
            example: 'Memposisikan Lab Multimedia & Game sebagai pusat keunggulan yang responsif terhadap kebutuhan industri dan pendidikan tinggi.'
        ),
        new OA\Property(
            property: 'sejarah',
            type: 'string',
            example: 'Laboratorium Multimedia dan Perangkat Bergerak merupakan salah satu laboratorium unggulan di Jurusan Teknologi Informasi yang berfokus pada riset, pengembangan, serta implementasi teknologi mobile computing dan multimedia interaktif.'
        ),
        new OA\Property(
            property: 'nilai_inti',
            type: 'string',
            example: 'Membangun ekosistem pembelajaran yang mendukung pertumbuhan talenta digital.'
        )
    ]
)]
class ProfileLabSchema
{
}

#[OA\Schema(
    schema: 'ProfileLabCreateRequest',
    required: ['misi', 'visi', 'sejarah', 'nilai_inti'],
    properties: [
        new OA\Property(
            property: 'misi',
            type: 'array',
            description: 'Array of mission statements (minimal 1 item)',
            items: new OA\Items(type: 'string'),
            example: [
                'Menyelenggarakan pendidikan praktikum dan penelitian yang berkualitas di bidang aplikasi teknologi Immersive, mobile, multimedia, serta teknologi interaktif.',
                'Mengembangkan riset dan inovasi berbasis mobile computing, multimedia, VR/AR, dan sensor interaktif untuk mendukung kemajuan ilmu pengetahuan dan teknologi'
            ]
        ),
        new OA\Property(
            property: 'visi',
            type: 'string',
            example: 'Memposisikan Lab Multimedia & Game sebagai pusat keunggulan yang responsif terhadap kebutuhan industri dan pendidikan tinggi.'
        ),
        new OA\Property(
            property: 'sejarah',
            type: 'string',
            example: 'Laboratorium Multimedia dan Perangkat Bergerak merupakan salah satu laboratorium unggulan di Jurusan Teknologi Informasi yang berfokus pada riset, pengembangan, serta implementasi teknologi mobile computing dan multimedia interaktif.'
        ),
        new OA\Property(
            property: 'nilai_inti',
            type: 'string',
            example: 'Membangun ekosistem pembelajaran yang mendukung pertumbuhan talenta digital.'
        )
    ]
)]
class ProfileLabCreateRequest
{
}

#[OA\Schema(
    schema: 'ProfileLabUpdateRequest',
    required: ['misi', 'visi', 'sejarah', 'nilai_inti'],
    properties: [
        new OA\Property(
            property: 'misi',
            type: 'array',
            description: 'Array of mission statements (minimal 1 item)',
            items: new OA\Items(type: 'string'),
            example: [
                'Menyelenggarakan pendidikan praktikum dan penelitian yang berkualitas di bidang aplikasi teknologi Immersive, mobile, multimedia, serta teknologi interaktif.',
                'Mengembangkan riset dan inovasi berbasis mobile computing, multimedia, VR/AR, dan sensor interaktif untuk mendukung kemajuan ilmu pengetahuan dan teknologi'
            ]
        ),
        new OA\Property(
            property: 'visi',
            type: 'string',
            example: 'Memposisikan Lab Multimedia & Game sebagai pusat keunggulan yang responsif terhadap kebutuhan industri dan pendidikan tinggi.'
        ),
        new OA\Property(
            property: 'sejarah',
            type: 'string',
            example: 'Laboratorium Multimedia dan Perangkat Bergerak merupakan salah satu laboratorium unggulan di Jurusan Teknologi Informasi yang berfokus pada riset, pengembangan, serta implementasi teknologi mobile computing dan multimedia interaktif.'
        ),
        new OA\Property(
            property: 'nilai_inti',
            type: 'string',
            example: 'Membangun ekosistem pembelajaran yang mendukung pertumbuhan talenta digital.'
        )
    ]
)]
class ProfileLabUpdateRequest
{
}

