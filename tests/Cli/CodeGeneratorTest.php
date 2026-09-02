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

    public function testItGeneratesModuleControllerAndModel(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $generator = new CodeGenerator(new Filesystem());

        $module = $generator->module('Booking', $this->temporaryDirectory->path());
        $controller = $generator->controller('Checkout', $this->temporaryDirectory->path());
        $model = $generator->model('CustomerProfile', $this->temporaryDirectory->path());

        self::assertFileExists($module);
        self::assertFileExists($controller);
        self::assertFileExists($model);
        self::assertStringContainsString('class BookingModule', (string) file_get_contents($module));
        self::assertStringContainsString('class CheckoutController', (string) file_get_contents($controller));
        self::assertStringContainsString('class CustomerProfile', (string) file_get_contents($model));
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
