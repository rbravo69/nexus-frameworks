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
        $directories = ['Configuration', 'Container', 'Contracts', 'Lifecycle', 'Module'];
        $forbidden = ['Nexus\\Docker\\', 'Nexus\\Database\\Eloquent\\'];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory));

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                self::assertIsString($content);

                foreach ($forbidden as $namespace) {
                    self::assertStringNotContainsString(
                        $namespace,
                        $content,
                        sprintf('%s must not depend on optional namespace %s.', $file->getPathname(), $namespace),
                    );
                }
            }
        }
    }
}
