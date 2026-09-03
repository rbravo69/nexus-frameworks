<?php

declare(strict_types=1);

namespace Nexus\View;

interface ViewRendererInterface
{
    /** @param array<string, mixed> $data */
    public function render(string $view, array $data = []): string;
}
