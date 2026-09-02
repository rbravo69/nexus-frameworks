<?php

declare(strict_types=1);

namespace Nexus\Benchmark;

final readonly class BenchmarkResult
{
    public function __construct(
        public string $name,
        public int $iterations,
        public float $averageNanoseconds,
        public float $minimumNanoseconds,
        public float $maximumNanoseconds,
    ) {
    }

    public function operationsPerSecond(): float
    {
        return $this->averageNanoseconds > 0.0
            ? 1_000_000_000 / $this->averageNanoseconds
            : 0.0;
    }
}
