<?php

declare(strict_types=1);

namespace Nexus\Tests\Database;

use Nexus\Database\ConnectionFactory;
use Nexus\Database\DatabaseConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdoConnectionTest extends TestCase
{
    public function testItExecutesPreparedStatementsAndSelectsRows(): void
    {
        $connection = (new ConnectionFactory())->make(new DatabaseConfig('sqlite', ':memory:'));
        $connection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $connection->statement('INSERT INTO users (name) VALUES (:name)', ['name' => 'Rafael']);

        $rows = $connection->select('SELECT id, name FROM users WHERE name = :name', ['name' => 'Rafael']);

        self::assertCount(1, $rows);
        self::assertSame('Rafael', $rows[0]['name']);
        self::assertSame('sqlite', $connection->driver());
    }

    public function testTransactionCommitsOnSuccess(): void
    {
        $connection = (new ConnectionFactory())->make(new DatabaseConfig('sqlite', ':memory:'));
        $connection->statement('CREATE TABLE entries (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');

        $result = $connection->transaction(function ($db): string {
            $db->statement('INSERT INTO entries (value) VALUES (?)', ['ok']);

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertCount(1, $connection->select('SELECT * FROM entries'));
        self::assertFalse($connection->inTransaction());
    }

    public function testTransactionRollsBackOnFailure(): void
    {
        $connection = (new ConnectionFactory())->make(new DatabaseConfig('sqlite', ':memory:'));
        $connection->statement('CREATE TABLE entries (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        $caught = null;

        try {
            $connection->transaction(function ($db): void {
                $db->statement('INSERT INTO entries (value) VALUES (?)', ['rollback']);
                throw new RuntimeException('stop');
            });
        } catch (RuntimeException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(RuntimeException::class, $caught);
        self::assertSame('stop', $caught->getMessage());
        self::assertSame([], $connection->select('SELECT * FROM entries'));
    }
}
