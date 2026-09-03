<?php

declare(strict_types=1);

namespace Nexus\View;

use InvalidArgumentException;
use Nexus\Application;

final class ViewFactory
{
    /**
     * @param array<string, string> $namespaces
     */
    public static function register(
        Application $application,
        string $renderer,
        string $viewsPath,
        array $namespaces = [],
        ?string $cachePath = null,
        bool $debug = false,
    ): View {
        $finder = new ViewFinder([$viewsPath]);

        foreach ($namespaces as $namespace => $path) {
            $finder->addNamespace($namespace, $path);
        }

        $viewRenderer = match (strtolower(trim($renderer))) {
            'php', 'native', 'php-native' => new NativePhpRenderer($finder),
            'twig' => new TwigRenderer($finder, $cachePath, $debug),
            default => throw new InvalidArgumentException(sprintf('Unknown view renderer "%s".', $renderer)),
        };

        $view = new View($viewRenderer);
        $application->container()
            ->instance(ViewFinder::class, $finder)
            ->instance(ViewRendererInterface::class, $viewRenderer)
            ->instance(View::class, $view);

        return $view;
    }
}
