<?php

declare(strict_types=1);

namespace Nexus\View;

use Throwable;

final readonly class NativePhpRenderer implements ViewRendererInterface
{
    public function __construct(private ViewFinder $finder)
    {
    }

    public function render(string $view, array $data = []): string
    {
        $file = $this->finder->find($view, ['php']);
        $level = ob_get_level();
        ob_start();

        try {
            extract($data, EXTR_SKIP);
            require $file;

            $output = ob_get_clean();

            return $output === false ? '' : $output;
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }
    }
}
