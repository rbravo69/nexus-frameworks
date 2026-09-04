<?php

declare(strict_types=1);

namespace Nexus\Http;

use Closure;
use JsonException;

class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $status = 200,
        private readonly array $headers = [],
        private readonly string $body = '',
        private readonly ?Closure $stream = null,
    ) {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException('HTTP status must be between 100 and 599.');
        }
    }

    /** @param array<string, string> $headers */
    public static function text(string $body, int $status = 200, array $headers = []): self
    {
        return new self($status, ['content-type' => 'text/plain; charset=utf-8', ...$headers], $body);
    }

    /** @param array<string, string> $headers */
    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        return new self($status, ['content-type' => 'text/html; charset=utf-8', ...$headers], $body);
    }

    /** @param array<string, string> $headers */
    public static function redirect(string $location, int $status = 302, array $headers = []): RedirectResponse
    {
        return RedirectResponse::to($location, $status, $headers);
    }

    /**
     * @param array<string, string> $headers
     * @throws JsonException
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self(
            $status,
            ['content-type' => 'application/json; charset=utf-8', ...$headers],
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @param Closure(): void $stream
     * @param array<string, string> $headers
     */
    public static function stream(Closure $stream, int $status = 200, array $headers = []): self
    {
        return new self($status, $headers, '', $stream);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function isStreamed(): bool
    {
        return $this->stream !== null;
    }

    public function sendStream(): void
    {
        if ($this->stream === null) {
            return;
        }

        ($this->stream)();
    }

    public function withHeader(string $name, string $value): self
    {
        return new self(
            $this->status,
            [...$this->headers, strtolower($name) => $value],
            $this->body,
            $this->stream,
        );
    }

    public function withCookie(Cookie $cookie): self
    {
        return $this->withHeader('Set-Cookie', $cookie->toHeader());
    }
}
