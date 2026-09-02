<?php

declare(strict_types=1);

namespace Nexus\Database;

final readonly class DatabaseConfig
{
    /** @var 'pgsql'|'mysql'|'sqlite'|'sqlsrv'|'oci' */
    public string $driver;

    /** @var array<int, mixed> */
    public array $options;

    /** @param array<int, mixed> $options */
    public function __construct(
        string $driver,
        public string $database,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $charset = null,
        array $options = [],
    ) {
        if (!in_array($driver, ['pgsql', 'mysql', 'sqlite', 'sqlsrv', 'oci'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported database driver "%s".', $driver));
        }

        if ($database === '') {
            throw new \InvalidArgumentException('Database name, service name, or SQLite path cannot be empty.');
        }

        /** @var 'pgsql'|'mysql'|'sqlite'|'sqlsrv'|'oci' $driver */
        $this->driver = $driver;
        $this->options = $options;
    }
}
