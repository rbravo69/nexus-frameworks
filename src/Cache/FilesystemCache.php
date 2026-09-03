<?php

declare(strict_types=1);

namespace Nexus\Cache;

use DateInterval;
use DateTimeImmutable;

final class FilesystemCache implements CacheInterface
{
    private readonly SerializerInterface $serializer;

    public function __construct(
        private readonly string $directory,
        ?SerializerInterface $serializer = null,
    ) {
        if ($directory === '') {
            throw new \InvalidArgumentException('Cache directory cannot be empty.');
        }

        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->read($key);
        return $item === null ? $default : $item['value'];
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): void
    {
        $seconds = $this->seconds($ttl);
        if ($seconds !== null && $seconds <= 0) {
            $this->delete($key);
            return;
        }

        $this->ensureDirectory();
        $payload = $this->serializer->serialize([
            'expiresAt' => $seconds === null ? null : time() + $seconds,
            'value' => $value,
        ]);

        $path = $this->path($key);
        $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temporary, $payload, LOCK_EX) === false || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException(sprintf('Unable to write cache item "%s".', $key));
        }
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (is_file($path) && !unlink($path)) {
            throw new \RuntimeException(sprintf('Unable to delete cache item "%s".', $key));
        }
    }

    public function has(string $key): bool
    {
        return $this->read($key) !== null;
    }

    public function clear(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $files = glob(rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.cache');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                throw new \RuntimeException(sprintf('Unable to delete cache file "%s".', $file));
            }
        }
    }

    public function remember(string $key, int|DateInterval|null $ttl, callable $resolver): mixed
    {
        $item = $this->read($key);
        if ($item !== null) {
            return $item['value'];
        }

        $value = $resolver();
        $this->set($key, $value, $ttl);
        return $value;
    }

    /** @return array{expiresAt: ?int, value: mixed}|null */
    private function read(string $key): ?array
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }

        $payload = file_get_contents($path);
        if ($payload === false) {
            throw new \RuntimeException(sprintf('Unable to read cache item "%s".', $key));
        }

        $item = $this->serializer->unserialize($payload);
        if (!is_array($item) || !array_key_exists('expiresAt', $item) || !array_key_exists('value', $item)) {
            throw new \UnexpectedValueException(sprintf('Cache item "%s" is invalid.', $key));
        }

        $expiresAt = $item['expiresAt'];
        if ($expiresAt !== null && !is_int($expiresAt)) {
            throw new \UnexpectedValueException(sprintf('Cache item "%s" has an invalid expiration.', $key));
        }

        if ($expiresAt !== null && $expiresAt <= time()) {
            $this->delete($key);
            return null;
        }

        return ['expiresAt' => $expiresAt, 'value' => $item['value']];
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.cache';
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException(sprintf('Unable to create cache directory "%s".', $this->directory));
        }
    }

    private function seconds(int|DateInterval|null $ttl): ?int
    {
        if ($ttl === null || is_int($ttl)) {
            return $ttl;
        }

        $now = new DateTimeImmutable('@0');
        return $now->add($ttl)->getTimestamp();
    }
}
