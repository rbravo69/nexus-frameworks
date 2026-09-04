<?php

declare(strict_types=1);

namespace Nexus;

use Nexus\Http\RedirectResponse;
use Nexus\Http\Request;
use Nexus\View\ViewResult;

/**
 * @param array<string, mixed> $data
 * @param array<string, string> $headers
 */
function view(string $name, array $data = [], int $status = 200, array $headers = []): ViewResult
{
    return new ViewResult($name, $data, $status, $headers);
}

/** @param array<string, string> $headers */
function redirect(string $location, int $status = 302, array $headers = []): RedirectResponse
{
    return RedirectResponse::to($location, $status, $headers);
}

/** @param array<string, string> $headers */
function redirect_back(
    Request $request,
    string $fallback = '/',
    int $status = 302,
    array $headers = [],
): RedirectResponse {
    return RedirectResponse::back($request, $fallback, $status, $headers);
}
