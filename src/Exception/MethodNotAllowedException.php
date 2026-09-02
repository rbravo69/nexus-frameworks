<?php

declare(strict_types=1);

namespace Nexus\Exception;

final class MethodNotAllowedException extends \RuntimeException
{
    /** @param list<string> $allowedMethods */
    public function __construct(
        string $method,
        string $path,
        private readonly array $allowedMethods,
    ) {
        parent::__construct(sprintf(
            'Method %s is not allowed for %s. Allowed: %s.',
            strtoupper($method),
            $path,
            implode(', ', $allowedMethods),
        ));
    }

    /** @return list<string> */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
