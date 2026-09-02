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

## Development

```bash
composer install
composer quality
```

See [the architecture](docs/ARCHITECTURE.md), [roadmap](docs/ROADMAP.md), and
[contribution guide](CONTRIBUTING.md) before proposing a change.

## License

Nexus Framework is open source software licensed under the MIT License.
