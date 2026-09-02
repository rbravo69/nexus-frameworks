<?php

declare(strict_types=1);

namespace Nexus\Cli;

use JsonException;
use Nexus\Exception\CliException;
use Nexus\Exception\InvalidInputException;
use Nexus\Module\ModuleArchitecture;

final readonly class ModuleScaffolder
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    /**
     * @param list<string> $dependencies
     * @throws JsonException
     */
    public function generate(
        string $name,
        string $projectPath,
        ModuleArchitecture $architecture,
        array $dependencies = [],
    ): string {
        $class = $this->className($name);
        $moduleName = $this->moduleName($class);
        $dependencies = $this->dependencies($dependencies, $moduleName);
        $root = sprintf('src/%s', $class);
        $files = [
            sprintf('%s/%sModule.php', $root, $class) => $this->moduleFile(
                $class,
                $moduleName,
                $architecture,
                $dependencies,
            ),
            $root . '/module.json' => $this->manifest($moduleName, $architecture, $dependencies),
            ...$this->presetFiles($root, $class, $architecture),
        ];

        foreach (array_keys($files) as $relativePath) {
            $path = $this->absolutePath($projectPath, $relativePath);

            if (file_exists($path)) {
                throw new CliException(sprintf('File "%s" already exists.', $path));
            }
        }

        foreach ($files as $relativePath => $content) {
            $this->filesystem->write($this->absolutePath($projectPath, $relativePath), $content);
        }

        return $this->absolutePath($projectPath, sprintf('%s/%sModule.php', $root, $class));
    }

    private function className(string $name): string
    {
        $name = trim($name);

        if ($name === '' || preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $name) !== 1) {
            throw new InvalidInputException('Generated names use letters, numbers, dashes and underscores.');
        }

        $class = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));

        return str_ends_with($class, 'Module')
            ? substr($class, 0, -strlen('Module'))
            : $class;
    }

    private function moduleName(string $class): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $class) ?? $class);
    }

    /**
     * @param list<string> $dependencies
     * @return list<string>
     */
    private function dependencies(array $dependencies, string $moduleName): array
    {
        $normalized = [];

        foreach ($dependencies as $dependency) {
            $dependency = strtolower(trim($dependency));

            if (preg_match('/^[a-z][a-z0-9-]*$/', $dependency) !== 1) {
                throw new InvalidInputException(sprintf('Invalid module dependency "%s".', $dependency));
            }

            if ($dependency === $moduleName) {
                throw new InvalidInputException('A module cannot depend on itself.');
            }

            $normalized[] = $dependency;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized, SORT_STRING);

        return $normalized;
    }

    /** @param list<string> $dependencies */
    private function moduleFile(
        string $class,
        string $moduleName,
        ModuleArchitecture $architecture,
        array $dependencies,
    ): string {
        $dependencyList = '[' . implode(', ', array_map(
            static fn (string $dependency): string => sprintf("'%s'", $dependency),
            $dependencies,
        )) . ']';

        return str_replace(
            ['{{ class }}', '{{ name }}', '{{ architecture }}', '{{ dependencies }}'],
            [$class, $moduleName, $architecture->name, $dependencyList],
            <<<'PHP'
<?php

declare(strict_types=1);

namespace App\{{ class }};

use Nexus\Application;
use Nexus\Contracts\ArchitecturalModuleInterface;
use Nexus\Module\ModuleArchitecture;

final class {{ class }}Module implements ArchitecturalModuleInterface
{
    public function name(): string
    {
        return '{{ name }}';
    }

    public function architecture(): ModuleArchitecture
    {
        return ModuleArchitecture::{{ architecture }};
    }

    public function dependencies(): array
    {
        return {{ dependencies }};
    }

    public function register(Application $application): void
    {
    }

    public function boot(Application $application): void
    {
    }

    public function shutdown(Application $application): void
    {
    }
}
PHP
        ) . PHP_EOL;
    }

    /**
     * @param list<string> $dependencies
     * @throws JsonException
     */
    private function manifest(
        string $moduleName,
        ModuleArchitecture $architecture,
        array $dependencies,
    ): string {
        return json_encode([
            'schema' => 1,
            'name' => $moduleName,
            'architecture' => $architecture->value,
            'dependencies' => $dependencies,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    }

    /** @return array<string, string> */
    private function presetFiles(string $root, string $class, ModuleArchitecture $architecture): array
    {
        $namespace = 'App\\' . $class;

        return match ($architecture) {
            ModuleArchitecture::Minimal, ModuleArchitecture::Custom => [],
            ModuleArchitecture::Mvc => [
                $root . '/Controller/' . $class . 'Controller.php' => $this->classFile($namespace . '\\Controller', $class . 'Controller'),
                $root . '/Model/' . $class . '.php' => $this->classFile($namespace . '\\Model', $class, true),
                $root . '/View/index.php' => sprintf("<h1>%s</h1>\n", $class),
            ],
            ModuleArchitecture::Layered => [
                $root . '/Domain/' . $class . '.php' => $this->classFile($namespace . '\\Domain', $class, true),
                $root . '/Application/' . $class . 'Service.php' => $this->classFile($namespace . '\\Application', $class . 'Service'),
                $root . '/Infrastructure/' . $class . 'Repository.php' => $this->classFile($namespace . '\\Infrastructure', $class . 'Repository'),
            ],
            ModuleArchitecture::Modular => [
                $root . '/Contract/' . $class . 'Feature.php' => $this->interfaceFile($namespace . '\\Contract', $class . 'Feature'),
                $root . '/Internal/' . $class . 'Service.php' => $this->classFile($namespace . '\\Internal', $class . 'Service'),
            ],
            ModuleArchitecture::Hexagonal => [
                $root . '/Domain/' . $class . '.php' => $this->classFile($namespace . '\\Domain', $class, true),
                $root . '/Application/' . $class . 'Service.php' => $this->classFile($namespace . '\\Application', $class . 'Service'),
                $root . '/Port/' . $class . 'Repository.php' => $this->interfaceFile($namespace . '\\Port', $class . 'Repository'),
                $root . '/Adapter/InMemory' . $class . 'Repository.php' => $this->classFile($namespace . '\\Adapter', 'InMemory' . $class . 'Repository'),
            ],
            ModuleArchitecture::Clean => [
                $root . '/Entity/' . $class . '.php' => $this->classFile($namespace . '\\Entity', $class, true),
                $root . '/UseCase/Create' . $class . '.php' => $this->classFile($namespace . '\\UseCase', 'Create' . $class),
                $root . '/InterfaceAdapter/' . $class . 'Controller.php' => $this->classFile($namespace . '\\InterfaceAdapter', $class . 'Controller'),
                $root . '/Framework/InMemory' . $class . 'Repository.php' => $this->classFile($namespace . '\\Framework', 'InMemory' . $class . 'Repository'),
            ],
            ModuleArchitecture::Ddd => [
                $root . '/Domain/' . $class . 'Aggregate.php' => $this->classFile($namespace . '\\Domain', $class . 'Aggregate'),
                $root . '/Domain/' . $class . 'Repository.php' => $this->interfaceFile($namespace . '\\Domain', $class . 'Repository'),
                $root . '/Application/' . $class . 'ApplicationService.php' => $this->classFile($namespace . '\\Application', $class . 'ApplicationService'),
                $root . '/Infrastructure/InMemory' . $class . 'Repository.php' => $this->classFile($namespace . '\\Infrastructure', 'InMemory' . $class . 'Repository'),
            ],
            ModuleArchitecture::Cqrs => [
                $root . '/Application/Command/Create' . $class . '.php' => $this->classFile($namespace . '\\Application\\Command', 'Create' . $class, true),
                $root . '/Application/Command/Create' . $class . 'Handler.php' => $this->classFile($namespace . '\\Application\\Command', 'Create' . $class . 'Handler'),
                $root . '/Application/Query/Get' . $class . '.php' => $this->classFile($namespace . '\\Application\\Query', 'Get' . $class, true),
                $root . '/Application/Query/Get' . $class . 'Handler.php' => $this->classFile($namespace . '\\Application\\Query', 'Get' . $class . 'Handler'),
            ],
        };
    }

    private function classFile(string $namespace, string $class, bool $readonly = false): string
    {
        return sprintf(
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace %s;\n\nfinal %sclass %s\n{\n}\n",
            $namespace,
            $readonly ? 'readonly ' : '',
            $class,
        );
    }

    private function interfaceFile(string $namespace, string $interface): string
    {
        return sprintf(
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace %s;\n\ninterface %s\n{\n}\n",
            $namespace,
            $interface,
        );
    }

    private function absolutePath(string $projectPath, string $relativePath): string
    {
        return rtrim($projectPath, '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }
}
