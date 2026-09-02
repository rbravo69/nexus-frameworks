<?php

declare(strict_types=1);

namespace Nexus\Seeder;

final class SeederRunner
{
    /** @var list<SeederInterface> */
    private array $seeders = [];

    public function add(SeederInterface $seeder): self
    {
        $this->seeders[] = $seeder;
        return $this;
    }

    public function run(SeederContext $context): void
    {
        foreach ($this->seeders as $seeder) {
            $seeder->run($context);
        }
    }

    /** @return list<class-string<SeederInterface>> */
    public function seeders(): array
    {
        return array_map(static fn (SeederInterface $seeder): string => $seeder::class, $this->seeders);
    }
}
