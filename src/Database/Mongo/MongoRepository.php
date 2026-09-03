<?php

declare(strict_types=1);

namespace Nexus\Database\Mongo;

final readonly class MongoRepository
{
    public function __construct(
        private MongoConnectionInterface $connection,
        private string $collection,
    ) {
        if ($collection === '') {
            throw new \InvalidArgumentException('MongoDB collection cannot be empty.');
        }
    }

    /** @param array<string, mixed> $document */
    public function insert(array $document): string
    {
        return $this->connection->insert($this->collection, $document);
    }

    /**
     * @param array<string, mixed> $filter
     * @return list<array<string, mixed>>
     */
    public function find(array $filter = []): array
    {
        return $this->connection->find($this->collection, $filter);
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $update
     */
    public function update(array $filter, array $update): int
    {
        return $this->connection->update($this->collection, $filter, $update);
    }

    /** @param array<string, mixed> $filter */
    public function delete(array $filter): int
    {
        return $this->connection->delete($this->collection, $filter);
    }

    /** @param array<string, mixed> $keys */
    public function createIndex(array $keys, bool $unique = false): string
    {
        return $this->connection->createIndex($this->collection, $keys, $unique);
    }
}
