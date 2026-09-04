<?php

declare(strict_types=1);

namespace Nexus\Http;

final class Request
{
    /**
     * @param array<string, string|list<string>> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers = [],
        private readonly array $query = [],
        private readonly mixed $body = null,
        private readonly array $attributes = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $methodValue = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uriValue = $_SERVER['REQUEST_URI'] ?? '/';
        $method = is_string($methodValue) ? strtoupper($methodValue) : 'GET';
        $uri = is_string($uriValue) ? $uriValue : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $rawBody = file_get_contents('php://input');
        $body = $_POST !== []
            ? self::queryFromGlobals($_POST)
            : ($rawBody !== false && $rawBody !== '' ? $rawBody : null);

        return new self(
            $method,
            is_string($path) && $path !== '' ? $path : '/',
            self::headersFromServer($_SERVER),
            self::queryFromGlobals($_GET),
            $body,
        );
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        return $this->path === '' ? '/' : $this->path;
    }

    /** @return array<string, string|list<string>> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) !== 0) {
                continue;
            }

            return is_array($value) ? ($value[0] ?? $default) : $value;
        }

        return $default;
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->query;
    }

    public function body(): mixed
    {
        return $this->body;
    }

    /**
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $input = array_replace($this->query, $this->parsedBody());

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    public function acceptsHtml(): bool
    {
        $accept = $this->header('Accept', '');

        return is_string($accept) && str_contains(strtolower($accept), 'text/html');
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $attributes = $this->attributes;
        $attributes[$name] = $value;

        return new self(
            $this->method,
            $this->path,
            $this->headers,
            $this->query,
            $this->body,
            $attributes,
        );
    }

    /** @param array<string, mixed> $attributes */
    public function withAttributes(array $attributes): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->headers,
            $this->query,
            $this->body,
            array_replace($this->attributes, $attributes),
        );
    }

    /** @return array<string, mixed> */
    private function parsedBody(): array
    {
        if (is_array($this->body)) {
            return self::queryFromGlobals($this->body);
        }

        if (!is_string($this->body) || $this->body === '') {
            return [];
        }

        $contentType = strtolower((string) $this->header('Content-Type', ''));

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($this->body, true);

            if (is_array($decoded)) {
                return self::queryFromGlobals($decoded);
            }

            return [];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($this->body, $parsed);

            return self::queryFromGlobals($parsed);
        }

        return [];
    }

    /**
     * @param array<array-key, mixed> $server
     * @return array<string, string>
     */
    private static function headersFromServer(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }

        foreach (['CONTENT_TYPE' => 'CONTENT-TYPE', 'CONTENT_LENGTH' => 'CONTENT-LENGTH'] as $serverKey => $header) {
            $value = $server[$serverKey] ?? null;

            if (is_scalar($value)) {
                $headers[$header] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * @param array<array-key, mixed> $query
     * @return array<string, mixed>
     */
    private static function queryFromGlobals(array $query): array
    {
        $normalized = [];

        foreach ($query as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
