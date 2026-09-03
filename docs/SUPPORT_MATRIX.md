# Verified support matrix — v0.1 release-candidate cycle

This document is the canonical snapshot of what the current Nexus tree can
claim. It distinguishes implemented code from CI validation and from future
roadmap intent.

## Status vocabulary

- **Implemented** — code exists in the repository and is covered by the normal quality suite.
- **Live CI** — exercised against a real service/runtime in GitHub Actions.
- **Build CI** — generated configuration/images are validated or built, without claiming an application-specific production deployment.
- **Planned** — roadmap intent only; not a v0.1 support claim.

## PHP and quality

| Area | Claim | Validation |
| --- | --- | --- |
| PHP | PHP 8.4+ | Quality matrix on PHP 8.4 and 8.5 |
| Tests | PHPUnit | Quality CI |
| Static analysis | PHPStan level max | Quality CI |
| Coding standard | PER-CS via PHP-CS-Fixer | Standards CI on PHP 8.4 |
| Dependency security | Composer audit | Standards CI |
| Performance smoke | Nexus benchmark command | Quality CI |

## Core and application model

| Area | v0.1 claim | Boundary |
| --- | --- | --- |
| Lifecycle/configuration | Implemented | Foundation API remains pre-1.0 |
| PSR-11 container | Implemented | Transient, singleton, request and worker scopes |
| Modules | Implemented | Nine architecture presets; architecture is module metadata |
| Capabilities | Implemented | Manifest-driven, dependency-aware install/load lifecycle |
| CLI | Implemented | Explicit command registry; see `nexus list` for current commands |
| CQRS | Implemented | Synchronous command/query buses; no Event Sourcing claim |
| Events | Implemented | Synchronous event bus; no broker/queue claim |

## HTTP/API

| Area | v0.1 claim | Boundary |
| --- | --- | --- |
| Request/response/routing | Implemented | Nexus-native HTTP abstractions |
| Middleware pipeline | Implemented | PSR-15-style semantics; not a blanket PSR-7/15 implementation claim |
| Route attributes | Implemented | Optional alternative to explicit route registration |
| REST helpers | Implemented | JSON envelopes, common response helpers and Problem Details |
| Validation | Implemented | ORM-independent rule set documented by the validation phase |
| OpenAPI | Implemented | OpenAPI 3.1 generation from registered routes |
| gRPC transport | Planned | A project-generation preset exists, but v0.1 does not implement a gRPC runtime |

## Frontend scaffolding

Traditional and modular monolith project generation can record and scaffold a
frontend stack without making frontend libraries part of Nexus Core.

| Area | v0.1 claim | Validation |
| --- | --- | --- |
| Twig | Composer dependency + starter view scaffolding | PHPUnit + generated scaffold CI |
| PHP Native | Starter PHP view scaffolding | PHPUnit |
| React | Vite starter scaffolding | Generated scaffold Build CI |
| Vue.js | Vite starter scaffolding | Generated scaffold Build CI |
| Svelte | Vite starter scaffolding | Generated scaffold Build CI |
| SolidJS | Vite starter scaffolding | Generated scaffold Build CI |
| HTMX / Alpine.js | Optional server-rendered asset scaffolding | Generated scaffold Build CI |
| Tailwind CSS | Optional Vite CSS scaffolding | Generated scaffold Build CI |
| Bootstrap | Optional asset scaffolding | Generated scaffold Build CI |
| Bulma | Optional CSS scaffolding | Generated scaffold Build CI |
| DaisyUI | Optional Tailwind component plugin | Generated scaffold Build CI |
| Material UI | Optional React component dependency | Generated scaffold Build CI |

Compatibility rules are enforced before generation: HTMX/Alpine.js are limited
to Twig or PHP Native selections, DaisyUI requires Tailwind CSS, and Material UI
requires React. The selected stack is stored in `nexus.json`.

**Validation boundary:** this is project and asset **scaffolding** support. Vite
CI installs the generated npm dependencies and proves representative generated
stacks can build. It does not yet claim a Nexus-native view abstraction,
automatic HTTP controller-to-template rendering, SPA routing, SSR/hydration, or
production asset deployment integration. Those claims require separate runtime
features and tests.

