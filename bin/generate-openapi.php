<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use OpenApi\Generator;

$scanPaths = [
    dirname(__DIR__) . '/app/Controllers',
    dirname(__DIR__) . '/app/Docs',
    dirname(__DIR__) . '/routes',
];

$outputDir = dirname(__DIR__) . '/swagger';
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "Failed to create swagger output directory.\n");
    exit(1);
}

$result = Generator::scan($scanPaths);
$outputFile = $outputDir . '/openapi.json';
file_put_contents($outputFile, $result->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$publicDir = dirname(__DIR__) . '/public/swagger';
if (!is_dir($publicDir) && !mkdir($publicDir, 0775, true) && !is_dir($publicDir)) {
    fwrite(STDERR, "Failed to create public swagger output directory.\n");
    exit(1);
}

$publicOutputFile = $publicDir . '/openapi.json';
if (!copy($outputFile, $publicOutputFile)) {
    fwrite(STDERR, "Failed to copy OpenAPI specification to public directory.\n");
    exit(1);
}

echo "OpenAPI specification generated at {$outputFile}\n";
echo "OpenAPI specification published at {$publicOutputFile}\n";

