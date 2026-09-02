<?php

declare(strict_types=1);

namespace Nexus\Tests\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Nexus\Database\DatabaseConfig;
use Nexus\Database\Eloquent\EloquentConfig;
use Nexus\Database\Eloquent\EloquentManager;
use PHPUnit\Framework\TestCase;

final class EloquentManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();
        parent::tearDown();
    }

    public function testItBootsEloquentAndPersistsModels(): void
    {
        $manager = (new EloquentManager())
            ->addConnection(new EloquentConfig(new DatabaseConfig('sqlite', ':memory:'), global: true))
            ->boot();

        $schema = $manager->connection()->getSchemaBuilder();
        $schema->create('users', static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });

        $user = new EloquentUser();
        $user->name = 'Rafael';
        $user->save();

        self::assertTrue($manager->isBooted());
        self::assertSame('Rafael', EloquentUser::query()->firstOrFail()->name);
    }

    public function testItSupportsNamedConnections(): void
    {
        $manager = (new EloquentManager())
            ->addConnection(new EloquentConfig(new DatabaseConfig('sqlite', ':memory:'), 'primary', true))
            ->addConnection(new EloquentConfig(new DatabaseConfig('sqlite', ':memory:'), 'analytics'))
            ->boot();

        self::assertSame('sqlite', $manager->connection('primary')->getDriverName());
        self::assertSame('sqlite', $manager->connection('analytics')->getDriverName());

        $model = $manager->setModelConnection(new EloquentUser(), 'analytics');
        self::assertSame('analytics', $model->getConnectionName());
    }

    public function testConfigMapsNexusDatabaseConfigurationToIlluminate(): void
    {
        $config = new EloquentConfig(new DatabaseConfig(
            driver: 'pgsql',
            database: 'nexus',
            host: 'db.internal',
            port: 5544,
            username: 'nexus_user',
            password: 'secret',
        ));

        $illuminate = $config->toIlluminate();

        self::assertSame('pgsql', $illuminate['driver']);
        self::assertSame('db.internal', $illuminate['host']);
        self::assertSame(5544, $illuminate['port']);
        self::assertSame('nexus', $illuminate['database']);
    }
}

/** @property string $name */
final class EloquentUser extends Model
{
    public $timestamps = false;
    protected $table = 'users';
    protected $guarded = [];
}
