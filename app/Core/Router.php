<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Response;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = [];
                foreach ($route['paramNames'] as $name) {
                    $params[] = $matches[$name] ?? null;
                }

                $this->invokeHandler($route['handler'], $params);
                return;
            }
        }

        Response::json(['message' => 'Not Found'], 404);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        preg_match_all('/\{(\w+)\}/', $path, $parameterMatches);
        $paramNames = $parameterMatches[1] ?? [];

        $pattern = preg_replace('/\//', '\\/', $path);
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $pattern);
        $pattern = '#^' . ($pattern === '' ? '/' : $pattern) . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => $pattern,
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
    }

    private function invokeHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            if (is_string($class)) {
                $instance = new $class();
                $handler = [$instance, $method];
            }
        }

        $response = call_user_func_array($handler, $params);

        if ($response !== null) {
            if (is_array($response)) {
                Response::json($response);
            } else {
                echo $response;
            }
        }
    }
}

