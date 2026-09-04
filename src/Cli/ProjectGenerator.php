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
    public function generate(
        string $name,
        ProjectType $type,
        string $workingDirectory,
        ?FrontendSelection $frontend = null,
    ): string {
        $name = trim($name);
        $frontend ??= FrontendSelection::none();

        if ($name === '') {
            throw new InvalidInputException('The project name is required.');
        }

        if (!$type->supportsFrontend() && $frontend->renderer !== FrontendRenderer::None) {
            throw new InvalidInputException('Frontend stacks are available only for monolith project types.');
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

        foreach ($this->files($projectName, $type, $frontend) as $path => $content) {
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
    private function files(string $projectName, ProjectType $type, FrontendSelection $frontend): array
    {
        $packageName = strtolower(str_replace('_', '-', $projectName));
        $composerRequirements = [
            'php' => '^8.4',
            'nexus/framework' => 'dev-main',
        ];

        if ($frontend->renderer === FrontendRenderer::Twig) {
            $composerRequirements['twig/twig'] = '^3.0';
        }

        $composer = json_encode([
            'name' => sprintf('app/%s', $packageName),
            'description' => sprintf('%s built with Nexus Framework.', $type->label()),
            'type' => 'project',
            'require' => $composerRequirements,
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

        $project = [
            'name' => $projectName,
            'type' => $type->value,
        ];

        if ($type->supportsFrontend()) {
            $project['frontend'] = $frontend->toArray();
        }

        $manifest = json_encode([
            'schema' => 1,
            'project' => $project,
            'capabilities' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $moduleName = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $projectName) ?? $projectName);
        $files = [
            '.gitignore' => "/vendor/\n/node_modules/\n/public/build/\n/.nexus/cache/\n.env\n",
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
        ];

        $files += $this->frontendFiles($frontend);

        $install = "composer install\nphp bin/app";

        if (isset($files['package.json'])) {
            $install .= "\nnpm install\nnpm run build";
        }

        $files['README.md'] = sprintf(
            "# %s\n\n%s generated with Nexus Framework.\n\n```bash\n%s\n```\n",
            $projectName,
            $type->label(),
            $install,
        );

        return $files;
    }

    /** @return array<string, string>
     *  @throws JsonException
     */
    private function frontendFiles(FrontendSelection $frontend): array
    {
        if ($frontend->renderer === FrontendRenderer::None) {
            return [];
        }

        $files = [
            'config/frontend.php' => sprintf(
                "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'renderer' => '%s',\n    'interactivity' => '%s',\n    'css' => '%s',\n    'components' => '%s',\n];\n",
                $frontend->renderer->value,
                $frontend->interactivity->value,
                $frontend->css->value,
                $frontend->components->value,
            ),
        ];

        if ($frontend->renderer === FrontendRenderer::Twig) {
            $files['resources/views/layouts/app.twig'] = <<<'TWIG'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{% block title %}Nexus{% endblock %}</title>
</head>
<body>
{% include 'partials/navigation.twig' %}
{% block content %}{% endblock %}
</body>
</html>
TWIG
            . PHP_EOL;
            $files['resources/views/partials/navigation.twig'] = "<nav><a href=\"/\">Nexus</a></nav>\n";
            $files['resources/views/components/welcome.twig'] = "<main><h1>Nexus + Twig</h1></main>\n";
            $files['resources/views/home.twig'] = <<<'TWIG'
{% extends 'layouts/app.twig' %}

{% block title %}Home · Nexus{% endblock %}

{% block content %}
    {% include 'components/welcome.twig' %}
{% endblock %}
TWIG
            . PHP_EOL;
        } elseif ($frontend->renderer === FrontendRenderer::Php) {
            $files['resources/views/layouts/app.php'] = <<<'PHP'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) ($title ?? 'Nexus'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
</head>
<body>
<?php require dirname(__DIR__) . '/partials/navigation.php'; ?>
<?= $content ?? '' ?>
</body>
</html>
PHP
            . PHP_EOL;
            $files['resources/views/partials/navigation.php'] = "<nav><a href=\"/\">Nexus</a></nav>\n";
            $files['resources/views/components/welcome.php'] = "<main><h1>Nexus + PHP Native</h1></main>\n";
            $files['resources/views/home.php'] = <<<'PHP'
<?php
ob_start();
require __DIR__ . '/components/welcome.php';
$content = ob_get_clean();
$title = 'Home · Nexus';
require __DIR__ . '/layouts/app.php';
PHP
            . PHP_EOL;
        }

        $package = $this->packageJson($frontend);

        if ($package !== null) {
            $files['package.json'] = $package;
            $files += $this->assetFiles($frontend);
        }

        return $files;
    }

    /** @throws JsonException */
    private function packageJson(FrontendSelection $frontend): ?string
    {
        $dependencies = [];
        $devDependencies = [];

        match ($frontend->renderer) {
            FrontendRenderer::React => $dependencies += ['react' => 'latest', 'react-dom' => 'latest'],
            FrontendRenderer::Vue => $dependencies += ['vue' => 'latest'],
            FrontendRenderer::Svelte => $dependencies += ['svelte' => 'latest'],
            FrontendRenderer::Solid => $dependencies += ['solid-js' => 'latest'],
            default => null,
        };

        match ($frontend->interactivity) {
            FrontendInteractivity::Htmx => $dependencies += ['htmx.org' => 'latest'],
            FrontendInteractivity::Alpine => $dependencies += ['alpinejs' => 'latest'],
            FrontendInteractivity::HtmxAlpine => $dependencies += ['htmx.org' => 'latest', 'alpinejs' => 'latest'],
            FrontendInteractivity::None => null,
        };

        match ($frontend->css) {
            CssFramework::Tailwind => $devDependencies += ['tailwindcss' => 'latest', '@tailwindcss/vite' => 'latest'],
            CssFramework::Bootstrap => $dependencies += ['bootstrap' => 'latest'],
            CssFramework::Bulma => $dependencies += ['bulma' => 'latest'],
            CssFramework::None => null,
        };

        match ($frontend->components) {
            ComponentLibrary::DaisyUi => $devDependencies += ['daisyui' => 'latest'],
            ComponentLibrary::MaterialUi => $dependencies += [
                '@mui/material' => 'latest',
                '@emotion/react' => 'latest',
                '@emotion/styled' => 'latest',
            ],
            ComponentLibrary::None => null,
        };

        $needsAssets = $dependencies !== [] || $devDependencies !== [] || !$frontend->renderer->isServerRendered();

        if (!$needsAssets) {
            return null;
        }

        $devDependencies['vite'] = 'latest';

        match ($frontend->renderer) {
            FrontendRenderer::React => $devDependencies['@vitejs/plugin-react'] = 'latest',
            FrontendRenderer::Vue => $devDependencies['@vitejs/plugin-vue'] = 'latest',
            FrontendRenderer::Svelte => $devDependencies['@sveltejs/vite-plugin-svelte'] = 'latest',
            FrontendRenderer::Solid => $devDependencies['vite-plugin-solid'] = 'latest',
            default => null,
        };

        ksort($dependencies);
        ksort($devDependencies);

        return json_encode(array_filter([
            'private' => true,
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
            ],
            'dependencies' => $dependencies,
            'devDependencies' => $devDependencies,
        ], static fn (mixed $value): bool => $value !== []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    }

    /** @return array<string, string> */
    private function assetFiles(FrontendSelection $frontend): array
    {
        $files = [];
        $plugins = [];
        $imports = [];
        $inputs = [];

        if ($frontend->css === CssFramework::Tailwind) {
            $plugins[] = "import tailwindcss from '@tailwindcss/vite';";
            $imports[] = 'tailwindcss()';
            $css = '@import "tailwindcss";' . PHP_EOL;

            if ($frontend->components === ComponentLibrary::DaisyUi) {
                $css .= '@plugin "daisyui";' . PHP_EOL;
            }

            $files['resources/frontend/app.css'] = $css;
            $inputs[] = 'resources/frontend/app.css';
        } elseif ($frontend->css === CssFramework::Bulma) {
            $files['resources/frontend/app.css'] = '@import "bulma/css/bulma.css";' . PHP_EOL;
            $inputs[] = 'resources/frontend/app.css';
        }

        switch ($frontend->renderer) {
            case FrontendRenderer::React:
                $plugins[] = "import react from '@vitejs/plugin-react';";
                $imports[] = 'react()';
                $files['resources/frontend/main.jsx'] = <<<'JS'
import React from 'react';
import { createRoot } from 'react-dom/client';

function App() {
    return <h1>Nexus + React</h1>;
}

createRoot(document.getElementById('app')).render(<App />);
JS
                . PHP_EOL;
                $inputs[] = 'resources/frontend/main.jsx';
                break;

            case FrontendRenderer::Vue:
                $plugins[] = "import vue from '@vitejs/plugin-vue';";
                $imports[] = 'vue()';
                $files['resources/frontend/main.js'] = "import { createApp } from 'vue';\nimport App from './App.vue';\n\ncreateApp(App).mount('#app');\n";
                $files['resources/frontend/App.vue'] = "<template>\n  <h1>Nexus + Vue.js</h1>\n</template>\n";
                $inputs[] = 'resources/frontend/main.js';
                break;

            case FrontendRenderer::Svelte:
                $plugins[] = "import { svelte } from '@sveltejs/vite-plugin-svelte';";
                $imports[] = 'svelte()';
                $files['resources/frontend/main.js'] = "import { mount } from 'svelte';\nimport App from './App.svelte';\n\nmount(App, { target: document.getElementById('app') });\n";
                $files['resources/frontend/App.svelte'] = "<h1>Nexus + Svelte</h1>\n";
                $inputs[] = 'resources/frontend/main.js';
                break;

            case FrontendRenderer::Solid:
                $plugins[] = "import solid from 'vite-plugin-solid';";
                $imports[] = 'solid()';
                $files['resources/frontend/main.jsx'] = "import { render } from 'solid-js/web';\n\nconst App = () => <h1>Nexus + SolidJS</h1>;\n\nrender(() => <App />, document.getElementById('app'));\n";
                $inputs[] = 'resources/frontend/main.jsx';
                break;

            default:
                $js = [];

                if ($frontend->interactivity === FrontendInteractivity::Htmx || $frontend->interactivity === FrontendInteractivity::HtmxAlpine) {
                    $js[] = "import 'htmx.org';";
                }

                if ($frontend->interactivity === FrontendInteractivity::Alpine || $frontend->interactivity === FrontendInteractivity::HtmxAlpine) {
                    $js[] = "import Alpine from 'alpinejs';";
                    $js[] = 'window.Alpine = Alpine;';
                    $js[] = 'Alpine.start();';
                }

                if ($frontend->css === CssFramework::Bootstrap) {
                    $js[] = "import 'bootstrap/dist/css/bootstrap.min.css';";
                    $js[] = "import 'bootstrap';";
                }

                if ($js !== []) {
                    $files['resources/frontend/app.js'] = implode(PHP_EOL, $js) . PHP_EOL;
                    $inputs[] = 'resources/frontend/app.js';
                }
                break;
        }

        if ($frontend->renderer === FrontendRenderer::React && $frontend->css === CssFramework::Bootstrap) {
            $files['resources/frontend/bootstrap.js'] = "import 'bootstrap/dist/css/bootstrap.min.css';\nimport 'bootstrap';\n";
            $inputs[] = 'resources/frontend/bootstrap.js';
        }

        if ($inputs === []) {
            return $files;
        }

        $pluginImports = $plugins === [] ? '' : implode(PHP_EOL, $plugins) . PHP_EOL;
        $pluginList = $imports === [] ? '[]' : '[' . implode(', ', $imports) . ']';
        $inputList = implode(', ', array_map(static fn (string $input): string => "'{$input}'", array_values(array_unique($inputs))));

        $files['vite.config.js'] = $pluginImports
            . "import { defineConfig } from 'vite';\n\n"
            . "export default defineConfig({\n"
            . "    plugins: {$pluginList},\n"
            . "    build: {\n"
            . "        outDir: 'public/build',\n"
            . "        emptyOutDir: true,\n"
            . "        rollupOptions: { input: [{$inputList}] },\n"
            . "    },\n"
            . "});\n";

        return $files;
    }
}
