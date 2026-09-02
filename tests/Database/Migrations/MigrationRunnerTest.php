<?php

declare(strict_types=1);

namespace Nexus\Tests\Database\Migrations;

use Nexus\Database\ConnectionFactory;
use Nexus\Database\ConnectionInterface;
use Nexus\Database\DatabaseConfig;
use Nexus\Database\Migrations\Migration;
use Nexus\Database\Migrations\MigrationRunner;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testItRunsPendingMigrationsOnlyAndRollsBackLastBatch(): void
    {
        $connection = (new ConnectionFactory())->make(new DatabaseConfig('sqlite', ':memory:'));
        $migration = new class implements Migration {
            public function id(): string
            {
                return '20260902_000001_create_users';
            }

            public function up(ConnectionInterface $connection): void
            {
                $connection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
            }

            public function down(ConnectionInterface $connection): void
            {
                $connection->statement('DROP TABLE users');
            }
        };
        $runner = new MigrationRunner($connection);

        self::assertSame(1, $runner->migrate([$migration]));
        self::assertSame(0, $runner->migrate([$migration]));
        self::assertSame([$migration->id()], $runner->applied());
        self::assertSame(1, $runner->rollback([$migration]));
        self::assertSame([], $runner->applied());
    }
}
