<?php

declare(strict_types=1);

namespace Nexus\Tests\Redis;

use Nexus\Redis\RedisCache;
use Nexus\Redis\RedisClientInterface;
use Nexus\Redis\RedisLock;
use PHPUnit\Framework\TestCase;

final class RedisTest extends TestCase
{
    public function testRedisCacheUsesPrefixAndRemember(): void
    {
        $client = new InMemoryRedisClient();
        $cache = new RedisCache($client);

        $cache->set('name', 'Nexus', 60);
        self::assertSame('Nexus', $cache->get('name'));
        self::assertTrue($cache->has('name'));
        self::assertSame(1, $client->writes);
        self::assertSame('value', $cache->remember('x', 60, static fn (): string => 'value'));
        self::assertSame('value', $cache->remember('x', 60, static fn (): string => 'other'));
    }

    public function testRedisLockTracksOwnership(): void
    {
        $client = new InMemoryRedisClient();
        $first = new RedisLock($client, 'job');
        $second = new RedisLock($client, 'job');

        self::assertTrue($first->acquire());
        self::assertTrue($first->owned());
        self::assertFalse($second->acquire());
        self::assertTrue($first->release());
        self::assertTrue($second->acquire());
    }
}

final class InMemoryRedisClient implements RedisClientInterface
{
    /** @var array<string, string> */
    private array $values = [];

    /** @var array<string, string> */
    private array $locks = [];

    public int $writes = 0;

    public function get(string $key): ?string
    {
        return $this->values[$key] ?? null;
    }

    public function set(string $key, string $value, ?int $ttlSeconds = null): void
    {
        unset($ttlSeconds);
        $this->values[$key] = $value;
        $this->writes++;
    }

    public function delete(string $key): void
    {
        unset($this->values[$key]);
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function clear(): void
    {
        $this->values = [];
    }

    public function acquireLock(string $key, string $token, int $ttlMilliseconds): bool
    {
        unset($ttlMilliseconds);
        if (isset($this->locks[$key])) {
            return false;
        }
        $this->locks[$key] = $token;
        return true;
    }

    public function releaseLock(string $key, string $token): bool
    {
        if (($this->locks[$key] ?? null) !== $token) {
            return false;
        }
        unset($this->locks[$key]);
        return true;
    }
}
