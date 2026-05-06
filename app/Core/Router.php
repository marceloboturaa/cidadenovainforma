<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $patterns = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $path = $this->normalize($path);

        if (str_contains($path, '{')) {
            $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
            $this->patterns[$method][] = [
                'regex' => '#^' . $regex . '$#',
                'handler' => $handler,
            ];
            return;
        }

        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            foreach ($this->patterns[$method] ?? [] as $route) {
                if (preg_match($route['regex'], $path, $matches)) {
                    foreach ($matches as $key => $value) {
                        if (!is_int($key)) {
                            $_GET[$key] = urldecode($value);
                        }
                    }

                    $handler = $route['handler'];
                    break;
                }
            }
        }

        if (!$handler) {
            http_response_code(404);
            View::render('errors/404', [], 'auth');
            return;
        }

        [$controller, $action] = $handler;
        (new $controller())->{$action}();
    }

    private function normalize(string $path): string
    {
        $basePath = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $path = '/' . trim($path, '/');

        if ($basePath && str_starts_with($path, '/' . $basePath)) {
            $path = substr($path, strlen('/' . $basePath)) ?: '/';
        }

        return $path === '//' ? '/' : $path;
    }
}
