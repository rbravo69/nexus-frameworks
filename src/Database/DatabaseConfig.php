<?php

declare(strict_types=1);

namespace Nexus\Database;

final readonly class DatabaseConfig
{
    /** @var 'pgsql'|'mysql'|'sqlite'|'sqlsrv'|'oci' */
    public string $driver;

    /** @var array<int, mixed> */
    public array $options;

    /** @var array<string, string|int|bool> */
    public array $driverOptions;

    /**
     * @param array<int, mixed> $options PDO constructor options.
     * @param array<string, string|int|bool> $driverOptions Explicit driver DSN options.
     */
    public function __construct(
        string $driver,
        public string $database,
        public ?string $host = null,
        public ?int $port = null,
        public ?string $username = null,
        public ?string $password = null,
        public ?string $charset = null,
        array $options = [],
        array $driverOptions = [],
    ) {
        if (!in_array($driver, ['pgsql', 'mysql', 'sqlite', 'sqlsrv', 'oci'], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported database driver "%s".', $driver));
        }

        if ($database === '') {
            throw new \InvalidArgumentException('Database name, service name, or SQLite path cannot be empty.');
        }

        if ($driver !== 'sqlsrv' && $driverOptions !== []) {
            throw new \InvalidArgumentException('Driver DSN options are currently supported only for SQL Server.');
        }

        foreach ($driverOptions as $name => $value) {
            if (!in_array($name, ['Encrypt', 'TrustServerCertificate'], true)) {
                throw new \InvalidArgumentException(sprintf('Unsupported SQL Server DSN option "%s".', $name));
            }

            if (is_string($value) && str_contains($value, ';')) {
                throw new \InvalidArgumentException(sprintf('SQL Server DSN option "%s" contains an invalid separator.', $name));
            }
        }

        /** @var 'pgsql'|'mysql'|'sqlite'|'sqlsrv'|'oci' $driver */
        $this->driver = $driver;
        $this->options = $options;
        $this->driverOptions = $driverOptions;
    }
}
