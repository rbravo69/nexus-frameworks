<?php

declare(strict_types=1);

namespace Nexus\Database\Schema\Diff;

use Nexus\Database\Schema\Column;
use Nexus\Database\Schema\Table;

final readonly class SchemaOperation
{
    private function __construct(
        public string $type,
        public string $table,
        public ?Table $tableDefinition = null,
        public ?Column $column = null,
        public bool $destructive = false,
    ) {
    }

    public static function createTable(Table $table): self
    {
        return new self('create_table', $table->name, $table);
    }

    public static function dropTable(string $table): self
    {
        return new self('drop_table', $table, destructive: true);
    }

    public static function addColumn(string $table, Column $column): self
    {
        return new self('add_column', $table, column: $column);
    }

    public static function dropColumn(string $table, Column $column): self
    {
        return new self('drop_column', $table, column: $column, destructive: true);
    }
}
