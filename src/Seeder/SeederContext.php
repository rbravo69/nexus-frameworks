<?php

declare(strict_types=1);

namespace Nexus\Seeder;

use Nexus\Fake\FakeGenerator;

final readonly class SeederContext
{
    public function __construct(
        public SeedScenario $scenario = SeedScenario::Dev,
        public string $environment = 'dev',
        public int $seed = 1,
        public ?SeedStoreInterface $store = null,
    ) {
        if ($environment === '') {
            throw new \InvalidArgumentException('Seeder environment cannot be empty.');
        }
    }

    public function fake(): FakeGenerator
    {
        return new FakeGenerator($this->seed);
    }
}
