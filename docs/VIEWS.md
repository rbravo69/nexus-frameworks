# Views

Nexus provides a small renderer-neutral view runtime for server-rendered monoliths.
PHP Native is dependency-free. Twig is the recommended optional renderer and is
installed only when the project selects it.

## Automatic registration

Projects generated with `nexus new` already write the selected renderer to
`config/frontend.php`. During bootstrap Nexus reads `frontend.renderer` and
automatically registers the server-rendered view runtime when the value is
`twig` or `php`.

The default view root is:

```text
resources/views/
```

For Twig, the default production cache location is:

```text
.nexus/cache/twig/
```

`APP_DEBUG=true` is passed through to the Twig renderer so development uses
Twig debug, auto-reload and strict variables. Client-side frontend selections
such as React, Vue.js, Svelte and SolidJS do not register `ViewRendererInterface`.

This means a generated Twig or PHP Native monolith does not need to call
`ViewFactory::register()` manually.

## Manual registration and overrides

Applications may still register a renderer explicitly when they need custom
paths, module namespaces or another bootstrap composition:

```php
use Nexus\View\ViewFactory;

$views = ViewFactory::register(
    application: $application,
    renderer: 'twig',
    viewsPath: __DIR__ . '/resources/views',
    cachePath: __DIR__ . '/.nexus/cache/twig',
    debug: false,
);
```

For PHP Native use `renderer: 'php'` and no Twig dependency is required.
Registration binds `ViewFinder`, `ViewRendererInterface` and `View` into the
Nexus container, so controllers and application services can request `View` by
constructor injection.

The automatic bootstrap also understands optional configuration overrides:

```php
return [
    'renderer' => 'twig',
    'views_path' => __DIR__ . '/../resources/views',
    'cache_path' => __DIR__ . '/../var/cache/twig',
];
```

Generated projects normally rely on the defaults and therefore do not need
these extra keys.

## Render HTML

```php
use Nexus\View\View;

final readonly class PropertyController
{
    public function __construct(private View $views) {}

    public function show(): \Nexus\Http\Response
    {
        return $this->views->response('properties/show.twig', [
            'title' => 'Property',
        ]);
    }
}
```

`View::response()` produces a normal Nexus HTTP response with
`text/html; charset=utf-8`.

## Global and module views

Global views normally live in:

```text
resources/views/
```

Modules can expose their own view roots through namespaces. Explicit
registration remains available for this use case:

```php
ViewFactory::register(
    application: $application,
    renderer: 'twig',
    viewsPath: __DIR__ . '/resources/views',
    namespaces: [
        'catalog' => __DIR__ . '/modules/Catalog/Views',
        'booking' => __DIR__ . '/modules/Booking/Views',
    ],
);
```

Twig module view:

```php
$views->response('catalog::products/card.twig', ['product' => $product]);
```

PHP Native uses the same Nexus namespace notation.

## Twig cache

Automatic registration uses `.nexus/cache/twig` by default. Manual registration
can pass another writable `cachePath`. Development can enable `debug: true`,
which also enables Twig auto-reload and strict variables.

## Frontend assets

The view runtime is independent from frontend assets. A project may separately
select HTMX, Alpine.js, Tailwind CSS, Bootstrap, Bulma or DaisyUI through the
monolith frontend scaffold. Twig does not imply HTMX or a CSS framework.

React, Vue.js, Svelte and SolidJS remain frontend asset/application integrations;
they do not use `ViewRendererInterface` as template engines.
