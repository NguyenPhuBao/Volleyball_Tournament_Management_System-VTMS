<?php

declare(strict_types=1);

namespace App\Backend\Core\Http;

final class Request
{
    private array $routeParams = [];

    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $body,
        private array $server,
        private array $files = []
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = self::normalizePath((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        $body = $_POST;

        if ($method !== 'GET' && empty($body)) {
            $raw = file_get_contents('php://input') ?: '';
            $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
            $looksLikeJson = str_starts_with(ltrim($raw), '{') || str_starts_with(ltrim($raw), '[');

            if (str_contains($contentType, 'application/json') || $looksLikeJson) {
                $decoded = json_decode($raw, true);
                $body = is_array($decoded) ? $decoded : [];
            }
        }

        return new self($method, $path, $_GET, $body, $_SERVER, $_FILES);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        if (array_key_exists($name, $this->server)) {
            return $this->server[$name];
        }

        $contentHeader = strtoupper(str_replace('-', '_', $key));

        return $this->server[$contentHeader] ?? $default;
    }

    public function expectsJson(): bool
    {
        $accept = (string) $this->header('Accept', '');
        $contentType = (string) $this->header('Content-Type', '');

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || str_starts_with($this->path, '/api/');
    }

    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    public function userAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function withRouteParams(array $params): self
    {
        $clone = clone $this;
        $clone->routeParams = $params;

        return $clone;
    }

    private static function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));

        if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
