<?php

declare(strict_types=1);

namespace Nexus;

use Nexus\View\ViewResult;

/**
 * @param array<string, mixed> $data
 * @param array<string, string> $headers
 */
function view(string $name, array $data = [], int $status = 200, array $headers = []): ViewResult
{
    return new ViewResult($name, $data, $status, $headers);
}
