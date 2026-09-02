<?php

declare(strict_types=1);

namespace Nexus\Database\Migrations;

use Nexus\Database\ConnectionInterface;

interface Migration
{
    public function id(): string;

    public function up(ConnectionInterface $connection): void;

    public function down(ConnectionInterface $connection): void;
}
