<?php

declare(strict_types=1);

namespace Nexus\Architecture;

use JsonException;
use Nexus\Module\ModuleArchitecture;

final class ArchitectureGuard
{
    /** @return list<string> */
    public function inspect(string $projectPath): array
    {
        $root = rtrim($projectPath, '/\\') . DIRECTORY_SEPARATOR . 'src';

        if (!is_dir($root)) {
            return ['src directory was not found.'];
        }

        $manifests = $this->manifests($root);
        $errors = [];
        $modules = [];

        foreach ($manifests as $path) {
            try {
                $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $errors[] = sprintf('%s contains invalid JSON: %s', $this->relative($projectPath, $path), $exception->getMessage());
                continue;
            }

            if (!is_array($data) || array_is_list($data)) {
                $errors[] = sprintf('%s must contain a JSON object.', $this->relative($projectPath, $path));
                continue;
            }

            $name = $data['name'] ?? null;
            $architecture = $data['architecture'] ?? null;
            $dependencies = $data['dependencies'] ?? null;

            if (!is_string($name) || preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1) {
                $errors[] = sprintf('%s has an invalid module name.', $this->relative($projectPath, $path));
                continue;
            }

            if (isset($modules[$name])) {
                $errors[] = sprintf('Duplicate module name "%s".', $name);
                continue;
            }

            if (!is_string($architecture) || ModuleArchitecture::tryFrom($architecture) === null) {
                $errors[] = sprintf('Module "%s" has an invalid architecture.', $name);
            }

            if (!is_array($dependencies) || !array_is_list($dependencies)) {
                $errors[] = sprintf('Module "%s" dependencies must be a list.', $name);
                $dependencies = [];
            }

            $normalized = [];

            foreach ($dependencies as $dependency) {
                if (!is_string($dependency) || preg_match('/^[a-z][a-z0-9-]*$/', $dependency) !== 1) {
                    $errors[] = sprintf('Module "%s" contains an invalid dependency.', $name);
                    continue;
                }

                if ($dependency === $name) {
                    $errors[] = sprintf('Module "%s" cannot depend on itself.', $name);
                    continue;
                }

                $normalized[] = $dependency;
            }

            $modules[$name] = array_values(array_unique($normalized));
        }

        foreach ($modules as $name => $dependencies) {
            foreach ($dependencies as $dependency) {
                if (!isset($modules[$dependency])) {
                    $errors[] = sprintf('Module "%s" depends on unknown module "%s".', $name, $dependency);
                }
            }
        }

        $errors = [...$errors, ...$this->cycles($modules)];

        return array_values(array_unique($errors));
    }

    /** @return list<string> */
    private function manifests(string $root): array
    {
        $paths = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo && $item->isFile() && $item->getFilename() === 'module.json') {
                $paths[] = $item->getPathname();
            }
        }

        sort($paths, SORT_STRING);

        return $paths;
    }

    /**
     * @param array<string, list<string>> $modules
     * @return list<string>
     */
    private function cycles(array $modules): array
    {
        $visiting = [];
        $visited = [];
        $errors = [];

        foreach (array_keys($modules) as $name) {
            $this->visit($name, $modules, $visiting, $visited, $errors);
        }

        return $errors;
    }

    /**
     * @param array<string, list<string>> $modules
     * @param list<string> $visiting
     * @param array<string, true> $visited
     * @param list<string> $errors
     */
    private function visit(string $name, array $modules, array &$visiting, array &$visited, array &$errors): void
    {
        if (isset($visited[$name])) {
            return;
        }

        $position = array_search($name, $visiting, true);

        if ($position !== false) {
            $path = [...array_slice($visiting, $position), $name];
            $errors[] = 'Circular module dependency: ' . implode(' -> ', $path) . '.';
            return;
        }

        $visiting[] = $name;

        foreach ($modules[$name] ?? [] as $dependency) {
            if (isset($modules[$dependency])) {
                $this->visit($dependency, $modules, $visiting, $visited, $errors);
            }
        }

        array_pop($visiting);
        $visited[$name] = true;
    }

    private function relative(string $projectPath, string $path): string
    {
        $root = rtrim($projectPath, '/\\') . DIRECTORY_SEPARATOR;

        return str_replace(DIRECTORY_SEPARATOR, '/', str_starts_with($path, $root) ? substr($path, strlen($root)) : $path);
    }
}
