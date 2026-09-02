<?php

declare(strict_types=1);

namespace Nexus\Database;

use PDOException;
use RuntimeException;

final class DatabaseException extends RuntimeException
{
    public static function fromPdo(PDOException $exception): self
    {
        return new self('Database operation failed.', (int) $exception->getCode(), $exception);
    }
}
