<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Models\Berita;
use App\Models\Event;
use App\Models\Galeri;
use App\Models\Project;
use Config\Database;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Statistics')]
class StatsController
{
    private Project $project;
    private Galeri $galeri;
    private Berita $berita;
    private Event $event;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->project = new Project($connection);
        $this->galeri = new Galeri($connection);
        $this->berita = new Berita($connection);
        $this->event = new Event($connection);
    }

    #[OA\Get(
        path: '/stats',
        summary: 'Mendapatkan jumlah data statistik (project, gallery, berita, event)',
        tags: ['Statistics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data statistik berhasil diambil',
                content: new OA\JsonContent(ref: '#/components/schemas/StatsResponse')
            ),
            new OA\Response(response: 500, description: 'Gagal mengambil data statistik')
        ]
    )]
    public function index(): void
    {
        try {
            $projectCount = $this->project->count();
            $galeriCount = $this->galeri->count();
            $beritaCount = $this->berita->count();
            $eventCount = $this->event->count();

            Response::json([
                'project' => $projectCount,
                'gallery' => $galeriCount,
                'berita' => $beritaCount,
                'event' => $eventCount,
            ]);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data statistik', 'error' => $exception->getMessage()], 500);
        }
    }
}

