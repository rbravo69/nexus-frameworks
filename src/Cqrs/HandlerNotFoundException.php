<?php

declare(strict_types=1);

namespace Nexus\Cqrs;

final class HandlerNotFoundException extends \RuntimeException
{
    public static function for(object $message): self
    {
        return new self(sprintf('No handler registered for "%s".', $message::class));
    }
}
