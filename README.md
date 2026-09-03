# Nexus Framework

**Simple by default. Powerful by design.**

Nexus is a progressive and modular application framework for modern PHP. It
lets a project start small and adopt modules, Hexagonal Architecture, DDD or
CQRS only when the problem requires them.

> Nexus recommends. The developer decides.

## Status

Nexus is under active development and is **not ready for production**. The
current milestone is the **v0.1 release-candidate hardening cycle**.

The repository already contains working implementations for the foundation
listed below, but pre-1.0 APIs may still change. Support claims are kept in the
[verified support matrix](docs/SUPPORT_MATRIX.md) and are intentionally narrower
than the long-term roadmap.

## Design principles

- A small core written in pure PHP.
- Install and boot only the capabilities an application uses.
- Business-oriented modules instead of framework-oriented folders.
- Infrastructure behind contracts and replaceable adapters.
- No mandatory HTTP, database, cache or ORM dependency in the core.
- Measured performance and explicit behavior over hidden magic.

## Requirements

- PHP 8.4 or newer
- Composer 2

Optional integrations require their own extensions or packages only when used.
For example, SQL Server uses PDO SQLSRV, Oracle uses PDO OCI, MongoDB uses the
official `mongodb/mongodb` library plus `ext-mongodb`, and Eloquent uses
`illuminate/database`.

## Verified v0.1 foundation

Implemented in the current tree:

- application lifecycle, configuration, modules and PSR-11 dependency injection;
- CLI/project generation and manifest-driven optional capabilities;
- HTTP routing, middleware, REST helpers, validation and OpenAPI generation;
- relational Database Core for PostgreSQL, MySQL, SQLite, SQL Server and Oracle;
- neutral migrations, Code First and basic Database First;
- optional Eloquent integration;
- optional MongoDB adapter with CRUD, repositories, collections/index introspection;
- cache, Redis contracts/cache/locks, CQRS, synchronous events;
- seeders, factories and deterministic fake-data foundations;
- optional Docker generation for FrankenPHP, PHP-FPM + Nginx, RoadRunner and OpenSwoole;
- monolith frontend scaffolding for Twig, PHP Native, React, Vue.js, Svelte and SolidJS;
- optional HTMX, Alpine.js, Tailwind CSS, Bootstrap, Bulma, DaisyUI and Material UI scaffolding;
- PHPUnit, PHPStan max, PER-CS checks, dependency audit and benchmark smoke gates.

Important validation boundaries are documented in the
[support matrix](docs/SUPPORT_MATRIX.md). In particular, relational engines have
live CI probes; MongoDB currently has adapter tests without a live MongoDB CI
service, and Docker CI validates generated topology/buildability rather than an
application-specific production deployment.

Queues, async runtimes, gRPC, messaging brokers, reporting, mail and other
future packages remain roadmap work unless a document explicitly states
otherwise.

## Core preview

```php
<?php

use Nexus\Bootstrap;

require __DIR__ . '/vendor/autoload.php';

$app = Bootstrap::create(basePath: __DIR__);
$app->boot();

// Application code...

$app->shutdown();
```

Modules are explicit and have a predictable lifecycle:

```php
$app->modules()->add(new BookingModule());
$app->boot();
```

Constructor injection and interface bindings are handled by the built-in
PSR-11 container:

```php
use Nexus\Container\Scope;

$app->container()
    ->bind(PaymentGateway::class, StripeGateway::class)
    ->factory(
        ExchangeRates::class,
        fn ($container) => new ExchangeRates($container->get(HttpClient::class)),
        Scope::Singleton,
    );

$service = $app->container()->get(CheckoutService::class);
```

## Development

```bash
composer install
composer verify
```

`composer verify` runs the unit test/static-analysis suite, coding-standard
check and dependency security audit. CI also runs Docker runtime build checks,
live relational integration probes and generated frontend build smoke tests.

## CLI

```bash
vendor/bin/nexus list
vendor/bin/nexus new booking-api --type=api --no-interaction
vendor/bin/nexus add redis
vendor/bin/nexus remove redis
vendor/bin/nexus make:module Booking --architecture=hexagonal --depends=identity
vendor/bin/nexus doctor
vendor/bin/nexus benchmark
vendor/bin/nexus serve
vendor/bin/nexus docker:init --runtime=frankenphp --services=postgres,redis
```

Running `nexus new` without `--type` starts a focused wizard with six presets:
API REST, microservice, gRPC service, module, traditional monolith and modular
monolith. The preset name describes generated project intent; it does **not**
mean every corresponding runtime transport (for example gRPC) is implemented
in v0.1. CI can use `--no-interaction` for deterministic generation.

Traditional and modular monolith presets can also scaffold a frontend stack.
The wizard keeps rendering, interactivity, CSS and component libraries as
separate decisions:

- frontend renderer: `twig`, `php`, `react`, `vue`, `svelte`, `solid` or `none`;
- server-rendered interactivity: `none`, `htmx`, `alpine` or `htmx-alpine`;
- CSS framework: `none`, `tailwind`, `bootstrap` or `bulma`;
- component library: `none`, `daisyui` or `mui`.

Compatibility is validated before files are generated: HTMX/Alpine.js are
restricted to Twig or PHP Native rendering, DaisyUI requires Tailwind CSS, and
Material UI requires React.

A deterministic non-interactive example is:

```bash
vendor/bin/nexus new storefront \
  --type=modular-monolith \
  --frontend=twig \
  --interactivity=htmx-alpine \
  --css=tailwind \
  --components=daisyui \
  --no-interaction
```

When frontend assets are needed Nexus generates `package.json`, Vite
configuration and the corresponding source files. Twig is added to Composer
only when the Twig renderer is selected. The selected stack is recorded in
`nexus.json` so tooling can inspect it later.

Capabilities are Composer packages selected in `nexus.json`. Nexus installs
their dependencies in order, prevents unsafe removals and loads only the
selected providers during application bootstrap. See the
[capabilities guide](docs/CAPABILITIES.md).

Every module chooses its own level of ceremony. Available presets are
`minimal`, `mvc`, `layered`, `modular`, `hexagonal`, `clean`, `ddd`, `cqrs` and
`custom`. The generator creates only directories containing real files, and
the runtime detects missing dependencies and cycles before registration. See
the [modules guide](docs/MODULES.md).

See [the architecture](docs/ARCHITECTURE.md), [verified support matrix](docs/SUPPORT_MATRIX.md),
[roadmap](docs/ROADMAP.md), and [contribution guide](CONTRIBUTING.md) before
proposing a change.

## License

Nexus Framework is open source software licensed under the MIT License.