<?php

declare(strict_types=1);

namespace Nexus\Benchmark;

final class BenchmarkRunner
{
    public function run(string $name, callable $benchmark, int $iterations = 1000, int $warmup = 50): BenchmarkResult
    {
        if ($iterations < 1) {
            throw new \InvalidArgumentException('Benchmark iterations must be greater than zero.');
        }

        if ($warmup < 0) {
            throw new \InvalidArgumentException('Benchmark warmup cannot be negative.');
        }

        for ($index = 0; $index < $warmup; $index++) {
            $benchmark();
        }

        $samples = [];

        for ($index = 0; $index < $iterations; $index++) {
            $start = hrtime(true);
            $benchmark();
            $samples[] = (float) (hrtime(true) - $start);
        }

        return new BenchmarkResult(
            $name,
            $iterations,
            array_sum($samples) / $iterations,
            min($samples),
            max($samples),
        );
    }
}
