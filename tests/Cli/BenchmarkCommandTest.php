<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\BufferedOutput;
use Nexus\Cli\CliFactory;
use Nexus\Cli\ExitCode;
use PHPUnit\Framework\TestCase;

final class BenchmarkCommandTest extends TestCase
{
    public function testBenchmarkCommandReportsBuiltInBenchmarks(): void
    {
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(output: $output);

        $exitCode = $cli->run([
            'nexus',
            'benchmark',
            '--iterations=5',
            '--warmup=1',
        ]);

        self::assertSame(ExitCode::Success, $exitCode);
        self::assertStringContainsString('php.noop:', $output->content());
        self::assertStringContainsString('container.get:', $output->content());
        self::assertStringContainsString('5 iterations', $output->content());
    }
}
