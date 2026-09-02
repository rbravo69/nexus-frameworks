<?php

declare(strict_types=1);

namespace Nexus\Seeder;

interface SeedStoreInterface
{
    /** @param array<string, mixed> $record */
    public function insert(string $target, array $record): string|int|null;
}
