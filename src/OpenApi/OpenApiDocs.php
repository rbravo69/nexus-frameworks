<?php

declare(strict_types=1);

namespace Nexus\OpenApi;

use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Routing\Router;

final readonly class OpenApiDocs
{
    public function __construct(
        private string $docsPath = '/docs',
        private string $schemaPath = '/openapi.json',
        private string $title = 'Nexus API',
        private string $version = '0.1.0',
    ) {
    }

    public function register(Router $router): void
    {
        $router->get($this->schemaPath, function (Request $request, array $parameters) use ($router): Response {
            $document = (new OpenApiGenerator($this->title, $this->version))->generate($router);
            unset($document['paths'][$this->docsPath], $document['paths'][$this->schemaPath]);

            return Response::json($document);
        });

        $router->get($this->docsPath, fn (Request $request, array $parameters): Response => Response::html(
            $this->swaggerHtml(),
        ));
    }

    private function swaggerHtml(): string
    {
        $schemaPath = json_encode($this->schemaPath, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $title = htmlspecialchars($this->title . ' Docs', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
window.onload = () => {
    SwaggerUIBundle({
        url: {$schemaPath},
        dom_id: '#swagger-ui',
        deepLinking: true,
        displayRequestDuration: true,
        persistAuthorization: true
    });
};
</script>
</body>
</html>
HTML;
    }
}
