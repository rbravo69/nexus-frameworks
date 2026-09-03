<?php

declare(strict_types=1);

namespace Nexus\Database\Mongo;

interface MongoConnectionInterface
{
    /** @param array<string, mixed> $document */
    public function insert(string $collection, array $document): string;

    /**
     * @param array<string, mixed> $filter
     * @return list<array<string, mixed>>
     */
    public function find(string $collection, array $filter = []): array;

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $update
     */
    public function update(string $collection, array $filter, array $update): int;

    /** @param array<string, mixed> $filter */
    public function delete(string $collection, array $filter): int;

    /** @param array<string, mixed> $keys */
    public function createIndex(string $collection, array $keys, bool $unique = false): string;

    /** @return list<string> */
    public function collections(): array;

    /** @return list<array<string, mixed>> */
    public function indexes(string $collection): array;
}
