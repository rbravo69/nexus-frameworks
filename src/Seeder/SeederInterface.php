<?php

declare(strict_types=1);

namespace Nexus\Seeder;

interface SeederInterface
{
    public function run(SeederContext $context): void;
}
