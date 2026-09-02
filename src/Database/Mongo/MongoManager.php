<?php

declare(strict_types=1);

namespace Nexus\Database\Mongo;

final class MongoManager
{
    /** @var array<string, MongoConnectionInterface> */
    private array $connections = [];

    public function add(string $name, MongoConnectionInterface $connection): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('MongoDB connection name cannot be empty.');
        }

        $this->connections[$name] = $connection;
        return $this;
    }

    public function connection(string $name = 'default'): MongoConnectionInterface
    {
        return $this->connections[$name]
            ?? throw new \RuntimeException(sprintf('MongoDB connection "%s" is not configured.', $name));
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->connections);
    }
}
