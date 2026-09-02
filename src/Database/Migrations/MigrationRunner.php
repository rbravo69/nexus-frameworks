<?php

declare(strict_types=1);

namespace Nexus\Database\Migrations;

use Nexus\Database\ConnectionInterface;

final class MigrationRunner
{
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    /** @param list<Migration> $migrations */
    public function migrate(array $migrations): int
    {
        $this->ensureRepository();
        $applied = array_fill_keys($this->applied(), true);
        $batch = $this->nextBatch();
        $count = 0;

        foreach ($migrations as $migration) {
            if (isset($applied[$migration->id()])) {
                continue;
            }

            $this->connection->transaction(function (ConnectionInterface $db) use ($migration, $batch): void {
                $migration->up($db);
                $db->statement(
                    'INSERT INTO nexus_migrations (migration, batch) VALUES (:migration, :batch)',
                    ['migration' => $migration->id(), 'batch' => $batch],
                );
            });
            $count++;
        }

        return $count;
    }

    /** @param list<Migration> $migrations */
    public function rollback(array $migrations): int
    {
        $this->ensureRepository();
        $lastBatch = $this->lastBatch();
        if ($lastBatch === 0) {
            return 0;
        }

        $byId = [];
        foreach ($migrations as $migration) {
            $byId[$migration->id()] = $migration;
        }

        $rows = $this->connection->select(
            'SELECT migration FROM nexus_migrations WHERE batch = :batch ORDER BY id DESC',
            ['batch' => $lastBatch],
        );
        $count = 0;

        foreach ($rows as $row) {
            $id = (string) $row['migration'];
            $migration = $byId[$id] ?? throw new \RuntimeException(sprintf('Migration "%s" is not available for rollback.', $id));

            $this->connection->transaction(function (ConnectionInterface $db) use ($migration, $id): void {
                $migration->down($db);
                $db->statement('DELETE FROM nexus_migrations WHERE migration = :migration', ['migration' => $id]);
            });
            $count++;
        }

        return $count;
    }

    /** @return list<string> */
    public function applied(): array
    {
        $this->ensureRepository();
        return array_map(
            static fn (array $row): string => (string) $row['migration'],
            $this->connection->select('SELECT migration FROM nexus_migrations ORDER BY id'),
        );
    }

    private function ensureRepository(): void
    {
        $sql = match ($this->connection->driver()) {
            'mysql' => 'CREATE TABLE IF NOT EXISTS nexus_migrations (id BIGINT AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL UNIQUE, batch INTEGER NOT NULL)',
            'pgsql' => 'CREATE TABLE IF NOT EXISTS nexus_migrations (id BIGSERIAL PRIMARY KEY, migration VARCHAR(255) NOT NULL UNIQUE, batch INTEGER NOT NULL)',
            default => 'CREATE TABLE IF NOT EXISTS nexus_migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration VARCHAR(255) NOT NULL UNIQUE, batch INTEGER NOT NULL)',
        };
        $this->connection->statement($sql);
    }

    private function nextBatch(): int
    {
        return $this->lastBatch() + 1;
    }

    private function lastBatch(): int
    {
        $rows = $this->connection->select('SELECT MAX(batch) AS batch FROM nexus_migrations');
        return (int) ($rows[0]['batch'] ?? 0);
    }
}
