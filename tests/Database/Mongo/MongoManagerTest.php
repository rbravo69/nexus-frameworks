<?php

declare(strict_types=1);

namespace Nexus\Tests\Database\Mongo;

use Nexus\Database\Mongo\MongoConfig;
use Nexus\Database\Mongo\MongoConnectionInterface;
use Nexus\Database\Mongo\MongoIntrospector;
use Nexus\Database\Mongo\MongoManager;
use Nexus\Database\Mongo\MongoRepository;
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

    public function testRepositoryAndIntrospectionUseNeutralConnectionContract(): void
    {
        $connection = new InMemoryMongoConnection();
        $repository = new MongoRepository($connection, 'users');
        $id = $repository->insert(['name' => 'Rafael']);

        self::assertSame('1', $id);
        self::assertSame([['name' => 'Rafael', '_id' => '1']], $repository->find());
        self::assertSame(1, $repository->update(['_id' => '1'], ['name' => 'Rafa']));
        self::assertSame('email_1', $repository->createIndex(['email' => 1], true));
        self::assertSame(['users' => [['name' => 'email_1', 'key' => ['email' => 1], 'unique' => true]]], (new MongoIntrospector($connection))->inspect());
        self::assertSame(1, $repository->delete(['_id' => '1']));
    }
}

final class InMemoryMongoConnection implements MongoConnectionInterface
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $documents = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $indexes = [];

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
        $parts = [];
        foreach ($keys as $field => $direction) {
            if (!is_int($direction) && !is_string($direction)) {
                throw new \InvalidArgumentException('Index direction must be scalar.');
            }
            $parts[] = $field . '_' . $direction;
        }
        $name = implode('_', $parts);
        $this->indexes[$collection][] = ['name' => $name, 'key' => $keys, 'unique' => $unique];
        return $name;
    }

    public function collections(): array
    {
        $names = array_unique([...array_keys($this->documents), ...array_keys($this->indexes)]);
        sort($names);
        return array_values($names);
    }

    public function indexes(string $collection): array
    {
        return $this->indexes[$collection] ?? [];
    }

    /**
     * @param array<string, mixed> $document
     * @param array<string, mixed> $filter
     */
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
