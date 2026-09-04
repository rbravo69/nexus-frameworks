<?php

declare(strict_types=1);

namespace Nexus\Session;

final class ArraySession implements SessionInterface
{
    /** @param array<string, mixed> $data */
    public function __construct(private array $data = [])
    {
    }

    public function start(): void
    {
    }

    public function close(): void
    {
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(bool $deleteOldSession = false): void
    {
    }

    public function invalidate(): void
    {
        $this->data = [];
    }

    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $new = $this->flashKeys('_nexus_flash_new');

        if (!in_array($key, $new, true)) {
            $new[] = $key;
        }

        $this->put('_nexus_flash_new', $new);
    }

    public function ageFlashData(): void
    {
        foreach ($this->flashKeys('_nexus_flash_old') as $key) {
            $this->forget($key);
        }

        $this->put('_nexus_flash_old', $this->flashKeys('_nexus_flash_new'));
        $this->put('_nexus_flash_new', []);
    }

    public function all(): array
    {
        return $this->data;
    }

    /** @return list<string> */
    private function flashKeys(string $key): array
    {
        $value = $this->get($key, []);

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
