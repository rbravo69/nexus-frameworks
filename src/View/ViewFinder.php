<?php

declare(strict_types=1);

namespace Nexus\View;

use InvalidArgumentException;
use RuntimeException;

final class ViewFinder
{
    /** @var list<string> */
    private array $paths = [];

    /** @var array<string, list<string>> */
    private array $namespaces = [];

    /** @param list<string> $paths */
    public function __construct(array $paths = [])
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
    }

    public function addPath(string $path): self
    {
        $path = rtrim($path, '/\\');

        if ($path === '') {
            throw new InvalidArgumentException('View path cannot be empty.');
        }

        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    public function addNamespace(string $namespace, string $path): self
    {
        $namespace = trim($namespace);
        $path = rtrim($path, '/\\');

        if ($namespace === '' || $path === '') {
            throw new InvalidArgumentException('View namespace and path cannot be empty.');
        }

        $this->namespaces[$namespace] ??= [];

        if (!in_array($path, $this->namespaces[$namespace], true)) {
            $this->namespaces[$namespace][] = $path;
        }

        return $this;
    }

    /** @return list<string> */
    public function paths(): array
    {
        return $this->paths;
    }

    /** @return array<string, list<string>> */
    public function namespaces(): array
    {
        return $this->namespaces;
    }

    /** @param list<string> $extensions */
    public function find(string $view, array $extensions): string
    {
        [$paths, $name] = $this->resolveSearch($view);
        $relative = str_replace(['.', '\\'], '/', $name);
        $candidates = [];

        if (pathinfo($relative, PATHINFO_EXTENSION) !== '') {
            $candidates[] = $relative;
        } else {
            foreach ($extensions as $extension) {
                $candidates[] = $relative . '.' . ltrim($extension, '.');
            }
        }

        foreach ($paths as $basePath) {
            foreach ($candidates as $candidate) {
                $file = $basePath . DIRECTORY_SEPARATOR . $candidate;

                if (is_file($file)) {
                    return $file;
                }
            }
        }

        throw new RuntimeException(sprintf('View "%s" was not found.', $view));
    }

    /** @return array{0: list<string>, 1: string} */
    private function resolveSearch(string $view): array
    {
        $view = trim($view);

        if ($view === '') {
            throw new InvalidArgumentException('View name cannot be empty.');
        }

        if (!str_contains($view, '::')) {
            return [$this->paths, $view];
        }

        [$namespace, $name] = explode('::', $view, 2);
        $paths = $this->namespaces[$namespace] ?? null;

        if ($paths === null) {
            throw new RuntimeException(sprintf('View namespace "%s" is not registered.', $namespace));
        }

        return [$paths, $name];
    }
}
