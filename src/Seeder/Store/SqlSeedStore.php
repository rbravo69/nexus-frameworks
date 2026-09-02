<?php

declare(strict_types=1);

namespace Nexus\Seeder\Store;

use Nexus\Database\ConnectionInterface;
use Nexus\Seeder\SeedStoreInterface;

final readonly class SqlSeedStore implements SeedStoreInterface
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function insert(string $target, array $record): null
    {
        if ($record === []) {
            throw new \InvalidArgumentException('Seed record cannot be empty.');
        }

        $columns = array_keys($record);
        $quoted = array_map($this->quote(...), $columns);
        $parameters = [];
        $placeholders = [];

        foreach ($record as $column => $value) {
            $parameter = 'seed_' . count($parameters);
            $parameters[$parameter] = $value;
            $placeholders[] = ':' . $parameter;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quote($target),
            implode(', ', $quoted),
            implode(', ', $placeholders),
        );

        $this->connection->statement($sql, $parameters);
        return null;
    }

    private function quote(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new \InvalidArgumentException(sprintf('Unsafe SQL seed identifier "%s".', $identifier));
        }

        return match ($this->connection->driver()) {
            'mysql' => '`' . $identifier . '`',
            'sqlsrv' => '[' . $identifier . ']',
            default => '"' . $identifier . '"',
        };
    }
}
