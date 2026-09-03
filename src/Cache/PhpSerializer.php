<?php

declare(strict_types=1);

namespace Nexus\Cache;

final readonly class PhpSerializer implements SerializerInterface
{
    /** @param bool|list<class-string> $allowedClasses */
    public function __construct(private bool|array $allowedClasses = false)
    {
    }

    public function serialize(mixed $value): string
    {
        return serialize($value);
    }

    public function unserialize(string $payload): mixed
    {
        return unserialize($payload, ['allowed_classes' => $this->allowedClasses]);
    }
}
