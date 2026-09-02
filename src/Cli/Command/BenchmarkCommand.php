<?php

declare(strict_types=1);

namespace Nexus\Cli\Command;

use Nexus\Benchmark\BenchmarkRunner;
use Nexus\Cli\CommandInterface;
use Nexus\Cli\ExitCode;
use Nexus\Cli\Input;
use Nexus\Cli\OutputInterface;
use Nexus\Container\Container;

final readonly class BenchmarkCommand implements CommandInterface
{
    public function __construct(private BenchmarkRunner $runner = new BenchmarkRunner())
    {
    }

    public function name(): string
    {
        return 'benchmark';
    }

    public function description(): string
    {
        return 'Run lightweight Nexus microbenchmarks.';
    }

    public function usage(): string
    {
        return 'nexus benchmark [--iterations=1000] [--warmup=50]';
    }

    public function execute(Input $input, OutputInterface $output): int
    {
        $iterations = $this->positiveInteger($input->option('iterations', '1000'), 'iterations');
        $warmup = $this->nonNegativeInteger($input->option('warmup', '50'), 'warmup');
        $container = new Container();
        $service = new \stdClass();
        $container->instance('benchmark.service', $service);

        $results = [
            $this->runner->run('php.noop', static fn (): null => null, $iterations, $warmup),
            $this->runner->run('container.get', static fn (): mixed => $container->get('benchmark.service'), $iterations, $warmup),
        ];

        foreach ($results as $result) {
            $output->writeln(sprintf(
                '%s: %.0f ns/op | %.0f ops/s | min %.0f | max %.0f | %d iterations',
                $result->name,
                $result->averageNanoseconds,
                $result->operationsPerSecond(),
                $result->minimumNanoseconds,
                $result->maximumNanoseconds,
                $result->iterations,
            ));
        }

        return ExitCode::Success;
    }

    private function positiveInteger(?string $value, string $name): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if (!is_int($integer) || $integer < 1) {
            throw new \InvalidArgumentException(sprintf('--%s must be a positive integer.', $name));
        }

        return $integer;
    }

    private function nonNegativeInteger(?string $value, string $name): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if (!is_int($integer) || $integer < 0) {
            throw new \InvalidArgumentException(sprintf('--%s must be a non-negative integer.', $name));
        }

        return $integer;
    }
}
