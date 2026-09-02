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
            default => throw new \InvalidArgumentException(sprintf('Unsupported database driver "%s".', $connection->driver())),
        };
    }

    private function sqlite(ConnectionInterface $connection): Schema
    {
        $schema = new Schema();
        $tables = $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

        foreach ($tables as $row) {
            $name = (string) $row['name'];
            $table = new Table($name);
            $columns = $connection->select(sprintf('PRAGMA table_info("%s")', str_replace('"', '""', $name)));

            foreach ($columns as $column) {
                $type = strtolower((string) ($column['type'] ?? 'text'));
                $table->column(new Column(
                    name: (string) $column['name'],
                    type: $this->normalizeType($type),
                    nullable: ((int) ($column['notnull'] ?? 0)) === 0,
                    primary: ((int) ($column['pk'] ?? 0)) === 1,
                    autoIncrement: ((int) ($column['pk'] ?? 0)) === 1 && str_contains($type, 'int'),
                    default: $column['dflt_value'] ?? null,
                ));
            }

            $schema->table($table);
        }

        return $schema;
    }

    private function pgsql(ConnectionInterface $connection): Schema
    {
        $schema = new Schema();
        $rows = $connection->select("SELECT table_name, column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = 'public' ORDER BY table_name, ordinal_position");
        return $this->fromInformationSchema($rows, $schema);
    }

    private function mysql(ConnectionInterface $connection): Schema
    {
        $schema = new Schema();
        $rows = $connection->select('SELECT table_name, column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = DATABASE() ORDER BY table_name, ordinal_position');
        return $this->fromInformationSchema($rows, $schema);
    }

    /** @param list<array<string, mixed>> $rows */
    private function fromInformationSchema(array $rows, Schema $schema): Schema
    {
        /** @var array<string, Table> $tables */
        $tables = [];

        foreach ($rows as $row) {
            $tableName = (string) $row['table_name'];
            $tables[$tableName] ??= new Table($tableName);
            $tables[$tableName]->column(new Column(
                name: (string) $row['column_name'],
                type: $this->normalizeType((string) $row['data_type']),
                nullable: strtoupper((string) $row['is_nullable']) === 'YES',
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
        return match (true) {
            str_contains($type, 'bigint') => 'bigint',
            str_contains($type, 'int') => 'integer',
            str_contains($type, 'char') => 'string',
            str_contains($type, 'text') => 'text',
            str_contains($type, 'bool') => 'boolean',
            str_contains($type, 'json') => 'json',
            str_contains($type, 'date') && !str_contains($type, 'time') => 'date',
            str_contains($type, 'time') => 'datetime',
            str_contains($type, 'real'), str_contains($type, 'double'), str_contains($type, 'float') => 'double',
            str_contains($type, 'decimal'), str_contains($type, 'numeric') => 'decimal',
            default => strtolower($type),
        };
    }
}
