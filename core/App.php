<?php
class App {
    private array $routes = [];

    public function get(string $path, array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void {
        $this->routes['POST'][$path] = $handler;
    }

    public function any(string $path, array $handler): void {
        $this->routes['GET'][$path]  = $handler;
        $this->routes['POST'][$path] = $handler;
    }

    public function run(): void {
        $method   = $_SERVER['REQUEST_METHOD'];
        $uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        $base     = rtrim(BASE_URL, '/');

        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        $path = '/' . ltrim($uri, '/');

        // Normalise trailing slashes
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $routes = $this->routes[$method] ?? [];

        if (isset($routes[$path])) {
            [$class, $method] = $routes[$path];
            (new $class())->$method();
            return;
        }

        // Try GET fallback for POST-only path not found
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($this->routes['GET'][$path])) {
            [$class, $method] = $this->routes['GET'][$path];
            (new $class())->$method();
            return;
        }

        http_response_code(404);
        echo '<h1 style="font-family:sans-serif;padding:40px;">404 — Page Not Found</h1>';
    }
}
