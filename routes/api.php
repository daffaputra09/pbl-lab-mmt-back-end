<?php

declare(strict_types=1);

use App\Controllers\DocsController;
use App\Controllers\KategoriController;
use App\Controllers\TagController;
use App\Controllers\EventController;


$router->get('/', [DocsController::class, 'ui']);
$router->get('/docs', [DocsController::class, 'ui']);
$router->get('/swagger/openapi.json', [DocsController::class, 'json']);

$router->get('/kategori', [KategoriController::class, 'index']);
$router->get('/kategori/{id}', [KategoriController::class, 'show']);
$router->post('/kategori', [KategoriController::class, 'store']);
$router->put('/kategori/{id}', [KategoriController::class, 'update']);
$router->delete('/kategori/{id}', [KategoriController::class, 'destroy']);

$router->get('/tag', [TagController::class, 'index']);
$router->get('/tag/{id}', [TagController::class, 'show']);
$router->post('/tag', [TagController::class, 'store']);
$router->put('/tag/{id}', [TagController::class, 'update']);
$router->delete('/tag/{id}', [TagController::class, 'destroy']);

$router->get('/event', [EventController::class, 'index']);
$router->get('/event/{id}', [EventController::class, 'show']);
$router->post('/event', [EventController::class, 'store']);
$router->put('/event/{id}', [EventController::class, 'update']);
$router->delete('/event/{id}', [EventController::class, 'destroy']);