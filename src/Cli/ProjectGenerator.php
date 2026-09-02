<?php

declare(strict_types=1);

namespace Nexus\Cli;

use JsonException;
use Nexus\Exception\InvalidInputException;

final readonly class ProjectGenerator
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    /** @throws JsonException */
    public function generate(string $name, ProjectType $type, string $workingDirectory): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidInputException('The project name is required.');
        }

        $target = $this->targetPath($name, $workingDirectory);
        $projectName = basename($target);

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $projectName) !== 1) {
            throw new InvalidInputException('Project names may contain letters, numbers, dots, dashes and underscores.');
        }

        if (file_exists($target) && !$this->filesystem->isEmptyDirectory($target)) {
            throw new InvalidInputException(sprintf('Target directory "%s" is not empty.', $target));
        }

        $this->filesystem->ensureDirectory($target);

        foreach ($this->files($projectName, $type) as $path => $content) {
            $this->filesystem->write(
                $target . DIRECTORY_SEPARATOR . $path,
                $content,
                $path === 'bin/app',
            );
        }

        return $target;
    }

    private function targetPath(string $name, string $workingDirectory): string
    {
        $absolute = str_starts_with($name, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $name) === 1;

        $target = rtrim(
            $absolute ? $name : $workingDirectory . DIRECTORY_SEPARATOR . $name,
            '/\\',
        );

        return $target === '' ? DIRECTORY_SEPARATOR : $target;
    }

    /** @return array<string, string>
     *  @throws JsonException
     */
    private function files(string $projectName, ProjectType $type): array
    {
        $packageName = strtolower(str_replace('_', '-', $projectName));
        $composer = json_encode([
            'name' => sprintf('app/%s', $packageName),
            'description' => sprintf('%s built with Nexus Framework.', $type->label()),
            'type' => 'project',
            'require' => [
                'php' => '^8.4',
                'nexus/framework' => 'dev-main',
            ],
            'repositories' => [[
                'type' => 'vcs',
                'url' => 'https://github.com/rbravo69/nexus-frameworks',
            ]],
            'autoload' => [
                'psr-4' => ['App\\' => 'src/'],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $manifest = json_encode([
            'schema' => 1,
            'project' => [
                'name' => $projectName,
                'type' => $type->value,
            ],
            'capabilities' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $moduleName = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $projectName) ?? $projectName);

        return [
            '.gitignore' => "/vendor/\n/.nexus/cache/\n.env\n",
            'composer.json' => $composer . PHP_EOL,
            'nexus.json' => $manifest . PHP_EOL,
            'config/app.php' => sprintf(
                "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'name' => '%s',\n    'type' => '%s',\n];\n",
                addslashes($projectName),
                $type->value,
            ),
            'src/AppModule.php' => str_replace('{{ module }}', $moduleName, <<<'PHP'
<?php

declare(strict_types=1);

namespace App;

use Nexus\Application;
use Nexus\Contracts\ArchitecturalModuleInterface;
use Nexus\Module\ModuleArchitecture;

final class AppModule implements ArchitecturalModuleInterface
{
    public function name(): string
    {
        return '{{ module }}';
    }

    public function architecture(): ModuleArchitecture
    {
        return ModuleArchitecture::Minimal;
    }

    public function dependencies(): array
    {
        return [];
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
            ) . PHP_EOL,
            'bootstrap.php' => <<<'PHP'
<?php

declare(strict_types=1);

use App\AppModule;
use Nexus\Bootstrap;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/AppModule.php';

$application = Bootstrap::create(__DIR__);
$application->modules()->add(new AppModule());

return $application;
PHP
            . PHP_EOL,
            'bin/app' => <<<'PHP'
#!/usr/bin/env php
<?php

declare(strict_types=1);

use Nexus\Application;

/** @var Application $application */
$application = require dirname(__DIR__) . '/bootstrap.php';
$application->boot();

fwrite(STDOUT, "Nexus application booted successfully.\n");

$application->shutdown();
PHP
            . PHP_EOL,
            'README.md' => sprintf(
                "# %s\n\n%s generated with Nexus Framework.\n\n```bash\ncomposer install\nphp bin/app\n```\n",
                $projectName,
                $type->label(),
            ),
        ];
    }
}
