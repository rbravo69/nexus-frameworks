<?php

declare(strict_types=1);

namespace Nexus\Tests\Database\Mongo;

use Nexus\Database\Mongo\MongoConfig;
use Nexus\Database\Mongo\MongoConnectionInterface;
use Nexus\Database\Mongo\MongoManager;
use PHPUnit\Framework\TestCase;

final class MongoManagerTest extends TestCase
{
    public function testConfigAndNamedConnections(): void
    {
        $config = new MongoConfig('mongodb://127.0.0.1:27017', 'nexus');
        self::assertSame('nexus', $config->database);

        $connection = new InMemoryMongoConnection();
        $manager = (new MongoManager())->add('default', $connection);

        self::assertSame(['default'], $manager->names());
        self::assertSame($connection, $manager->connection());
    }

    public function testDocumentContractSupportsBasicOperations(): void
    {
        $connection = new InMemoryMongoConnection();
        $id = $connection->insert('users', ['name' => 'Rafael']);

        self::assertSame('1', $id);
        self::assertSame([['name' => 'Rafael', '_id' => '1']], $connection->find('users'));
        self::assertSame(1, $connection->update('users', ['_id' => '1'], ['name' => 'Rafa']));
        self::assertSame(1, $connection->delete('users', ['_id' => '1']));
        self::assertSame('email_1', $connection->createIndex('users', ['email' => 1], true));
    }
}

final class InMemoryMongoConnection implements MongoConnectionInterface
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $documents = [];

    public function insert(string $collection, array $document): string
    {
        $id = (string) (count($this->documents[$collection] ?? []) + 1);
        $document['_id'] = $id;
        $this->documents[$collection][] = $document;
        return $id;
    }

    public function find(string $collection, array $filter = []): array
    {
        return array_values(array_filter(
            $this->documents[$collection] ?? [],
            static fn (array $document): bool => self::matches($document, $filter),
        ));
    }

    public function update(string $collection, array $filter, array $update): int
    {
        $count = 0;
        foreach ($this->documents[$collection] ?? [] as $index => $document) {
            if (!self::matches($document, $filter)) {
                continue;
            }
            $this->documents[$collection][$index] = [...$document, ...$update];
            $count++;
        }
        return $count;
    }

    public function delete(string $collection, array $filter): int
    {
        $before = count($this->documents[$collection] ?? []);
        $this->documents[$collection] = array_values(array_filter(
            $this->documents[$collection] ?? [],
            static fn (array $document): bool => !self::matches($document, $filter),
        ));
        return $before - count($this->documents[$collection]);
    }

    public function createIndex(string $collection, array $keys, bool $unique = false): string
    {
        unset($collection, $unique);
        $parts = [];
        foreach ($keys as $field => $direction) {
            $parts[] = $field . '_' . (string) $direction;
        }
        return implode('_', $parts);
    }

    /** @param array<string, mixed> $document @param array<string, mixed> $filter */
    private static function matches(array $document, array $filter): bool
    {
        foreach ($filter as $key => $value) {
            if (($document[$key] ?? null) !== $value) {
                return false;
            }
        }
        return true;
    }
}
