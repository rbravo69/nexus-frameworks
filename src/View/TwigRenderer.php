<?php

declare(strict_types=1);

namespace Nexus\View;

use RuntimeException;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final readonly class TwigRenderer implements ViewRendererInterface
{
    private Environment $twig;

    private FilesystemLoader $loader;

    public function __construct(
        ViewFinder $finder,
        ?string $cachePath = null,
        bool $debug = false,
        ?WebViewContext $context = null,
    ) {
        if (!class_exists(Environment::class) || !class_exists(FilesystemLoader::class) || !class_exists(TwigFunction::class)) {
            throw new RuntimeException('Twig rendering requires the optional twig/twig package.');
        }

        $this->loader = new FilesystemLoader($finder->paths());

        foreach ($finder->namespaces() as $namespace => $paths) {
            foreach ($paths as $path) {
                $this->loader->addPath($path, $namespace);
            }
        }

        $this->twig = new Environment($this->loader, [
            'cache' => $cachePath ?? false,
            'debug' => $debug,
            'strict_variables' => $debug,
            'auto_reload' => $debug,
        ]);

        if ($context !== null) {
            $this->registerWebFunctions($context);
        }
    }

    public function addNamespace(string $namespace, string $path): void
    {
        $this->loader->addPath($path, $namespace);
    }

    public function render(string $view, array $data = []): string
    {
        $name = str_contains($view, '::')
            ? str_replace('::', '/', '@' . $view)
            : $view;

        return $this->twig->render($name, $data);
    }

    private function registerWebFunctions(WebViewContext $context): void
    {
        $feedback = $context->feedback();

        $this->twig->addFunction(new TwigFunction(
            'asset',
            fn (string $entry): string => $context->assets()->url($entry),
        ));
        $this->twig->addFunction(new TwigFunction(
            'vite',
            fn (string|array $entries): string => $context->assets()->tags($entries),
            ['is_safe' => ['html']],
        ));
        $this->twig->addFunction(new TwigFunction(
            'csrf_token',
            fn (): string => $context->csrf()->token(),
        ));
        $this->twig->addFunction(new TwigFunction(
            'csrf_field',
            fn (): string => $context->csrf()->field(),
            ['is_safe' => ['html']],
        ));
        $this->twig->addFunction(new TwigFunction(
            'old',
            fn (string $key, mixed $default = null): mixed => $feedback->old($key, $default),
        ));
        $this->twig->addFunction(new TwigFunction(
            'errors',
            fn (): array => $feedback->errors(),
        ));
        $this->twig->addFunction(new TwigFunction(
            'error',
            fn (string $key, ?string $default = null): ?string => $feedback->error($key, $default),
        ));
        $this->twig->addFunction(new TwigFunction(
            'error_messages',
            fn (string $key): array => $feedback->errorMessages($key),
        ));
        $this->twig->addFunction(new TwigFunction(
            'has_error',
            fn (string $key): bool => $feedback->hasError($key),
        ));
        $this->twig->addFunction(new TwigFunction(
            'any_errors',
            fn (): bool => $feedback->anyErrors(),
        ));
        $this->twig->addFunction(new TwigFunction(
            'flash',
            fn (string $key, mixed $default = null): mixed => $feedback->flash($key, $default),
        ));
        $this->twig->addFunction(new TwigFunction(
            'has_flash',
            fn (string $key): bool => $feedback->hasFlash($key),
        ));
        $this->twig->addFunction(new TwigFunction(
            'auth',
            fn (): mixed => $context->auth()->user(),
        ));
    }
}
