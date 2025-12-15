<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Models\Anggota;
use App\Models\Berita;
use App\Models\Event;
use App\Models\Galeri;
use App\Models\Kategori;
use App\Models\Project;
use App\Models\Rating;
use Config\Database;
use OpenApi\Attributes as OA;
use PDOException;

#[OA\Tag(name: 'Dashboard')]
class DashboardController
{
    private Project $project;
    private Galeri $galeri;
    private Berita $berita;
    private Event $event;
    private Rating $rating;
    private Anggota $anggota;
    private Kategori $kategori;
    private \PDO $db;

    public function __construct()
    {
        $connection = (new Database())->getConnection();
        $this->db = $connection;
        $this->project = new Project($connection);
        $this->galeri = new Galeri($connection);
        $this->berita = new Berita($connection);
        $this->event = new Event($connection);
        $this->rating = new Rating($connection);
        $this->anggota = new Anggota($connection);
        $this->kategori = new Kategori($connection);
    }

    #[OA\Get(
        path: '/dashboard',
        summary: 'Mendapatkan data lengkap untuk dashboard admin (statistik, data terkini, dan data chart)',
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data dashboard berhasil diambil',
                content: new OA\JsonContent(ref: '#/components/schemas/DashboardResponse')
            ),
            new OA\Response(response: 500, description: 'Gagal mengambil data dashboard')
        ]
    )]
    public function index(): void
    {
        try {
            // Summary Statistics
            $summary = [
                'project' => $this->project->count(),
                'gallery' => $this->galeri->count(),
                'berita' => $this->berita->count(),
                'event' => $this->event->count(),
                'rating' => $this->rating->count(),
                'anggota' => $this->anggota->count(),
                'kategori' => $this->kategori->count(),
            ];

            // Average Rating
            $avgRating = $this->getAverageRating();

            // Recent Data (5 terbaru)
            $recent = [
                'projects' => $this->getRecentProjects(5),
                'berita' => $this->getRecentBerita(5),
                'events' => $this->getRecentEvents(5),
                'gallery' => $this->getRecentGallery(5),
                'ratings' => $this->getRecentRatings(5),
            ];

            // Chart Data
            $charts = [
                'project_by_status' => $this->getProjectByStatus(),
                'berita_by_status' => $this->getBeritaByStatus(),
                'event_by_status' => $this->getEventByStatus(),
                'gallery_by_type' => $this->getGalleryByType(),
                'project_by_kategori' => $this->getProjectByKategori(),
                'rating_distribution' => $this->getRatingDistribution(),
                'activity_timeline' => $this->getActivityTimeline(),
            ];

            Response::json([
                'summary' => $summary,
                'average_rating' => $avgRating,
                'recent' => $recent,
                'charts' => $charts,
            ]);
        } catch (PDOException $exception) {
            Response::json(['message' => 'Gagal mengambil data dashboard', 'error' => $exception->getMessage()], 500);
        }
    }

    private function getAverageRating(): ?float
    {
        $stmt = $this->db->query('SELECT AVG(rating) FROM rating');
        $result = $stmt->fetchColumn();
        return $result !== false ? round((float) $result, 2) : null;
    }

    private function getRecentProjects(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, status, created_at 
             FROM project 
             ORDER BY created_at DESC 
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getRecentBerita(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, judul as title, status, created_at 
             FROM berita 
             ORDER BY created_at DESC 
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getRecentEvents(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, judul as title, tanggal_event 
             FROM event 
             ORDER BY id DESC 
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getRecentGallery(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, type, tanggal_kegiatan, created_at 
             FROM galeri 
             ORDER BY created_at DESC 
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getRecentRatings(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, id_project, name, rating, created_at 
             FROM rating 
             ORDER BY created_at DESC 
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getProjectByStatus(): array
    {
        $stmt = $this->db->query(
            "SELECT status as name, COUNT(*) as value 
             FROM project 
             GROUP BY status 
             ORDER BY status"
        );
        return $stmt->fetchAll();
    }

    private function getBeritaByStatus(): array
    {
        $stmt = $this->db->query(
            "SELECT status as name, COUNT(*) as value 
             FROM berita 
             GROUP BY status 
             ORDER BY status"
        );
        return $stmt->fetchAll();
    }

    private function getEventByStatus(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                CASE 
                    WHEN tanggal_event IS NULL THEN 'tidak_ditentukan'
                    WHEN tanggal_event >= CURRENT_DATE THEN 'akan_datang'
                    ELSE 'terlewat'
                END as name,
                COUNT(*) as value
             FROM event 
             GROUP BY name 
             ORDER BY name"
        );
        return $stmt->fetchAll();
    }

    private function getGalleryByType(): array
    {
        $stmt = $this->db->query(
            "SELECT type as name, COUNT(*) as value 
             FROM galeri 
             GROUP BY type 
             ORDER BY type"
        );
        return $stmt->fetchAll();
    }

    private function getProjectByKategori(): array
    {
        $stmt = $this->db->query(
            "SELECT k.name, COUNT(p.id) as value 
             FROM project p 
             INNER JOIN kategori k ON p.id_kategori = k.id 
             GROUP BY k.id, k.name 
             ORDER BY k.name"
        );
        return $stmt->fetchAll();
    }

    private function getRatingDistribution(): array
    {
        $stmt = $this->db->query(
            "SELECT rating as name, COUNT(*) as value 
             FROM rating 
             GROUP BY rating 
             ORDER BY rating"
        );
        return $stmt->fetchAll();
    }

    private function getActivityTimeline(): array
    {
        // Get last 12 months of activity
        $stmt = $this->db->query(
            "SELECT 
                TO_CHAR(created_at, 'YYYY-MM') as month,
                COUNT(*) as project_count
             FROM project 
             WHERE created_at >= CURRENT_DATE - INTERVAL '12 months'
             GROUP BY TO_CHAR(created_at, 'YYYY-MM')
             ORDER BY month"
        );
        $projectTimeline = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $this->db->query(
            "SELECT 
                TO_CHAR(created_at, 'YYYY-MM') as month,
                COUNT(*) as berita_count
             FROM berita 
             WHERE created_at >= CURRENT_DATE - INTERVAL '12 months'
             GROUP BY TO_CHAR(created_at, 'YYYY-MM')
             ORDER BY month"
        );
        $beritaTimeline = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Event doesn't have created_at, use tanggal_event if available, otherwise skip timeline for events
        $stmt = $this->db->query(
            "SELECT 
                TO_CHAR(tanggal_event, 'YYYY-MM') as month,
                COUNT(*) as event_count
             FROM event 
             WHERE tanggal_event >= CURRENT_DATE - INTERVAL '12 months'
             GROUP BY TO_CHAR(tanggal_event, 'YYYY-MM')
             ORDER BY month"
        );
        $eventTimeline = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Merge all timelines
        $timeline = [];
        $allMonths = array_unique(array_merge(
            array_column($projectTimeline, 'month'),
            array_column($beritaTimeline, 'month'),
            array_column($eventTimeline, 'month')
        ));
        sort($allMonths);

        foreach ($allMonths as $month) {
            $projectCount = 0;
            $beritaCount = 0;
            $eventCount = 0;

            foreach ($projectTimeline as $item) {
                if ($item['month'] === $month) {
                    $projectCount = (int) $item['project_count'];
                    break;
                }
            }

            foreach ($beritaTimeline as $item) {
                if ($item['month'] === $month) {
                    $beritaCount = (int) $item['berita_count'];
                    break;
                }
            }

            foreach ($eventTimeline as $item) {
                if ($item['month'] === $month) {
                    $eventCount = (int) $item['event_count'];
                    break;
                }
            }

            $timeline[] = [
                'month' => $month,
                'project' => $projectCount,
                'berita' => $beritaCount,
                'event' => $eventCount,
            ];
        }

        return $timeline;
    }
}

