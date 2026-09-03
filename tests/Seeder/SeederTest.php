<?php

declare(strict_types=1);

namespace Nexus\Tests\Seeder;

use Nexus\Database\Mongo\MongoConnectionInterface;
use Nexus\Database\PdoConnection;
use Nexus\Factory\Factory;
use Nexus\Fake\FakeGenerator;
use Nexus\Seeder\SeedScenario;
use Nexus\Seeder\SeederContext;
use Nexus\Seeder\SeederInterface;
use Nexus\Seeder\SeederRunner;
use Nexus\Seeder\Store\MongoSeedStore;
use Nexus\Seeder\Store\SqlSeedStore;
use PDO;
use PHPUnit\Framework\TestCase;

final class SeederTest extends TestCase
{
    public function testFakeGeneratorIsDeterministic(): void
    {
        $a = new FakeGenerator(42);
        $b = new FakeGenerator(42);

        self::assertSame($a->name(), $b->name());
        self::assertSame($a->email(), $b->email());
        self::assertSame($a->integer(1, 1000), $b->integer(1, 1000));
    }

    public function testFactoryBuildsRequestedNumberOfRecords(): void
    {
        $factory = new UserFactory();
        $records = $factory->count(3, new FakeGenerator(7));

        self::assertCount(3, $records);
        self::assertArrayHasKey('name', $records[0]);
        self::assertArrayHasKey('email', $records[0]);
    }

    public function testRunnerPassesScenarioAndStoreToSeeders(): void
    {
        $store = new RecordingStore();
        $runner = (new SeederRunner())->add(new DemoSeeder());
        $runner->run(new SeederContext(SeedScenario::Demo, 'dev', 9, $store));

        self::assertSame([['users', ['scenario' => 'demo']]], $store->records);
        self::assertSame([DemoSeeder::class], $runner->seeders());
    }

    public function testSqlSeedStoreWritesToSqlite(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $connection = new PdoConnection($pdo, 'sqlite');
        $connection->statement('CREATE TABLE users (name TEXT NOT NULL, email TEXT NOT NULL)');
        $store = new SqlSeedStore($connection);

        $store->insert('users', ['name' => 'Rafael', 'email' => 'rafael@example.test']);

        self::assertSame(
            [['name' => 'Rafael', 'email' => 'rafael@example.test']],
            $connection->select('SELECT name, email FROM users'),
        );
    }

    public function testMongoSeedStoreDelegatesDocumentInsert(): void
    {
        $connection = new RecordingMongoConnection();
        $id = (new MongoSeedStore($connection))->insert('users', ['name' => 'Rafael']);

        self::assertSame('mongo-1', $id);
        self::assertSame([['users', ['name' => 'Rafael']]], $connection->inserts);
    }
}

final class UserFactory extends Factory
{
    protected function definition(FakeGenerator $fake): array
    {
        $name = $fake->name();
        return ['name' => $name, 'email' => $fake->email($name)];
    }
}

final class DemoSeeder implements SeederInterface
{
    public function run(SeederContext $context): void
    {
        $context->store?->insert('users', ['scenario' => $context->scenario->value]);
    }
}

final class RecordingStore implements \Nexus\Seeder\SeedStoreInterface
{
    /** @var list<array{string, array<string, mixed>}> */
    public array $records = [];

    public function insert(string $target, array $record): null
    {
        $this->records[] = [$target, $record];
        return null;
    }
}

final class RecordingMongoConnection implements MongoConnectionInterface
{
    /** @var list<array{string, array<string, mixed>}> */
    public array $inserts = [];

    public function insert(string $collection, array $document): string
    {
        $this->inserts[] = [$collection, $document];
        return 'mongo-1';
    }

    public function find(string $collection, array $filter = []): array
    {
        return [];
    }

    public function update(string $collection, array $filter, array $update): int
    {
        return 0;
    }

    public function delete(string $collection, array $filter): int
    {
        return 0;
    }

    public function createIndex(string $collection, array $keys, bool $unique = false): string
    {
        return 'index';
    }

    public function collections(): array
    {
        return [];
    }

    public function indexes(string $collection): array
    {
        return [];
    }
}
