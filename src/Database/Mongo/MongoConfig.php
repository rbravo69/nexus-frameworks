<?php

declare(strict_types=1);

namespace Nexus\Database\Mongo;

final readonly class MongoConfig
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public string $uri,
        public string $database,
        public array $options = [],
    ) {
        if ($uri === '') {
            throw new \InvalidArgumentException('MongoDB URI cannot be empty.');
        }
        if ($database === '') {
            throw new \InvalidArgumentException('MongoDB database cannot be empty.');
        }
    }
}
