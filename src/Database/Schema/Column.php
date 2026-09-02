<?php

declare(strict_types=1);

namespace Nexus\Database\Schema;

final readonly class Column
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable = false,
        public bool $primary = false,
        public bool $autoIncrement = false,
        public mixed $default = null,
    ) {
        if ($name === '' || $type === '') {
            throw new \InvalidArgumentException('Column name and type cannot be empty.');
        }
    }
}
