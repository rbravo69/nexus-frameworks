<?php

declare(strict_types=1);

namespace Nexus\Database;

use PDO;

final class ConnectionFactory
{
    public function make(DatabaseConfig $config): ConnectionInterface
    {
        $pdo = new PDO(
            $this->dsn($config),
            $config->username,
            $config->password,
            $this->pdoOptions($config),
        );

        return new PdoConnection($pdo, $config->driver);
    }

    public function dsn(DatabaseConfig $config): string
    {
        return match ($config->driver) {
            'sqlite' => 'sqlite:' . $config->database,
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $config->host ?? '127.0.0.1',
                $config->port ?? 5432,
                $config->database,
            ),
            'mysql' => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config->host ?? '127.0.0.1',
                $config->port ?? 3306,
                $config->database,
                $config->charset ?? 'utf8mb4',
            ),
            'sqlsrv' => $this->sqlServerDsn($config),
            'oci' => sprintf(
                'oci:dbname=//%s:%d/%s;charset=%s',
                $config->host ?? '127.0.0.1',
                $config->port ?? 1521,
                $config->database,
                $config->charset ?? 'AL32UTF8',
            ),
        };
    }

    private function sqlServerDsn(DatabaseConfig $config): string
    {
        $dsn = sprintf(
            'sqlsrv:Server=%s,%d;Database=%s',
            $config->host ?? '127.0.0.1',
            $config->port ?? 1433,
            $config->database,
        );

        foreach ($config->driverOptions as $name => $value) {
            $dsn .= sprintf(';%s=%s', $name, $this->dsnOptionValue($value));
        }

        return $dsn;
    }

    private function dsnOptionValue(string|int|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }

    /** @return array<int, mixed> */
    private function pdoOptions(DatabaseConfig $config): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            ...$config->options,
        ];
    }
}
