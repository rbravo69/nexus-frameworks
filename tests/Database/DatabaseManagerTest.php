<?php

declare(strict_types=1);

namespace Nexus\Tests\Database;

use Nexus\Database\DatabaseConfig;
use Nexus\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;

final class DatabaseManagerTest extends TestCase
{
    public function testItResolvesDefaultAndNamedConnectionsLazily(): void
    {
        $manager = new DatabaseManager();
        $manager
            ->add('default', new DatabaseConfig('sqlite', ':memory:'))
            ->add('analytics', new DatabaseConfig('sqlite', ':memory:'));

        self::assertSame(['default', 'analytics'], $manager->names());
        self::assertSame($manager->connection(), $manager->connection('default'));
        self::assertNotSame($manager->connection(), $manager->connection('analytics'));
    }

    public function testDisconnectForcesAFreshConnection(): void
    {
        $manager = new DatabaseManager();
        $manager->add('default', new DatabaseConfig('sqlite', ':memory:'));
        $first = $manager->connection();

        $manager->disconnect();

        self::assertNotSame($first, $manager->connection());
    }

    public function testMissingConnectionFailsClearly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Database connection "missing" is not configured.');

        (new DatabaseManager())->connection('missing');
    }
}
