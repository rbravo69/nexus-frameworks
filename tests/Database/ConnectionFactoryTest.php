<?php

declare(strict_types=1);

namespace Nexus\Tests\Database;

use Nexus\Database\ConnectionFactory;
use Nexus\Database\DatabaseConfig;
use PHPUnit\Framework\TestCase;

final class ConnectionFactoryTest extends TestCase
{
    public function testItBuildsPostgresDsn(): void
    {
        $dsn = (new ConnectionFactory())->dsn(new DatabaseConfig(driver: 'pgsql', database: 'nexus', host: 'db.internal', port: 5544));
        self::assertSame('pgsql:host=db.internal;port=5544;dbname=nexus', $dsn);
    }

    public function testItBuildsMysqlDsnWithUtf8mb4ByDefault(): void
    {
        $dsn = (new ConnectionFactory())->dsn(new DatabaseConfig(driver: 'mysql', database: 'nexus'));
        self::assertSame('mysql:host=127.0.0.1;port=3306;dbname=nexus;charset=utf8mb4', $dsn);
    }

    public function testItBuildsSqliteDsn(): void
    {
        $dsn = (new ConnectionFactory())->dsn(new DatabaseConfig(driver: 'sqlite', database: ':memory:'));
        self::assertSame('sqlite::memory:', $dsn);
    }

    public function testItBuildsSqlServerDsn(): void
    {
        $dsn = (new ConnectionFactory())->dsn(new DatabaseConfig(driver: 'sqlsrv', database: 'nexus', host: 'sql.internal', port: 1444));
        self::assertSame('sqlsrv:Server=sql.internal,1444;Database=nexus', $dsn);
    }

    public function testItBuildsOracleDsn(): void
    {
        $dsn = (new ConnectionFactory())->dsn(new DatabaseConfig(driver: 'oci', database: 'FREEPDB1', host: 'oracle.internal'));
        self::assertSame('oci:dbname=//oracle.internal:1521/FREEPDB1;charset=AL32UTF8', $dsn);
    }

    public function testUnsupportedDriverIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DatabaseConfig('db2', 'nexus');
    }
}
