<?php

declare(strict_types=1);

namespace Nexus\View;

use Nexus\Http\Response;

final readonly class View
{
    public function __construct(private ViewRendererInterface $renderer)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $name, array $data = []): string
    {
        return $this->renderer->render($name, $data);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function response(string $name, array $data = [], int $status = 200, array $headers = []): Response
    {
        return Response::html($this->render($name, $data), $status, $headers);
    }
}
