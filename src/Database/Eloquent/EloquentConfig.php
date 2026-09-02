<?php

declare(strict_types=1);

namespace Nexus\Database\Eloquent;

use Nexus\Database\DatabaseConfig;

final readonly class EloquentConfig
{
    public function __construct(
        public DatabaseConfig $database,
        public string $name = 'default',
        public bool $global = false,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Eloquent connection name cannot be empty.');
        }
    }

    /** @return array<string, mixed> */
    public function toIlluminate(): array
    {
        return match ($this->database->driver) {
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => $this->database->database,
                'prefix' => '',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => $this->database->host ?? '127.0.0.1',
                'port' => $this->database->port ?? 5432,
                'database' => $this->database->database,
                'username' => $this->database->username ?? '',
                'password' => $this->database->password ?? '',
                'charset' => $this->database->charset ?? 'utf8',
                'prefix' => '',
                'schema' => 'public',
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => $this->database->host ?? '127.0.0.1',
                'port' => $this->database->port ?? 3306,
                'database' => $this->database->database,
                'username' => $this->database->username ?? '',
                'password' => $this->database->password ?? '',
                'charset' => $this->database->charset ?? 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
            'sqlsrv' => [
                'driver' => 'sqlsrv',
                'host' => $this->database->host ?? '127.0.0.1',
                'port' => $this->database->port ?? 1433,
                'database' => $this->database->database,
                'username' => $this->database->username ?? '',
                'password' => $this->database->password ?? '',
                'charset' => $this->database->charset ?? 'utf8',
                'prefix' => '',
            ],
            'oci' => throw new \LogicException('Oracle is supported by Nexus Database Core, but Illuminate Database has no native Oracle driver. Use SQL/PDO or an explicit Oracle Eloquent adapter.'),
        };
    }
}
