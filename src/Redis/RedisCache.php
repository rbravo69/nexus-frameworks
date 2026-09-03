<?php

declare(strict_types=1);

namespace Nexus\Redis;

use DateInterval;
use DateTimeImmutable;
use Nexus\Cache\CacheInterface;
use Nexus\Cache\PhpSerializer;
use Nexus\Cache\SerializerInterface;

final class RedisCache implements CacheInterface
{
    private readonly SerializerInterface $serializer;

    public function __construct(
        private readonly RedisClientInterface $client,
        private readonly string $prefix = 'nexus:cache:',
        ?SerializerInterface $serializer = null,
    ) {
        if ($prefix === '') {
            throw new \InvalidArgumentException('Redis cache prefix cannot be empty.');
        }

        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $payload = $this->client->get($this->key($key));
        return $payload === null ? $default : $this->serializer->unserialize($payload);
    }

    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): void
    {
        $seconds = $this->seconds($ttl);
        if ($seconds !== null && $seconds <= 0) {
            $this->delete($key);
            return;
        }

        $this->client->set($this->key($key), $this->serializer->serialize($value), $seconds);
    }

    public function delete(string $key): void
    {
        $this->client->delete($this->key($key));
    }

    public function has(string $key): bool
    {
        return $this->client->exists($this->key($key));
    }

    public function clear(): void
    {
        $this->client->deleteByPrefix($this->prefix);
    }

    public function remember(string $key, int|DateInterval|null $ttl, callable $resolver): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $resolver();
        $this->set($key, $value, $ttl);
        return $value;
    }

    private function key(string $key): string
    {
        return $this->prefix . $key;
    }

    private function seconds(int|DateInterval|null $ttl): ?int
    {
        if ($ttl === null || is_int($ttl)) {
            return $ttl;
        }

        return (new DateTimeImmutable('@0'))->add($ttl)->getTimestamp();
    }
}
