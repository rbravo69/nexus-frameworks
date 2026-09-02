<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\CliException;

final class Filesystem
{
    public function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new CliException(sprintf('Unable to create directory "%s".', $path));
        }
    }

    public function write(string $path, string $content, bool $executable = false): void
    {
        $this->ensureDirectory(dirname($path));

        if (file_exists($path)) {
            throw new CliException(sprintf('File "%s" already exists.', $path));
        }

        if (file_put_contents($path, $content) === false) {
            throw new CliException(sprintf('Unable to write file "%s".', $path));
        }

        if ($executable && !chmod($path, 0755)) {
            throw new CliException(sprintf('Unable to make file "%s" executable.', $path));
        }
    }

    public function isEmptyDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $items = scandir($path);

        return $items !== false && count($items) === 2;
    }
}
