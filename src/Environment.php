<?php

declare(strict_types=1);

namespace Nexus;

use InvalidArgumentException;

final readonly class Environment
{
    public string $basePath;

    public function __construct(
        string $basePath,
        public string $name = 'production',
        public bool $debug = false,
    ) {
        if ($basePath === '') {
            throw new InvalidArgumentException('The application base path cannot be empty.');
        }

        if ($name === '') {
            throw new InvalidArgumentException('The environment name cannot be empty.');
        }

        $resolvedPath = realpath($basePath);
        $normalizedPath = rtrim(
            $resolvedPath === false ? $basePath : $resolvedPath,
            '/\\',
        );

        $this->basePath = $normalizedPath === '' ? DIRECTORY_SEPARATOR : $normalizedPath;
    }

    public function is(string ...$names): bool
    {
        return in_array($this->name, $names, true);
    }

    public function path(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}
