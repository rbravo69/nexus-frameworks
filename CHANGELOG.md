# Changelog

All notable changes to Nexus Framework are documented in this file.

The project follows Semantic Versioning. Before 1.0, public APIs may still change between minor releases; release notes will call out intentional compatibility breaks.

## [0.1.0-rc.1] - 2026-09-03

First public release candidate of the Nexus v0.1 Foundation.

### Added

- Application bootstrap, lifecycle, configuration and module registry.
- PSR-11 dependency injection container with transient, singleton, request and worker scopes.
- Progressive modules with minimal, MVC, layered, modular, hexagonal, clean, DDD, CQRS and custom architecture presets.
- CLI project generation, capability management, module/code generators, doctor, serve, benchmark and Docker commands.
- HTTP request/response primitives, router, middleware pipeline, controller DI and PHP Attribute routing.
- REST response helpers, Problem Details support, validation and OpenAPI 3.1 generation.
- Relational Database Core with PostgreSQL, MySQL, SQLite, SQL Server and Oracle support.
- Neutral Schema Model, migrations, Code First diff/apply and basic Database First introspection.
- Optional Eloquent integration without making Illuminate a Nexus Core dependency.
- Optional MongoDB adapter, document repository facade and basic collection/index introspection.
- Cache core, filesystem/array cache, Redis cache/lock contracts and safe namespace clearing.
- CQRS command/query buses and synchronous event bus.
- Seeders, factories, deterministic fake-data generation and SQL/Mongo seed stores.
- Optional Docker generation for FrankenPHP, PHP-FPM + Nginx, RoadRunner and OpenSwoole.
- PHPUnit, PHPStan max, PER-CS checks, Composer dependency audit and benchmark infrastructure.
- Live relational CI for PostgreSQL, MySQL, SQL Server and Oracle.
- Build validation for all four supported Docker runtime topologies.
- Verified support matrix documenting implementation and validation boundaries.

### Changed

- Capability metadata now distinguishes bundled capabilities from future external Composer packages.
- Core no longer depends on CLI internals.
- HTTP no longer depends directly on REST or Validation implementations.
- PDO is optional at the framework root and required only when relational persistence is used.
- Cache serialization is explicit and object hydration is disabled by default during unserialization.
- Redis cache clearing is prefix-scoped instead of database-global.

### Security

- Composer dependency audit is a required CI gate.
- PHP cache unserialization defaults to `allowed_classes=false`.
- Redis cache clearing cannot flush unrelated application namespaces through the cache abstraction.
- SQL Server CI uses explicit TLS options for the ephemeral integration environment rather than disabling encryption globally.

### Validation boundaries

- PostgreSQL, MySQL, SQL Server and Oracle are exercised against live CI services.
- SQLite is covered through in-memory integration tests.
- MongoDB has an official adapter and protocol-level tests, but no live MongoDB CI service yet.
- Docker CI validates generated Compose topology and image buildability; it does not claim application-specific load, availability or production deployment validation.
- Native gRPC transport, queues, async runtimes, RabbitMQ/Kafka/SQS messaging adapters, mail and reporting remain future work.

[0.1.0-rc.1]: https://github.com/rbravo69/nexus-frameworks/releases/tag/v0.1.0-rc.1
