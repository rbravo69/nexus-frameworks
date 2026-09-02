<?php

declare(strict_types=1);

namespace Nexus\Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends NexusException implements ContainerExceptionInterface
{
}
