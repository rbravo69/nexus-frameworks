<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;
use Nexus\Module\ModuleArchitecture;

final readonly class CodeGenerator
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    /** @param list<string> $dependencies */
    public function module(
        string $name,
        string $projectPath,
        ModuleArchitecture $architecture = ModuleArchitecture::Minimal,
        array $dependencies = [],
    ): string {
        return (new ModuleScaffolder($this->filesystem))->generate(
            $name,
            $projectPath,
            $architecture,
            $dependencies,
        );
    }

    public function controller(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Controller);
    }

    public function model(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Model);
    }

    public function service(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Service);
    }

    public function repository(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Repository);
    }

    public function middleware(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Middleware);
    }

    public function request(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Request);
    }

    public function event(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Event);
    }

    public function listener(string $name, string $projectPath): string
    {
        return $this->artifact($name, $projectPath, GeneratorType::Listener);
    }

    private function artifact(string $name, string $projectPath, GeneratorType $type): string
    {
        [$directory, $namespace, $suffix] = match ($type) {
            GeneratorType::Controller => ['src/Controller', 'App\\Controller', 'Controller'],
            GeneratorType::Model => ['src/Model', 'App\\Model', ''],
            GeneratorType::Service => ['src/Service', 'App\\Service', 'Service'],
            GeneratorType::Repository => ['src/Repository', 'App\\Repository', 'Repository'],
            GeneratorType::Middleware => ['src/Http/Middleware', 'App\\Http\\Middleware', 'Middleware'],
            GeneratorType::Request => ['src/Http/Request', 'App\\Http\\Request', 'Request'],
            GeneratorType::Event => ['src/Event', 'App\\Event', 'Event'],
            GeneratorType::Listener => ['src/Event/Listener', 'App\\Event\\Listener', 'Listener'],
            GeneratorType::Module => throw new InvalidInputException('Modules use the module scaffolder.'),
        };

        $class = $this->className($name, $suffix);
        $path = sprintf('%s/%s.php', $directory, $class);

        return $this->write(
            $projectPath,
            $path,
            $this->artifactContent($class, $namespace, $type),
        );
    }

    private function artifactContent(string $class, string $namespace, GeneratorType $type): string
    {
        if ($type === GeneratorType::Middleware) {
            return str_replace(
                ['{{ namespace }}', '{{ class }}'],
                [$namespace, $class],
                <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\RequestHandlerInterface;
use Nexus\Http\Response;

final class {{ class }} implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request);
    }
}
PHP
            ) . PHP_EOL;
        }

        if ($type === GeneratorType::Listener) {
            return str_replace(
                ['{{ namespace }}', '{{ class }}'],
                [$namespace, $class],
                <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

final class {{ class }}
{
    public function __invoke(object $event): void
    {
    }
}
PHP
            ) . PHP_EOL;
        }

        return str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $class],
            <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ namespace }};

final class {{ class }}
{
}
PHP
        ) . PHP_EOL;
    }

    private function className(string $name, string $suffix = ''): string
    {
        $name = trim($name);

        if ($name === '' || preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $name) !== 1) {
            throw new InvalidInputException('Generated names use letters, numbers, dashes and underscores.');
        }

        $class = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));

        if ($suffix !== '' && !str_ends_with($class, $suffix)) {
            $class .= $suffix;
        }

        return $class;
    }

    private function write(string $projectPath, string $relativePath, string $content): string
    {
        $path = rtrim($projectPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->filesystem->write($path, $content);

        return $path;
    }
}
