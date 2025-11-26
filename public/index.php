<?php

declare(strict_types=1);

use App\Core\Router;
use Dotenv\Dotenv;

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

if ($requestPath && $requestPath !== '/') {
    $requestPath = str_replace('\\', '/', $requestPath);
    $requestPath = ltrim($requestPath, '/');
    
    $filePath = __DIR__ . '/' . $requestPath;
    
    if (file_exists($filePath) && is_file($filePath)) {
        $mimeType = mime_content_type($filePath);
        if ($mimeType === false) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'mp4' => 'video/mp4',
                'mpeg' => 'video/mpeg',
                'mov' => 'video/quicktime',
                'avi' => 'video/x-msvideo',
                'json' => 'application/json',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        }
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        if ($origin !== '*') {
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000');
        
        readfile($filePath);
        exit;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');

if ($origin !== '*') {
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$router = new Router();

require dirname(__DIR__) . '/routes/api.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

