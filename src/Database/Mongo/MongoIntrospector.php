<?php

declare(strict_types=1);

namespace Nexus\Database\Mongo;

final readonly class MongoIntrospector
{
    public function __construct(private MongoConnectionInterface $connection)
    {
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function inspect(): array
    {
        $snapshot = [];

        foreach ($this->connection->collections() as $collection) {
            $snapshot[$collection] = $this->connection->indexes($collection);
        }

        ksort($snapshot);

        return $snapshot;
    }
}
