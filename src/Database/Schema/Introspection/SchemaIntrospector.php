<?php

declare(strict_types=1);

namespace Nexus\Database\Schema\Introspection;

use Nexus\Database\ConnectionInterface;
use Nexus\Database\Schema\Column;
use Nexus\Database\Schema\Schema;
use Nexus\Database\Schema\Table;

final class SchemaIntrospector
{
    public function inspect(ConnectionInterface $connection): Schema
    {
        return match ($connection->driver()) {
            'sqlite' => $this->sqlite($connection),
            'pgsql' => $this->pgsql($connection),
            'mysql' => $this->mysql($connection),
            'sqlsrv' => $this->sqlsrv($connection),
            'oci' => $this->oci($connection),
            default => throw new \InvalidArgumentException(sprintf('Unsupported database driver "%s".', $connection->driver())),
        };
    }

    private function sqlite(ConnectionInterface $connection): Schema
    {
        $schema = new Schema();
        $tables = $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

        foreach ($tables as $row) {
            $name = $this->stringValue($row['name'] ?? null, 'name');
            $table = new Table($name);
            $columns = $connection->select(sprintf('PRAGMA table_info("%s")', str_replace('"', '""', $name)));

            foreach ($columns as $column) {
                $type = strtolower($this->stringValue($column['type'] ?? 'text', 'type'));
                $notNull = $this->intValue($column['notnull'] ?? 0, 'notnull');
                $primary = $this->intValue($column['pk'] ?? 0, 'pk') === 1;
                $table->column(new Column(
                    name: $this->stringValue($column['name'] ?? null, 'name'),
                    type: $this->normalizeType($type),
                    nullable: $notNull === 0,
                    primary: $primary,
                    autoIncrement: $primary && str_contains($type, 'int'),
                    default: $column['dflt_value'] ?? null,
                ));
            }
            $schema->table($table);
        }

        return $schema;
    }

    private function pgsql(ConnectionInterface $connection): Schema
    {
        return $this->fromInformationSchema($connection->select("SELECT table_name, column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = 'public' ORDER BY table_name, ordinal_position"), new Schema());
    }

    private function mysql(ConnectionInterface $connection): Schema
    {
        return $this->fromInformationSchema($connection->select('SELECT table_name, column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = DATABASE() ORDER BY table_name, ordinal_position'), new Schema());
    }

    private function sqlsrv(ConnectionInterface $connection): Schema
    {
        return $this->fromInformationSchema($connection->select("SELECT table_name, column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = 'dbo' ORDER BY table_name, ordinal_position"), new Schema());
    }

    private function oci(ConnectionInterface $connection): Schema
    {
        $rows = $connection->select("SELECT table_name, column_name, data_type, nullable AS is_nullable, data_default AS column_default FROM user_tab_columns ORDER BY table_name, column_id");
        return $this->fromInformationSchema($rows, new Schema());
    }

    /** @param list<array<string, mixed>> $rows */
    private function fromInformationSchema(array $rows, Schema $schema): Schema
    {
        /** @var array<string, Table> $tables */
        $tables = [];
        foreach ($rows as $row) {
            $tableName = $this->stringValue($row['table_name'] ?? null, 'table_name');
            $tables[$tableName] ??= new Table($tableName);
            $nullable = strtoupper($this->stringValue($row['is_nullable'] ?? null, 'is_nullable'));
            $tables[$tableName]->column(new Column(
                name: $this->stringValue($row['column_name'] ?? null, 'column_name'),
                type: $this->normalizeType($this->stringValue($row['data_type'] ?? null, 'data_type')),
                nullable: in_array($nullable, ['YES', 'Y'], true),
                default: $row['column_default'] ?? null,
            ));
        }

        foreach ($tables as $table) {
            $schema->table($table);
        }
        return $schema;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower($type);
        return match (true) {
            str_contains($type, 'bigint') => 'bigint',
            str_contains($type, 'number') => 'decimal',
            str_contains($type, 'int') => 'integer',
            str_contains($type, 'char') => 'string',
            str_contains($type, 'clob'), str_contains($type, 'text') => 'text',
            str_contains($type, 'bool'), $type === 'bit' => 'boolean',
            str_contains($type, 'json') => 'json',
            str_contains($type, 'date') && !str_contains($type, 'time') => 'date',
            str_contains($type, 'time') => 'datetime',
            str_contains($type, 'real'), str_contains($type, 'double'), str_contains($type, 'float') => 'double',
            str_contains($type, 'decimal'), str_contains($type, 'numeric') => 'decimal',
            default => $type,
        };
    }

    private function stringValue(mixed $value, string $field): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new \UnexpectedValueException(sprintf('Database field "%s" must be scalar.', $field));
        }
        return (string) $value;
    }

    private function intValue(mixed $value, string $field): int
    {
        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            throw new \UnexpectedValueException(sprintf('Database field "%s" must be numeric.', $field));
        }
        return (int) $value;
    }
}
