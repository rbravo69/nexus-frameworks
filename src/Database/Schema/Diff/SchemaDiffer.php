<?php

declare(strict_types=1);

namespace Nexus\Database\Schema\Diff;

use Nexus\Database\Schema\Schema;

final class SchemaDiffer
{
    /** @return list<SchemaOperation> */
    public function diff(Schema $current, Schema $desired): array
    {
        $operations = [];

        foreach ($desired->tables() as $tableName => $desiredTable) {
            if (!$current->hasTable($tableName)) {
                $operations[] = SchemaOperation::createTable($desiredTable);
                continue;
            }

            $currentTable = $current->get($tableName);

            foreach ($desiredTable->columns() as $columnName => $column) {
                if (!$currentTable->hasColumn($columnName)) {
                    $operations[] = SchemaOperation::addColumn($tableName, $column);
                }
            }

            foreach ($currentTable->columns() as $columnName => $column) {
                if (!$desiredTable->hasColumn($columnName)) {
                    $operations[] = SchemaOperation::dropColumn($tableName, $column);
                }
            }
        }

        foreach ($current->tables() as $tableName => $table) {
            if (!$desired->hasTable($tableName)) {
                $operations[] = SchemaOperation::dropTable($tableName);
            }
        }

        return $operations;
    }
}
