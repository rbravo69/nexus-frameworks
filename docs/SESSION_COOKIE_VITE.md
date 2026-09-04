# Sessions, cookies and Vite HMR

## Session drivers

Server-rendered Nexus applications resolve `SessionInterface` through `SessionFactory`.
The configured driver is read from `session.driver`.

Supported built-in drivers:

```text
native  PHP native session handling
file    PHP native sessions with an explicit save path
array   in-memory session for tests and short-lived processes
```

Example configuration:

```php
return [
    'driver' => 'file',
    'name' => 'NEXUSSESSID',
    'path' => __DIR__ . '/../.nexus/sessions',
    'secure' => true,
    'same_site' => 'Lax',
];
```

`SessionFactory::fromHandler()` accepts any PHP `SessionHandlerInterface`, allowing database,
Redis or application-specific persistence without coupling the Nexus core to a client library.

## Cookies

`Nexus\Http\Cookie` is the shared immutable cookie value object. It normalizes SameSite values,
URL-encodes values and supports `Secure`, `HttpOnly`, `Domain`, `Path` and `Max-Age` attributes.

```php
use Nexus\Http\Cookie;

$response = $response->withCookie(new Cookie(
    name: 'theme',
    value: 'dark',
    maxAge: 86400,
    secure: true,
));
```

Requests expose parsed cookies directly:

```php
$theme = $request->cookie('theme', 'light');
$all = $request->cookies();
```

The remember-me authentication flow uses the same cookie abstraction.

## Vite and HMR

`AssetManager` supports both production manifests and the Vite development server.

In development, Vite writes its origin to:

```text
public/hot
```

For example:

```text
http://localhost:5173
```

When that file exists, `asset()` resolves entries against the dev server automatically.
The `vite()` helper also injects the Vite HMR client.

Twig:

```twig
{{ vite([
    'resources/frontend/app.css',
    'resources/frontend/app.js'
]) }}
```

PHP Native:

```php
<?= $vite([
    'resources/frontend/app.css',
    'resources/frontend/app.js',
]) ?>
```

In production the same helper resolves hashed files through `public/build/.vite/manifest.json`
or `public/build/manifest.json`, so templates do not need environment-specific branches.
