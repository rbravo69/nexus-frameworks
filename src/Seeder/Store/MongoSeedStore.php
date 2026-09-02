<?php

declare(strict_types=1);

namespace Nexus\Seeder\Store;

use Nexus\Database\Mongo\MongoConnectionInterface;
use Nexus\Seeder\SeedStoreInterface;

final readonly class MongoSeedStore implements SeedStoreInterface
{
    public function __construct(private MongoConnectionInterface $connection)
    {
    }

    public function insert(string $target, array $record): string
    {
        return $this->connection->insert($target, $record);
    }
}
