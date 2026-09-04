# Web monolith runtime

Nexus keeps server-rendered web features optional. They are registered only when
a project selects the Twig or PHP Native frontend renderer.

## Web route group

Normal browser routes can be grouped with `Router::web()`. Nexus applies the
standard server-rendered middleware stack automatically:

```text
SessionMiddleware
CsrfMiddleware
```

Example:

```php
$router->web(function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->post('/profile', [ProfileController::class, 'update']);
});
```

An optional prefix can be supplied as the second argument:

```php
$router->web(function (Router $router): void {
    $router->get('/dashboard', [AdminController::class, 'index']);
}, '/admin');
```

`Router::web()` is intended for Twig/PHP Native monolith routes. API routes stay
free of sessions and CSRF unless the application adds those concerns explicitly.

## Redirect responses

Redirects are first-class responses and may carry flash data for the next web
request:

```php
use function Nexus\redirect;

return redirect('/dashboard')
    ->with('success', 'Profile saved.');
```

`SessionMiddleware` persists flash values from `RedirectResponse` automatically.
Redirecting back to the referring page is explicit and request-aware:

```php
use function Nexus\redirect_back;

return redirect_back($request, '/fallback')
    ->with('error', 'Please review the form.');
```

`Response::redirect()` remains available and now returns the same
`RedirectResponse` type, so existing code remains compatible.

## Returning views from controllers and routes

Nexus exposes a small stateless helper:

```php
use function Nexus\view;

return view('home', [
    'title' => 'Nexus',
]);
```

The helper returns a `ViewResult`. `HttpKernel` converts it to a normal HTML
`Response` through the configured `View` service. Controllers may still return
`Response` directly when they need explicit control.

## View conventions

Generated server-rendered monoliths use real, non-empty view conventions:

```text
resources/views/
├── layouts/
├── components/
├── partials/
└── home.twig|php
```

Twig projects use normal Twig inheritance and includes. PHP Native projects use
plain PHP composition and output buffering; Nexus does not invent a second PHP
template language.

## Module views

When a registered module has a `Views` directory, Nexus exposes it automatically
as a view namespace. For example:

```text
modules/
└── Catalog/
    └── Views/
        └── products/
            └── card.twig
```

A module named `catalog` can render it with:

```php
return view('catalog::products/card.twig', [
    'product' => $product,
]);
```

The same namespace notation works with PHP Native views.

## HTML errors

For requests accepting `text/html`, Nexus first looks for an application error
view such as:

```text
resources/views/errors/404.twig
resources/views/errors/419.twig
resources/views/errors/500.twig
```

PHP Native uses the matching `.php` names. If an application override is not
present, Nexus uses its small built-in generic HTML error view. JSON-oriented
requests continue to receive JSON errors.

The standard web mappings currently include:

- 404 Not Found
- 405 Method Not Allowed
- 419 Page Expired for CSRF failures
- 500 Internal Server Error

## Assets and Vite

`AssetManager` reads the Vite manifest from either
`public/build/.vite/manifest.json` or `public/build/manifest.json` and resolves
hashed build files.

Twig exposes:

```twig
<link rel="stylesheet" href="{{ asset('resources/frontend/app.css') }}">
```

PHP Native views receive a callable named `$asset`:

```php
<link rel="stylesheet" href="<?= htmlspecialchars($asset('resources/frontend/app.css')) ?>">
```

`AssetManager` is also registered in the container for explicit constructor
injection.

## Sessions and flash data

Server-rendered monoliths receive the `SessionInterface` contract backed by PHP
native sessions by default. Session startup remains lazy until session data is
actually used or `SessionMiddleware` begins a web request.

Flash values are intended for the next request:

```php
$session->flash('success', 'Saved successfully.');
```

`Router::web()` applies `SessionMiddleware` automatically. Manual route groups
may still use it directly when an application needs a custom stack.

## CSRF

`CsrfTokenManager` stores a cryptographically random token in the session.
Unsafe form requests are protected automatically inside `Router::web()` groups.

Twig:

```twig
<form method="post">
    {{ csrf_field() }}
</form>
```

PHP Native:

```php
<form method="post">
    <?= $csrf_field() ?>
</form>
```

The middleware accepts the `_token` form field or the `X-CSRF-TOKEN` header.
GET, HEAD, OPTIONS and TRACE are treated as safe methods.

## Form validation and old input

`FormValidator` wraps the existing Nexus validator for browser form workflows.
On failure it flashes validation errors and safe old input, then throws a web
validation exception that `HttpKernel` converts to a redirect.

```php
$validated = $forms->validate($request, [
    'email' => 'required|email',
    'name' => 'required|string|max:120',
]);
```

Sensitive keys such as CSRF tokens and passwords are not kept as old input.

Twig helpers:

```twig
<input name="email" value="{{ old('email') }}">
{% if error('email') %}
    <p>{{ error('email') }}</p>
{% endif %}
```

PHP Native views receive `$old` and `$errors` callables through the shared view
context.

## Web authentication

`AuthManager` provides session authentication without coupling Nexus to a
specific ORM or user table. Applications implement `UserProviderInterface` and
bind it in the container:

```php
$application->container()->instance(
    UserProviderInterface::class,
    new DatabaseUserProvider(),
);
```

The provider is responsible for retrieving users and verifying credentials. Use
PHP's `password_hash()` and `password_verify()` or another appropriate secure
credential mechanism; Nexus does not store or compare plaintext passwords.

Login:

```php
if ($auth->attempt([
    'email' => $request->input('email'),
    'password' => $request->input('password'),
])) {
    return redirect('/dashboard');
}
```

`AuthMiddleware` protects routes. HTML guests are redirected to `/login`; other
clients receive HTTP 401 JSON. `AuthManager` also exposes `hasRole()` for simple
role checks while applications remain free to build richer authorization on top.

## Philosophy

These features follow the same Nexus rules as the rest of the framework:

- server-rendered web services are not registered for API-only or client-side
  React/Vue/Svelte/Solid projects;
- PHP Native remains dependency-free;
- Twig remains optional;
- the application owns persistence and user-provider decisions;
- helpers return explicit value objects rather than hiding a global container.
