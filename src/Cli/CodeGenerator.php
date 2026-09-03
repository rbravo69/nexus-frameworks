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
        $class = $this->className($name, 'Controller');
        $path = sprintf('src/Controller/%s.php', $class);
        $content = str_replace('{{ class }}', $class, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Controller;

final class {{ class }}
{
}
PHP
        ) . PHP_EOL;

        return $this->write($projectPath, $path, $content);
    }

    public function model(string $name, string $projectPath): string
    {
        $class = $this->className($name);
        $path = sprintf('src/Model/%s.php', $class);
        $content = str_replace('{{ class }}', $class, <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Model;

final class {{ class }}
{
}
PHP
        ) . PHP_EOL;

        return $this->write($projectPath, $path, $content);
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
