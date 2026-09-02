# HTTP and Routing

Nexus HTTP is intentionally small and independent from the application core.
Applications that do not need HTTP do not load it.

## Request and response

```php
$request = new Request('GET', '/users/42');
$response = Response::json(['id' => 42]);
```

Responses support plain text, JSON and streamed callbacks.

## Routes

```php
$router->get('/health', fn (Request $request, array $params) => Response::text('ok'));
$router->post('/bookings', [BookingController::class, 'store']);
```

Parameterized routes expose parameters both as the handler parameter array and
as request attributes.

## Route groups

```php
$router->group('/api/v1', function (Router $router): void {
    $router->get('/users/{id}', [UserController::class, 'show']);
});
```

Groups can carry middleware and can be nested.

## Attributes

```php
#[Route('/bookings/{id}', methods: ['GET'])]
public function show(Request $request, array $params): Response
{
    // ...
}
```

`AttributeRouteLoader` converts controller attributes into the same internal
route representation used by explicit routes. Attributes are therefore syntax,
not a second routing system.

## Middleware

Nexus uses a request-handler pipeline with PSR-15 style semantics while keeping
the HTTP package independent. Provider-specific interoperability adapters can be
added without changing application middleware.

## Error handling

The default HTTP kernel returns:

- 404 for an unknown route;
- 405 with an `Allow` header when a path exists for another method;
- 500 for uncaught exceptions.

Debug exception messages are opt-in.
