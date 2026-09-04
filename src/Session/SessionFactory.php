<?php

declare(strict_types=1);

namespace Nexus\Session;

use SessionHandlerInterface;

final readonly class SessionFactory
{
    /** @param array<string, mixed> $config */
    public function create(string $driver, array $config = []): SessionInterface
    {
        return match (strtolower(trim($driver))) {
            'array', 'memory' => new ArraySession(),
            'file', 'files' => new NativeSession(
                name: $this->string($config, 'name', 'NEXUSSESSID'),
                cookieSecure: $this->bool($config, 'secure', false),
                sameSite: $this->string($config, 'same_site', 'Lax'),
                savePath: $this->nullableString($config, 'path'),
            ),
            'native', 'php' => new NativeSession(
                name: $this->string($config, 'name', 'NEXUSSESSID'),
                cookieSecure: $this->bool($config, 'secure', false),
                sameSite: $this->string($config, 'same_site', 'Lax'),
            ),
            default => throw new \InvalidArgumentException(sprintf('Unknown session driver "%s".', $driver)),
        };
    }

    /** @param array<string, mixed> $config */
    public function fromHandler(SessionHandlerInterface $handler, array $config = []): SessionInterface
    {
        return new NativeSession(
            name: $this->string($config, 'name', 'NEXUSSESSID'),
            cookieSecure: $this->bool($config, 'secure', false),
            sameSite: $this->string($config, 'same_site', 'Lax'),
            handler: $handler,
        );
    }

    /** @param array<string, mixed> $config */
    private function string(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? $default;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /** @param array<string, mixed> $config */
    private function nullableString(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $config */
    private function bool(array $config, string $key, bool $default): bool
    {
        $value = $config[$key] ?? $default;

        return is_bool($value) ? $value : $default;
    }
}
