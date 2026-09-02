<?php

declare(strict_types=1);

namespace Nexus\Redis;

final class RedisLock
{
    private ?string $token = null;

    public function __construct(
        private readonly RedisClientInterface $client,
        private readonly string $name,
        private readonly int $ttlMilliseconds = 10000,
        private readonly string $prefix = 'nexus:lock:',
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Lock name cannot be empty.');
        }
        if ($ttlMilliseconds <= 0) {
            throw new \InvalidArgumentException('Lock TTL must be greater than zero.');
        }
    }

    public function acquire(): bool
    {
        if ($this->token !== null) {
            return true;
        }

        $token = bin2hex(random_bytes(16));
        if (!$this->client->acquireLock($this->prefix . $this->name, $token, $this->ttlMilliseconds)) {
            return false;
        }

        $this->token = $token;
        return true;
    }

    public function release(): bool
    {
        if ($this->token === null) {
            return false;
        }

        $released = $this->client->releaseLock($this->prefix . $this->name, $this->token);
        if ($released) {
            $this->token = null;
        }

        return $released;
    }

    public function owned(): bool
    {
        return $this->token !== null;
    }
}