## Relational databases

| Engine | Database Core | Schema/Code First/basic Database First | Live CI |
| --- | --- | --- | --- |
| PostgreSQL | Implemented | Implemented | Yes |
| MySQL | Implemented | Implemented | Yes |
| SQLite | Implemented | Implemented | Unit/in-memory CI |
| SQL Server | Implemented | Implemented | Yes |
| Oracle | Implemented | Implemented | Yes |

Relational support uses PDO drivers. SQL Server requires PDO SQLSRV when used;
Oracle requires PDO OCI when used. Live CI probes validate connectivity and the
current integration contract, not every database feature or server version.

Advanced introspection for views, materialized views, procedures, functions,
triggers and sequences remains roadmap work.

## Eloquent

The optional Eloquent integration is implemented through `illuminate/database`.
PostgreSQL, MySQL, SQLite and SQL Server can use Illuminate-supported drivers.
Oracle is **not** claimed as a native Eloquent driver: Nexus Database Core
supports Oracle, while Eloquent-on-Oracle requires an explicit third-party
adapter selected by the application.

The repository's Eloquent integration tests use SQLite in memory. Relational
live CI validates Nexus Database Core separately from Eloquent.

## MongoDB

The optional MongoDB capability includes:

- `MongoLibraryConnection` over the official `mongodb/mongodb` library;
- document insert/find/update/delete operations;
- index creation and listing;
- collection listing;
- named lazy connections through `MongoManager`;
- `MongoRepository`;
- conservative Database First introspection of collections and indexes.

MongoDB is not modeled as SQL/PDO and Nexus does not claim a custom ODM.
`ext-mongodb` and `mongodb/mongodb` are optional requirements only for
applications that use this adapter.

**Validation boundary:** the adapter is currently tested with protocol-compatible
test doubles. There is no live MongoDB service workflow in the release-candidate
matrix yet, so documentation must not describe MongoDB as live-CI validated.

## Cache and Redis

| Area | Claim | Boundary |
| --- | --- | --- |
| Array cache | Implemented | Process-local |
| Filesystem cache | Implemented | Serializer abstraction and TTL support |
| Redis cache contract | Implemented | Depends on an application-selected Redis client adapter |
| Redis locks | Implemented | Token-based lock abstraction |
| Namespaced clear | Implemented | Deletes only the configured prefix |

Nexus does not currently claim queue, session, rate-limit or pub/sub products
merely because Redis can support those use cases in the future.

## Seeders, factories and fake data

Seed runners, factories and deterministic fake-data foundations are implemented.
Advanced schema/FK-aware generation and broad Database First factory synthesis
should only be claimed when their specific commands and tests exist.

## Docker

Supported generated runtime topologies:

- FrankenPHP;
- PHP-FPM + Nginx;
- RoadRunner;
- OpenSwoole.

Docker CI generates production variants, validates Compose configuration and
builds the relevant runtime images. This is **Build CI**, not a claim that a
generated application has been load-tested, security-hardened for every hosting
environment, or end-to-end deployed in production.

Selectable infrastructure services currently generated by Nexus are PostgreSQL,
MySQL, Redis, MongoDB, SQL Server, RabbitMQ, Kafka and Mailpit. Their presence in
Docker generation does not imply Nexus v0.1 implements a first-class application
adapter for every service (notably RabbitMQ and Kafka messaging remain future
framework work).

## Not part of the v0.1 support claim

Unless explicitly implemented in a later RC, the following remain planned:

- async runtime abstraction;
- gRPC transport/runtime;
- queue subsystem and workers;
- RabbitMQ, Kafka and SQS application adapters;
- Event Sourcing;
- advanced MongoDB transactions/capabilities;
- advanced relational introspection;
- reporting packages;
- mail package;
- observability/Architecture Guard;
- AI/MCP capabilities.

The roadmap may describe these future directions, but roadmap intent must not be
presented as current support.
