<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\Input;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function testItParsesArgumentsFlagsAndOptionValues(): void
    {
        $input = new Input([
            'shop',
            '--type=api',
            '--port',
            '8080',
            '--no-interaction',
        ]);

        self::assertSame('shop', $input->argument(0));
        self::assertSame('api', $input->option('type'));
        self::assertSame('8080', $input->option('port'));
        self::assertTrue($input->hasOption('no-interaction'));
        self::assertNull($input->option('missing'));
    }
}
