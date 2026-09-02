<?php

declare(strict_types=1);

namespace Nexus\Database\Schema;

final class Schema
{
    /** @var array<string, Table> */
    private array $tables = [];

    public function table(Table $table): self
    {
        $this->tables[$table->name] = $table;

        return $this;
    }

    /** @return array<string, Table> */
    public function tables(): array
    {
        return $this->tables;
    }

    public function hasTable(string $name): bool
    {
        return isset($this->tables[$name]);
    }

    public function get(string $name): Table
    {
        return $this->tables[$name] ?? throw new \InvalidArgumentException(sprintf('Unknown table "%s".', $name));
    }
}
