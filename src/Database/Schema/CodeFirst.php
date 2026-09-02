<?php

declare(strict_types=1);

namespace Nexus\Database\Schema;

use Nexus\Database\ConnectionInterface;
use Nexus\Database\Schema\Diff\SchemaDiffer;
use Nexus\Database\Schema\Diff\SchemaOperation;
use Nexus\Database\Schema\Introspection\SchemaIntrospector;
use Nexus\Database\Schema\Sql\SchemaCompiler;

final class CodeFirst
{
    public function __construct(
        private readonly SchemaIntrospector $introspector = new SchemaIntrospector(),
        private readonly SchemaDiffer $differ = new SchemaDiffer(),
        private readonly SchemaCompiler $compiler = new SchemaCompiler(),
    ) {
    }

    /** @return list<SchemaOperation> */
    public function diff(ConnectionInterface $connection, Schema $desired): array
    {
        return $this->differ->diff($this->introspector->inspect($connection), $desired);
    }

    /** @return list<string> */
    public function sql(ConnectionInterface $connection, Schema $desired): array
    {
        $sql = [];
        foreach ($this->diff($connection, $desired) as $operation) {
            array_push($sql, ...$this->compiler->compile($operation, $connection->driver()));
        }

        return $sql;
    }

    public function apply(ConnectionInterface $connection, Schema $desired, bool $allowDestructive = false): void
    {
        $operations = $this->diff($connection, $desired);

        foreach ($operations as $operation) {
            if ($operation->destructive && !$allowDestructive) {
                throw new \RuntimeException(sprintf('Destructive schema operation "%s" on "%s" requires explicit approval.', $operation->type, $operation->table));
            }
        }

        $connection->transaction(function (ConnectionInterface $db) use ($operations): void {
            foreach ($operations as $operation) {
                foreach ($this->compiler->compile($operation, $db->driver()) as $sql) {
                    $db->statement($sql);
                }
            }
        });
    }
}
