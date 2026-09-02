<?php

declare(strict_types=1);

namespace Nexus\Exception;

use Psr\Container\NotFoundExceptionInterface;

final class ServiceNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    public static function for(string $id): self
    {
        return new self(sprintf('Service "%s" is not bound and cannot be autowired.', $id));
    }
}
