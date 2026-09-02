<?php

declare(strict_types=1);

namespace Nexus\Factory;

use Nexus\Fake\FakeGenerator;

interface FactoryInterface
{
    /** @return array<string, mixed> */
    public function make(FakeGenerator $fake): array;

    /** @return list<array<string, mixed>> */
    public function count(int $count, FakeGenerator $fake): array;
}
