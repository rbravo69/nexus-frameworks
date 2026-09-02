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
        };
    }

    /** @return array<int, mixed> */
    private function pdoOptions(DatabaseConfig $config): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        foreach ($config->options as $key => $value) {
            if (!is_int($key)) {
                throw new \InvalidArgumentException('PDO option keys must be integer PDO attribute constants.');
            }

            $options[$key] = $value;
        }

        return $options;
    }
}
