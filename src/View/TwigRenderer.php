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
        $this->twig->addFunction(new TwigFunction(
            'asset',
            fn (string $entry): string => $context->assets()->url($entry),
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
            static function (string $key, mixed $default = null) use ($context): mixed {
                $old = $context->session()->get('_old_input', []);

                return is_array($old) ? ($old[$key] ?? $default) : $default;
            },
        ));
        $this->twig->addFunction(new TwigFunction(
            'errors',
            static function () use ($context): array {
                $errors = $context->session()->get('_errors', []);

                return is_array($errors) ? $errors : [];
            },
        ));
        $this->twig->addFunction(new TwigFunction(
            'error',
            static function (string $key) use ($context): ?string {
                $errors = $context->session()->get('_errors', []);
                $messages = is_array($errors) ? ($errors[$key] ?? []) : [];

                return is_array($messages) && isset($messages[0]) && is_string($messages[0])
                    ? $messages[0]
                    : null;
            },
        ));
        $this->twig->addFunction(new TwigFunction(
            'flash',
            fn (string $key, mixed $default = null): mixed => $context->session()->get($key, $default),
        ));
        $this->twig->addFunction(new TwigFunction(
            'auth',
            fn (): mixed => $context->auth()->user(),
        ));
    }
}
