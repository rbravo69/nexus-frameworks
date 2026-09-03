<?php

declare(strict_types=1);

namespace Nexus\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FoundationDependencyTest extends TestCase
{
    public function testFoundationDoesNotDependOnOptionalInfrastructure(): void
    {
        $root = dirname(__DIR__, 2) . '/src';

        $this->assertDirectoriesDoNotDependOn(
            $root,
            ['Configuration', 'Container', 'Contracts', 'Lifecycle', 'Module'],
            ['Nexus\\Docker\\', 'Nexus\\Database\\Eloquent\\'],
        );
    }

    public function testCoreAndCapabilitiesDoNotDependOnCli(): void
    {
        $root = dirname(__DIR__, 2) . '/src';
        $this->assertFileDoesNotDependOn($root . '/Bootstrap.php', ['Nexus\\Cli\\']);
        $this->assertDirectoriesDoNotDependOn($root, ['Capability'], ['Nexus\\Cli\\']);
    }

    public function testHttpDoesNotDependOnRestOrValidation(): void
    {
        $root = dirname(__DIR__, 2) . '/src';
        $this->assertDirectoriesDoNotDependOn(
            $root,
            ['Http'],
            ['Nexus\\Rest\\', 'Nexus\\Validation\\'],
        );
    }

    /**
     * @param list<string> $directories
     * @param list<string> $forbidden
     */
    private function assertDirectoriesDoNotDependOn(string $root, array $directories, array $forbidden): void
    {
        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory));

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $this->assertFileDoesNotDependOn($file->getPathname(), $forbidden);
            }
        }
    }

    /** @param list<string> $forbidden */
    private function assertFileDoesNotDependOn(string $path, array $forbidden): void
    {
        $content = file_get_contents($path);
        self::assertIsString($content);

        foreach ($forbidden as $namespace) {
            self::assertStringNotContainsString(
                $namespace,
                $content,
                sprintf('%s must not depend on %s.', $path, $namespace),
            );
        }
    }
}
