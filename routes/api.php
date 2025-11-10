<?php

declare(strict_types=1);

use App\Controllers\DocsController;
use App\Controllers\KategoriController;

$router->get('/', [DocsController::class, 'ui']);
$router->get('/docs', [DocsController::class, 'ui']);
$router->get('/swagger/openapi.json', [DocsController::class, 'json']);

$router->get('/kategori', [KategoriController::class, 'index']);
$router->get('/kategori/{id}', [KategoriController::class, 'show']);
$router->post('/kategori', [KategoriController::class, 'store']);
$router->put('/kategori/{id}', [KategoriController::class, 'update']);
$router->delete('/kategori/{id}', [KategoriController::class, 'destroy']);

