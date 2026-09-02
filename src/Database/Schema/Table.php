<?php

declare(strict_types=1);

namespace Nexus\Database\Schema;

final class Table
{
    /** @var array<string, Column> */
    private array $columns = [];

    public function __construct(public readonly string $name)
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Table name cannot be empty.');
        }
    }

    public function column(Column $column): self
    {
        $this->columns[$column->name] = $column;

        return $this;
    }

    /** @return array<string, Column> */
    public function columns(): array
    {
        return $this->columns;
    }

    public function hasColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }
}
