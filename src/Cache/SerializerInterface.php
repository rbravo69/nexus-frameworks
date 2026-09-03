<?php

declare(strict_types=1);

namespace Nexus\Cache;

interface SerializerInterface
{
    public function serialize(mixed $value): string;

    public function unserialize(string $payload): mixed;
}
