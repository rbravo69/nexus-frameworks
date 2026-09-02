<?php

declare(strict_types=1);

namespace Nexus\Database;

final readonly class DatabaseConfig
{
    /**
     * @param 'pgsql'|'mysql'|'sqlite' $driver
     * @param array<int, mixed> $options
     */
    public function __construct(
        public string $driver,
        public string $database,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $charset = null,
        public array $options = [],
    ) {
        if ($database === '') {
            throw new \InvalidArgumentException('Database name or SQLite path cannot be empty.');
        }
    }
}
