<?php

declare(strict_types=1);

namespace Nexus\Tests\Database\Schema;

use Nexus\Database\ConnectionFactory;
use Nexus\Database\DatabaseConfig;
use Nexus\Database\Schema\CodeFirst;
use Nexus\Database\Schema\Column;
use Nexus\Database\Schema\Diff\SchemaDiffer;
use Nexus\Database\Schema\Introspection\SchemaIntrospector;
use Nexus\Database\Schema\Schema;
use Nexus\Database\Schema\Table;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    public function testDiffMarksDestructiveOperations(): void
    {
        $current = (new Schema())->table(
            (new Table('users'))
                ->column(new Column('id', 'integer', primary: true))
                ->column(new Column('legacy', 'string')),
        );
        $desired = (new Schema())->table(
            (new Table('users'))
                ->column(new Column('id', 'integer', primary: true))
                ->column(new Column('email', 'string')),
        );

        $operations = (new SchemaDiffer())->diff($current, $desired);

        self::assertCount(2, $operations);
        self::assertSame('add_column', $operations[0]->type);
        self::assertFalse($operations[0]->destructive);
        self::assertSame('drop_column', $operations[1]->type);
        self::assertTrue($operations[1]->destructive);
    }

    public function testCodeFirstCreatesAndExtendsSchema(): void
    {
        $connection = (new ConnectionFactory())->make(new DatabaseConfig('sqlite', ':memory:'));
        $schema = (new Schema())->table(
            (new Table('users'))
                ->column(new Column('id', 'integer', primary: true, autoIncrement: true))
                ->column(new Column('name', 'string')),
        );
        $codeFirst = new CodeFirst();

        $codeFirst->apply($connection, $schema);
        $schema->get('users')->column(new Column('email', 'string', nullable: true));
        $codeFirst->apply($connection, $schema);

        $connection->statement('INSERT INTO users (name, email) VALUES (:name, :email)', ['name' => 'Rafael', 'email' => 'rafael@example.com']);
        $rows = $connection->select('SELECT name, email FROM users');

        self::assertSame('Rafael', $rows[0]['name']);
        self::assertSame('rafael@example.com', $rows[0]['email']);
    }

    public function testDatabaseFirstInspectsExistingSqliteSchema(): void
    {
        $connection = (new ConnectionFactory())->make(new DatabaseConfig('sqlite', ':memory:'));
        $connection->statement('CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, sku VARCHAR(255) NOT NULL, price DECIMAL(18,2) NOT NULL)');

        $schema = (new SchemaIntrospector())->inspect($connection);

        self::assertTrue($schema->hasTable('products'));
        self::assertTrue($schema->get('products')->hasColumn('sku'));
        self::assertSame('decimal', $schema->get('products')->columns()['price']->type);
    }

    public function testCodeFirstRejectsDestructiveChangesByDefault(): void
    {
        $connection = (new ConnectionFactory())->make(new DatabaseConfig('sqlite', ':memory:'));
        $connection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, legacy TEXT)');
        $desired = (new Schema())->table((new Table('users'))->column(new Column('id', 'integer', primary: true)));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires explicit approval');

        (new CodeFirst())->apply($connection, $desired);
    }
}
