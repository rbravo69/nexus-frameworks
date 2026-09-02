<?php

declare(strict_types=1);

namespace Nexus\Database;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

final class PdoConnection implements ConnectionInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $driverName,
    ) {
    }

    /**
     * @param array<string|int, mixed> $parameters
     * @return list<array<string, mixed>>
     */
    public function select(string $sql, array $parameters = []): array
    {
        $statement = $this->prepareAndExecute($sql, $parameters);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!is_array($rows)) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    /** @param array<string|int, mixed> $parameters */
    public function statement(string $sql, array $parameters = []): int
    {
        return $this->prepareAndExecute($sql, $parameters)->rowCount();
    }

    /**
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }

            throw $exception;
        }
    }

    public function beginTransaction(): void
    {
        if (!$this->pdo->beginTransaction()) {
            throw new DatabaseException('Could not begin database transaction.');
        }
    }

    public function commit(): void
    {
        if (!$this->pdo->commit()) {
            throw new DatabaseException('Could not commit database transaction.');
        }
    }

    public function rollBack(): void
    {
        if (!$this->pdo->rollBack()) {
            throw new DatabaseException('Could not roll back database transaction.');
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function driver(): string
    {
        return $this->driverName;
    }

    /**
     * @param array<string|int, mixed> $parameters
     */
    private function prepareAndExecute(string $sql, array $parameters): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($sql);

            if (!$statement instanceof PDOStatement) {
                throw new DatabaseException('Could not prepare database statement.');
            }

            $statement->execute($parameters);

            return $statement;
        } catch (PDOException $exception) {
            throw DatabaseException::fromPdo($exception);
        }
    }
}
