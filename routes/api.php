<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DocsController;
use App\Controllers\KategoriController;
use App\Controllers\TagController;
use App\Controllers\EventController;
use App\Controllers\GaleriController;
use App\Controllers\BeritaController;
use App\Controllers\ProjectController;
use App\Controllers\RatingController;
use App\Controllers\UserController;

$router->get('/', [DocsController::class, 'ui']);
$router->get('/docs', [DocsController::class, 'ui']);
$router->get('/swagger/openapi.json', [DocsController::class, 'json']);

$router->post('/auth/login', [AuthController::class, 'login']);

$router->post('/user', [UserController::class, 'store']);
$router->get('/user/profile', [UserController::class, 'profile']);

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
$router->get('/event/recent', [EventController::class, 'recent']);
$router->get('/event/recent/{limit}', [EventController::class, 'recentWithLimit']);
$router->get('/event/{id}', [EventController::class, 'show']);
$router->post('/event', [EventController::class, 'store']);
$router->put('/event/{id}', [EventController::class, 'update']);
$router->post('/event/{id}', [EventController::class, 'update']);
$router->delete('/event/{id}', [EventController::class, 'destroy']);

$router->get('/galeri', [GaleriController::class, 'index']);
$router->get('/galeri/{id}', [GaleriController::class, 'show']);
$router->post('/galeri', [GaleriController::class, 'store']);
$router->put('/galeri/{id}', [GaleriController::class, 'update']);
$router->post('/galeri/{id}', [GaleriController::class, 'update']); // Support POST for file upload
$router->delete('/galeri/{id}', [GaleriController::class, 'destroy']);

$router->get('/berita', [BeritaController::class, 'index']);
$router->get('/berita/{id}', [BeritaController::class, 'show']);
$router->post('/berita', [BeritaController::class, 'store']);
$router->put('/berita/{id}', [BeritaController::class, 'update']);
$router->post('/berita/{id}', [BeritaController::class, 'update']);
$router->delete('/berita/{id}', [BeritaController::class, 'destroy']);

$router->get('/project', [ProjectController::class, 'index']);
$router->get('/project/{id}', [ProjectController::class, 'show']);
$router->post('/project', [ProjectController::class, 'store']);
$router->put('/project/{id}', [ProjectController::class, 'update']);
$router->post('/project/{id}', [ProjectController::class, 'update']);
$router->delete('/project/{id}', [ProjectController::class, 'destroy']);

$router->get('/rating', [RatingController::class, 'index']);
$router->get('/rating/{id}', [RatingController::class, 'show']);
$router->post('/rating', [RatingController::class, 'store']);
$router->put('/rating/{id}', [RatingController::class, 'update']);
$router->delete('/rating/{id}', [RatingController::class, 'destroy']);