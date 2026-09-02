<?php

declare(strict_types=1);

namespace Nexus\Tests\Benchmark;

use Nexus\Benchmark\BenchmarkRunner;
use PHPUnit\Framework\TestCase;

final class BenchmarkRunnerTest extends TestCase
{
    public function testItMeasuresARepeatableCallable(): void
    {
        $calls = 0;
        $result = (new BenchmarkRunner())->run(
            'counter',
            static function () use (&$calls): void {
                $calls++;
            },
            iterations: 10,
            warmup: 2,
        );

        self::assertSame('counter', $result->name);
        self::assertSame(10, $result->iterations);
        self::assertSame(12, $calls);
        self::assertGreaterThanOrEqual(0.0, $result->minimumNanoseconds);
        self::assertGreaterThanOrEqual($result->minimumNanoseconds, $result->averageNanoseconds);
        self::assertGreaterThanOrEqual($result->averageNanoseconds, $result->maximumNanoseconds);
        self::assertGreaterThanOrEqual(0.0, $result->operationsPerSecond());
    }

    public function testItRejectsInvalidIterationConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new BenchmarkRunner())->run('invalid', static fn (): null => null, iterations: 0);
    }
}
