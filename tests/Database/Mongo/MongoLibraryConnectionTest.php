<?php

declare(strict_types=1);

namespace Nexus\Tests\Database\Mongo;

use Nexus\Database\Mongo\MongoLibraryConnection;
use PHPUnit\Framework\TestCase;

final class MongoLibraryConnectionTest extends TestCase
{
    public function testAdapterDelegatesCrudIndexesAndIntrospection(): void
    {
        $client = new FakeMongoClient();
        $connection = MongoLibraryConnection::fromClient($client, 'nexus');

        self::assertSame('abc123', $connection->insert('users', ['name' => 'Rafael']));
        self::assertSame([['name' => 'Rafael']], $connection->find('users', ['active' => true]));
        self::assertSame(2, $connection->update('users', ['active' => true], ['$set' => ['active' => false]]));
        self::assertSame(1, $connection->delete('users', ['active' => false]));
        self::assertSame('email_1', $connection->createIndex('users', ['email' => 1], true));
        self::assertSame(['logs', 'users'], $connection->collections());
        self::assertSame([['name' => '_id_', 'key' => ['_id' => 1]]], $connection->indexes('users'));
    }
}

final class FakeMongoClient
{
    public FakeMongoCollection $collection;

    public function __construct()
    {
        $this->collection = new FakeMongoCollection();
    }

    public function selectCollection(string $database, string $collection): object
    {
        unset($database, $collection);
        return $this->collection;
    }

    public function selectDatabase(string $database): object
    {
        unset($database);
        return new FakeMongoDatabase();
    }
}

final class FakeMongoDatabase
{
    /** @return list<object> */
    public function listCollections(): array
    {
        return [new FakeCollectionInfo('users'), new FakeCollectionInfo('logs')];
    }
}

final readonly class FakeCollectionInfo
{
    public function __construct(private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}

final class FakeMongoCollection
{
    /** @param array<string, mixed> $document */
    public function insertOne(array $document): object
    {
        unset($document);
        return new class {
            public function getInsertedId(): string
            {
                return 'abc123';
            }
        };
    }

    /**
     * @param array<string, mixed> $filter
     * @return list<array<string, mixed>>
     */
    public function find(array $filter): array
    {
        unset($filter);
        return [['name' => 'Rafael']];
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $update
     */
    public function updateMany(array $filter, array $update): object
    {
        unset($filter, $update);
        return new class {
            public function getModifiedCount(): int
            {
                return 2;
            }
        };
    }

    /** @param array<string, mixed> $filter */
    public function deleteMany(array $filter): object
    {
        unset($filter);
        return new class {
            public function getDeletedCount(): int
            {
                return 1;
            }
        };
    }

    /**
     * @param array<string, mixed> $keys
     * @param array<string, mixed> $options
     */
    public function createIndex(array $keys, array $options): string
    {
        unset($keys, $options);
        return 'email_1';
    }

    /** @return list<array<string, mixed>> */
    public function listIndexes(): array
    {
        return [['name' => '_id_', 'key' => ['_id' => 1]]];
    }
}
