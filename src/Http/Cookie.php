<?php

declare(strict_types=1);

namespace Nexus\Http;

final readonly class Cookie
{
    public function __construct(
        public string $name,
        public string $value,
        public int $maxAge = 0,
        public string $path = '/',
        public ?string $domain = null,
        public bool $secure = false,
        public bool $httpOnly = true,
        public string $sameSite = 'Lax',
    ) {
        if ($name === '' || preg_match('/[=;\s]/', $name) === 1) {
            throw new \InvalidArgumentException('Cookie name is invalid.');
        }
    }

    public function toHeader(): string
    {
        $parts = [
            $this->name . '=' . rawurlencode($this->value),
            'Path=' . ($this->path === '' ? '/' : $this->path),
            'SameSite=' . $this->normalizedSameSite(),
        ];

        if ($this->maxAge !== 0) {
            $parts[] = 'Max-Age=' . $this->maxAge;
        }

        if ($this->domain !== null && $this->domain !== '') {
            $parts[] = 'Domain=' . $this->domain;
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        return implode('; ', $parts);
    }

    public static function forget(string $name, string $path = '/', ?string $domain = null): self
    {
        return new self($name, '', -3600, $path, $domain);
    }

    /** @return 'Lax'|'None'|'Strict' */
    private function normalizedSameSite(): string
    {
        return match (strtolower($this->sameSite)) {
            'none' => 'None',
            'strict' => 'Strict',
            default => 'Lax',
        };
    }
}
