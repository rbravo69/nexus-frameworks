<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\CodeGenerator;
use Nexus\Cli\Filesystem;
use Nexus\Exception\CliException;
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
}
