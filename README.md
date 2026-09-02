# Nexus Framework

**Simple by default. Powerful by design.**

Nexus is a progressive and modular application framework for modern PHP. It
lets a project start small and adopt modules, Hexagonal Architecture, DDD or
CQRS only when the problem requires them.

> Nexus recommends. The developer decides.

## Status

Nexus is under active development and is not ready for production. The current
milestone is **v0.1 Foundation**.

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
composer quality
```

## CLI

```bash
vendor/bin/nexus list
vendor/bin/nexus new booking-api --type=api --no-interaction
vendor/bin/nexus add redis
vendor/bin/nexus remove redis
vendor/bin/nexus make:module Booking
vendor/bin/nexus doctor
```

Running `nexus new` without `--type` starts a focused wizard with six presets:
API REST, microservice, gRPC service, module, traditional monolith and modular
monolith. CI can use `--no-interaction` for deterministic generation.

Capabilities are Composer packages selected in `nexus.json`. Nexus installs
their dependencies in order, prevents unsafe removals and loads only the
selected providers during application bootstrap. See the
[capabilities guide](docs/CAPABILITIES.md).

See [the architecture](docs/ARCHITECTURE.md), [roadmap](docs/ROADMAP.md), and
[contribution guide](CONTRIBUTING.md) before proposing a change.

## License

Nexus Framework is open source software licensed under the MIT License.
