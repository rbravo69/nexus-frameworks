<?php

declare(strict_types=1);

namespace Nexus\View;

use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final readonly class TwigRenderer implements ViewRendererInterface
{
    private Environment $twig;

    public function __construct(
        ViewFinder $finder,
        ?string $cachePath = null,
        bool $debug = false,
    ) {
        if (!class_exists(Environment::class) || !class_exists(FilesystemLoader::class)) {
            throw new RuntimeException('Twig rendering requires the optional twig/twig package.');
        }

        $loader = new FilesystemLoader($finder->paths());

        foreach ($finder->namespaces() as $namespace => $paths) {
            foreach ($paths as $path) {
                $loader->addPath($path, $namespace);
            }
        }

        $this->twig = new Environment($loader, [
            'cache' => $cachePath ?? false,
            'debug' => $debug,
            'strict_variables' => $debug,
            'auto_reload' => $debug,
        ]);
    }

    public function render(string $view, array $data = []): string
    {
        $name = str_contains($view, '::')
            ? str_replace('::', '/', '@' . $view)
            : $view;

        return $this->twig->render($name, $data);
    }
}
