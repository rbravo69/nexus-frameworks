<?php

declare(strict_types=1);

namespace Nexus\Database\Schema\Sql;

use Nexus\Database\Schema\Column;
use Nexus\Database\Schema\Diff\SchemaOperation;
use Nexus\Database\Schema\Table;

final class SchemaCompiler
{
    /** @return list<string> */
    public function compile(SchemaOperation $operation, string $driver): array
    {
        return match ($operation->type) {
            'create_table' => [$this->compileCreateTable($this->requireTable($operation), $driver)],
            'drop_table' => [sprintf('DROP TABLE %s', $this->quote($operation->table, $driver))],
            'add_column' => [sprintf(
                'ALTER TABLE %s ADD COLUMN %s',
                $this->quote($operation->table, $driver),
                $this->columnSql($this->requireColumn($operation), $driver),
            )],
            'drop_column' => [sprintf(
                'ALTER TABLE %s DROP COLUMN %s',
                $this->quote($operation->table, $driver),
                $this->quote($this->requireColumn($operation)->name, $driver),
            )],
            default => throw new \InvalidArgumentException(sprintf('Unsupported schema operation "%s".', $operation->type)),
        };
    }

    private function compileCreateTable(Table $table, string $driver): string
    {
        $columns = array_map(
            fn (Column $column): string => $this->columnSql($column, $driver),
            array_values($table->columns()),
        );

        if ($columns === []) {
            throw new \InvalidArgumentException('Cannot create a table without columns.');
        }

        return sprintf(
            'CREATE TABLE %s (%s)',
            $this->quote($table->name, $driver),
            implode(', ', $columns),
        );
    }

    private function columnSql(Column $column, string $driver): string
    {
        if ($column->autoIncrement && $column->primary) {
            return match ($driver) {
                'sqlite' => sprintf('%s INTEGER PRIMARY KEY AUTOINCREMENT', $this->quote($column->name, $driver)),
                'pgsql' => sprintf('%s BIGSERIAL PRIMARY KEY', $this->quote($column->name, $driver)),
                'mysql' => sprintf('%s BIGINT AUTO_INCREMENT PRIMARY KEY', $this->quote($column->name, $driver)),
                default => throw new \InvalidArgumentException(sprintf('Unsupported database driver "%s".', $driver)),
            };
        }

        $sql = $this->quote($column->name, $driver) . ' ' . $this->mapType($column->type, $driver);

        if ($column->primary) {
            $sql .= ' PRIMARY KEY';
        }

        if (!$column->nullable) {
            $sql .= ' NOT NULL';
        }

        if ($column->default !== null) {
            $sql .= ' DEFAULT ' . $this->literal($column->default);
        }

        return $sql;
    }

    private function mapType(string $type, string $driver): string
    {
        $normalized = strtolower($type);

        return match ($normalized) {
            'integer', 'int' => 'INTEGER',
            'bigint' => 'BIGINT',
            'string', 'varchar' => 'VARCHAR(255)',
            'text' => 'TEXT',
            'boolean', 'bool' => $driver === 'mysql' ? 'TINYINT(1)' : 'BOOLEAN',
            'float', 'double' => $driver === 'pgsql' ? 'DOUBLE PRECISION' : 'DOUBLE',
            'decimal' => 'DECIMAL(18,2)',
            'date' => 'DATE',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'json' => $driver === 'sqlite' ? 'TEXT' : 'JSON',
            default => strtoupper($type),
        };
    }

    private function quote(string $identifier, string $driver): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Unsafe SQL identifier "%s".', $identifier));
        }

        return $driver === 'mysql' ? '`' . $identifier . '`' : '"' . $identifier . '"';
    }

    private function literal(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value) => (string) $value,
            is_string($value) => "'" . str_replace("'", "''", $value) . "'",
            default => throw new \InvalidArgumentException('Unsupported default value type.'),
        };
    }

    private function requireTable(SchemaOperation $operation): Table
    {
        return $operation->tableDefinition
            ?? throw new \LogicException('Schema operation requires a table definition.');
    }

    private function requireColumn(SchemaOperation $operation): Column
    {
        return $operation->column
            ?? throw new \LogicException('Schema operation requires a column definition.');
    }
}
