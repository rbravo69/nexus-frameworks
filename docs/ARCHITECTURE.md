# Architecture

Nexus consists of a minimal core and optional capabilities. The core owns only
application bootstrapping, environment, configuration, contracts, lifecycle,
exceptions, and module registration.

## Dependency rule

The core never knows an optional implementation. Capabilities depend on core
contracts and applications select their adapters.

```text
Application -> Nexus Core <- Optional capability
```

Implemented optional areas in the current v0.1 tree include HTTP, relational
database access, Eloquent, MongoDB, cache/Redis, CQRS/events, seed/fake-data
foundations and Docker generation. Queues, async, gRPC, messaging, reporting and
mail are architectural directions on the roadmap rather than current v0.1
runtime support.

The canonical boundary between implemented, validated and planned behavior is
recorded in [SUPPORT_MATRIX.md](SUPPORT_MATRIX.md).

## Progressive structure

A module may start with a single class and evolve internally toward layers,
Hexagonal Architecture, DDD or CQRS. Nexus does not force every module in an
application to use the same degree of ceremony.

Module architecture is metadata, not a global application mode. Minimal, MVC,
Layered, Modular, Hexagonal, Clean, DDD, CQRS and Custom modules can coexist.
Dependencies are declared by stable module names and resolved topologically.
Unknown dependencies and cycles fail before any module registration runs.

## Lifecycle

Application startup is deterministic:

1. `before_boot`
2. register selected capabilities in dependency order
3. register every module in dependency order
4. `after_register`
5. boot selected capabilities in dependency order
6. boot every module in dependency order
7. `after_boot`

Shutdown runs modules first and capabilities afterward; each group uses reverse
registration order between `before_shutdown` and `after_shutdown`.

## Capabilities

Each capability has explicit metadata: stable name, Composer package, provider
class and dependencies. The resolver rejects unknown names and dependency
cycles. The installer keeps Composer and `nexus.json` synchronized, rolls back
failed operations and refuses to remove a capability required by another
installed capability.

Runtime loading is manifest-driven. Providers are resolved through the DI
container, then participate in `register`, `boot` and `shutdown`. An optional
package that is not selected is never instantiated.

A capability name or future package appearing in design documentation does not,
by itself, constitute a support claim. Current support must also appear in the
verified support matrix.

## Dependency injection

The PSR-11 container supports constructor autowiring, explicit interface
bindings, factories and four lifetimes: transient, singleton, request and
worker. Request and worker scopes are explicit boundaries; resolving a scoped
service outside its active boundary is an error.

Bindings are lazy. A service is built only when requested, and `lazy()` can
defer even the container lookup until a value is actually consumed.

## Persistence boundaries

Relational persistence uses the neutral Database Core and schema model. PDO
adapters support PostgreSQL, MySQL, SQLite, SQL Server and Oracle. Migrations,
Code First and basic Database First stay independent from Eloquent.

Eloquent is optional and follows Illuminate's driver capabilities. Nexus does
not claim native Oracle Eloquent support; Oracle remains available through
Nexus Database Core unless an application explicitly installs a compatible
third-party Eloquent adapter.

MongoDB is a separate document-persistence path and is never forced through the
relational schema/PDO abstractions. The official optional library adapter does
not turn Nexus into an ODM.

## HTTP and long-running runtimes

Nexus' current HTTP layer uses its own request/response abstractions and
middleware pipeline. Docker can generate RoadRunner and OpenSwoole runtime
topologies, but application-specific long-running bootstrap behavior remains an
explicit entrypoint concern. A gRPC project-generation preset does not imply a
gRPC transport implementation in v0.1.

## CLI

The CLI has its own small command kernel, input parser and output contracts. It
does not require HTTP or database capabilities. Commands are registered
explicitly and return stable process exit codes (`0`, `1` or `2`).

Project generation supports interactive and non-interactive operation. File
generators never overwrite an existing file. `nexus list` is the authoritative
runtime inventory of commands present in a given checkout.
