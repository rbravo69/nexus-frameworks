<?php

declare(strict_types=1);

namespace Nexus\Tests\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class TemporaryDirectory
{
    private string $path;

    public function __construct()
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nexus-test-' . bin2hex(random_bytes(8));

        if (!mkdir($this->path, 0777, true) && !is_dir($this->path)) {
            throw new \RuntimeException('Unable to create test directory.');
        }
    }

    public function path(string $relative = ''): string
    {
        return $relative === ''
            ? $this->path
            : $this->path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    public function remove(): void
    {
        if (!is_dir($this->path) || !str_starts_with(basename($this->path), 'nexus-test-')) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $item->isLink() || $item->isFile()
                ? unlink($item->getPathname())
                : rmdir($item->getPathname());
        }

        rmdir($this->path);
    }
}
