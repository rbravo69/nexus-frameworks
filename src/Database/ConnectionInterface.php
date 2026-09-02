<?php

declare(strict_types=1);

namespace Nexus\Database;

interface ConnectionInterface
{
    /**
     * @param array<string|int, mixed> $parameters
     * @return list<array<string, mixed>>
     */
    public function select(string $sql, array $parameters = []): array;

    /** @param array<string|int, mixed> $parameters */
    public function statement(string $sql, array $parameters = []): int;

    /**
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function inTransaction(): bool;

    public function driver(): string;
}
