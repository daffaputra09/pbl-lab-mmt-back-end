<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use OpenApi\Attributes as OA;

class DocsController
{
    #[OA\Get(
        path: '/docs',
        summary: 'Swagger UI',
        tags: ['Documentation'],
        responses: [
            new OA\Response(response: 200, description: 'Halaman Swagger UI')
        ]
    )]
    public function ui(): void
    {
        $specUrl = '/swagger/openapi.json';
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: '{$specUrl}',
                dom_id: '#swagger-ui'
            });
        };
    </script>
</body>
</html>
HTML;

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    #[OA\Get(
        path: '/swagger/openapi.json',
        summary: 'OpenAPI Specification',
        tags: ['Documentation'],
        responses: [
            new OA\Response(response: 200, description: 'Berkas spesifikasi OpenAPI')
        ]
    )]
    public function json(): void
    {
        $file = dirname(__DIR__, 2) . '/swagger/openapi.json';

        if (!is_file($file)) {
            Response::json(['message' => 'OpenAPI specification not generated yet'], 404);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        readfile($file);
    }
}

