<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\BufferedOutput;
use Nexus\Cli\CliFactory;
use Nexus\Cli\ExitCode;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class DeveloperToolingCommandTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testListExposesExpandedMakeCommands(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        self::assertSame(ExitCode::Success, $cli->run(['nexus', 'list']));

        foreach ([
            'make:service',
            'make:repository',
            'make:middleware',
            'make:request',
            'make:event',
            'make:listener',
        ] as $command) {
            self::assertStringContainsString($command, $output->content());
        }
    }

    public function testExpandedMakeCommandsCreateExpectedFiles(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $output = new BufferedOutput();
        $cli = (new CliFactory())->create(
            output: $output,
            workingDirectory: $this->temporaryDirectory->path(),
        );

        $commands = [
            ['make:service', 'Checkout', 'src/Service/CheckoutService.php'],
            ['make:repository', 'Booking', 'src/Repository/BookingRepository.php'],
            ['make:middleware', 'Authenticate', 'src/Http/Middleware/AuthenticateMiddleware.php'],
            ['make:request', 'CreateBooking', 'src/Http/Request/CreateBookingRequest.php'],
            ['make:event', 'BookingCreated', 'src/Event/BookingCreatedEvent.php'],
            ['make:listener', 'SendBookingConfirmation', 'src/Event/Listener/SendBookingConfirmationListener.php'],
        ];

        foreach ($commands as [$command, $name, $path]) {
            self::assertSame(ExitCode::Success, $cli->run(['nexus', $command, $name]));
            self::assertFileExists($this->temporaryDirectory->path($path));
        }
    }
}
