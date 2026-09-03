<?php

declare(strict_types=1);

namespace Nexus\Database\Mongo;

final class MongoManager
{
    /** @var array<string, MongoConnectionInterface> */
    private array $connections = [];

    /** @var array<string, MongoConfig> */
    private array $configs = [];

    public function add(string $name, MongoConnectionInterface $connection): self
    {
        $this->assertName($name);
        $this->connections[$name] = $connection;
        unset($this->configs[$name]);
        return $this;
    }

    public function configure(string $name, MongoConfig $config): self
    {
        $this->assertName($name);
        $this->configs[$name] = $config;
        unset($this->connections[$name]);
        return $this;
    }

    public function connection(string $name = 'default'): MongoConnectionInterface
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (!isset($this->configs[$name])) {
            throw new \RuntimeException(sprintf('MongoDB connection "%s" is not configured.', $name));
        }

        return $this->connections[$name] = MongoLibraryConnection::connect($this->configs[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = array_unique([...array_keys($this->configs), ...array_keys($this->connections)]);
        sort($names);
        return $names;
    }

    private function assertName(string $name): void
    {
        if ($name === '') {
            throw new \InvalidArgumentException('MongoDB connection name cannot be empty.');
        }
    }
}
