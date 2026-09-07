<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\CodeGenerator;
use Nexus\Cli\Filesystem;
use Nexus\Exception\CliException;
use Nexus\Module\ModuleArchitecture;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class CodeGeneratorTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testItGeneratesDeveloperArtifacts(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());

        $artifacts = [
            $generator->module('Booking', $this->temporaryDirectory->path()),
            $generator->controller('Checkout', $this->temporaryDirectory->path()),
            $generator->model('CustomerProfile', $this->temporaryDirectory->path()),
            $generator->service('Checkout', $this->temporaryDirectory->path()),
            $generator->repository('Booking', $this->temporaryDirectory->path()),
            $generator->middleware('Authenticate', $this->temporaryDirectory->path()),
            $generator->request('CreateBooking', $this->temporaryDirectory->path()),
            $generator->event('BookingCreated', $this->temporaryDirectory->path()),
            $generator->listener('SendBookingConfirmation', $this->temporaryDirectory->path()),
        ];

        foreach ($artifacts as $artifact) {
            self::assertFileExists($artifact);
        }

        self::assertStringContainsString('class BookingModule', (string) file_get_contents($artifacts[0]));
        self::assertStringContainsString('class CheckoutController', (string) file_get_contents($artifacts[1]));
        self::assertStringContainsString('class CustomerProfile', (string) file_get_contents($artifacts[2]));
        self::assertStringContainsString('class CheckoutService', (string) file_get_contents($artifacts[3]));
        self::assertStringContainsString('class BookingRepository', (string) file_get_contents($artifacts[4]));
        self::assertStringContainsString('implements MiddlewareInterface', (string) file_get_contents($artifacts[5]));
        self::assertStringContainsString('class CreateBookingRequest', (string) file_get_contents($artifacts[6]));
        self::assertStringContainsString('class BookingCreatedEvent', (string) file_get_contents($artifacts[7]));
        self::assertStringContainsString('class SendBookingConfirmationListener', (string) file_get_contents($artifacts[8]));
    }

    public function testItNeverOverwritesGeneratedFiles(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());
        $generator->model('Customer', $this->temporaryDirectory->path());

        $this->expectException(CliException::class);

        $generator->model('Customer', $this->temporaryDirectory->path());
    }

    public function testEveryModuleArchitectureCreatesOnlyDirectoriesWithRealFiles(): void
    {
        $temporaryDirectory = new TemporaryDirectory();
        $this->temporaryDirectory = $temporaryDirectory;
        $generator = new CodeGenerator(new Filesystem());

        foreach (ModuleArchitecture::cases() as $architecture) {
            $name = 'Sales' . $architecture->name;
            $generator->module(
                $name,
                $temporaryDirectory->path(),
                $architecture,
                ['identity'],
            );
            $root = $temporaryDirectory->path('src/' . $name);
            $manifest = json_decode(
                (string) file_get_contents($root . '/module.json'),
                true,
                flags: JSON_THROW_ON_ERROR,
            );

            self::assertIsArray($manifest);
            self::assertSame($architecture->value, $manifest['architecture'] ?? null);
            self::assertSame(['identity'], $manifest['dependencies'] ?? null);
            $this->assertNoEmptyDirectories($root);
        }
    }

    public function testHexagonalScaffoldWiresApplicationToPortAndAdapterToPort(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());
        $generator->module('Orders', $this->temporaryDirectory->path(), ModuleArchitecture::Hexagonal);

        $service = (string) file_get_contents($this->temporaryDirectory->path('src/Orders/Application/OrdersService.php'));
        $adapter = (string) file_get_contents($this->temporaryDirectory->path('src/Orders/Adapter/InMemoryOrdersRepository.php'));

        self::assertStringContainsString('use App\\Orders\\Port\\OrdersRepository;', $service);
        self::assertStringContainsString('private OrdersRepository $repository', $service);
        self::assertStringContainsString('use App\\Orders\\Port\\OrdersRepository;', $adapter);
        self::assertStringContainsString('implements OrdersRepository', $adapter);
    }

    public function testDddScaffoldWiresApplicationAndInfrastructureThroughDomainContract(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());
        $generator->module('Payments', $this->temporaryDirectory->path(), ModuleArchitecture::Ddd);

        $service = (string) file_get_contents($this->temporaryDirectory->path('src/Payments/Application/PaymentsApplicationService.php'));
        $repository = (string) file_get_contents($this->temporaryDirectory->path('src/Payments/Infrastructure/InMemoryPaymentsRepository.php'));

        self::assertStringContainsString('use App\\Payments\\Domain\\PaymentsRepository;', $service);
        self::assertStringContainsString('private PaymentsRepository $repository', $service);
        self::assertStringContainsString('use App\\Payments\\Domain\\PaymentsRepository;', $repository);
        self::assertStringContainsString('implements PaymentsRepository', $repository);
    }

    public function testModuleGenerationIsAtomicWhenAnyTargetAlreadyExists(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $root = $this->temporaryDirectory->path('src/Booking');
        mkdir($root, 0777, true);
        file_put_contents($root . '/module.json', '{}');
        $generator = new CodeGenerator(new Filesystem());

        try {
            $generator->module('Booking', $this->temporaryDirectory->path(), ModuleArchitecture::Hexagonal);
            self::fail('Expected an existing-file conflict.');
        } catch (CliException) {
            self::assertFileDoesNotExist($root . '/BookingModule.php');
            self::assertSame(['module.json'], array_values(array_diff(scandir($root) ?: [], ['.', '..'])));
        }
    }

    public function testModuleRejectsSelfDependencies(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();

        $this->expectException(\Nexus\Exception\InvalidInputException::class);

        (new CodeGenerator(new Filesystem()))->module(
            'Booking',
            $this->temporaryDirectory->path(),
            dependencies: ['booking'],
        );
    }

    private function assertNoEmptyDirectories(string $root): void
    {
        $directories = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($directories as $item) {
            /** @var \SplFileInfo $item */
            if (!$item->isDir()) {
                continue;
            }

            self::assertNotSame([], array_values(array_diff(scandir($item->getPathname()) ?: [], ['.', '..'])));
        }
    }
}
