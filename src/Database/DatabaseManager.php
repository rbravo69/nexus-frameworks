<?php

declare(strict_types=1);

namespace Nexus\Database;

final class DatabaseManager
{
    /** @var array<string, DatabaseConfig> */
    private array $configs = [];

    /** @var array<string, ConnectionInterface> */
    private array $connections = [];

    public function __construct(
        private readonly ConnectionFactory $factory = new ConnectionFactory(),
        private string $default = 'default',
    ) {
    }

    public function add(string $name, DatabaseConfig $config): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Database connection name cannot be empty.');
        }

        $this->configs[$name] = $config;
        unset($this->connections[$name]);

        return $this;
    }

    public function setDefault(string $name): self
    {
        $this->default = $name;

        return $this;
    }

    public function connection(?string $name = null): ConnectionInterface
    {
        $name ??= $this->default;

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = $this->configs[$name] ?? null;

        if (!$config instanceof DatabaseConfig) {
            throw new \InvalidArgumentException(sprintf('Database connection "%s" is not configured.', $name));
        }

        return $this->connections[$name] = $this->factory->make($config);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->configs);
    }

    public function disconnect(?string $name = null): void
    {
        unset($this->connections[$name ?? $this->default]);
    }
}
