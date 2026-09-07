<?php

declare(strict_types=1);

use Nexus\Application;
use Nexus\Http\HttpKernel;
use Nexus\Http\Request;
use Nexus\Http\Response;

$projectRoot = getcwd();

if (!is_string($projectRoot) || !is_file($projectRoot . '/bootstrap.php')) {
    http_response_code(500);
    echo "Nexus bootstrap.php was not found in the working directory.\n";
    return;
}

/** @var Application $application */
$application = require $projectRoot . '/bootstrap.php';
$application->boot();

try {
    $kernel = $application->container()->get(HttpKernel::class);

    if (!$kernel instanceof HttpKernel) {
        throw new RuntimeException('Nexus HTTP runtime is not registered.');
    }

    $response = $kernel->handle(Request::fromGlobals());

    http_response_code($response->status());

    foreach ($response->headers() as $name => $value) {
        header($name . ': ' . $value);
    }

    if ($response->isStreamed()) {
        $response->sendStream();
    } else {
        echo $response->body();
    }
} finally {
    $application->shutdown();
}
