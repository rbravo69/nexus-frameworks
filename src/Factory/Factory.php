<?php

declare(strict_types=1);

namespace Nexus\Factory;

use Nexus\Fake\FakeGenerator;

abstract class Factory implements FactoryInterface
{
    /** @return array<string, mixed> */
    abstract protected function definition(FakeGenerator $fake): array;

    public function make(FakeGenerator $fake): array
    {
        return $this->definition($fake);
    }

    public function count(int $count, FakeGenerator $fake): array
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Factory count cannot be negative.');
        }

        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $records[] = $this->make($fake);
        }
        return $records;
    }
}
