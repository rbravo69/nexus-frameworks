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

HTTP, database, Eloquent, Redis, MongoDB, CQRS, events, queues, async, gRPC,
reporting and mail are not core dependencies.

## Progressive structure

A module may start with a single class and evolve internally toward layers,
Hexagonal Architecture, DDD or CQRS. Nexus does not force every module in an
application to use the same degree of ceremony.

## Lifecycle

Application startup is deterministic:

1. `before_boot`
2. register selected capabilities in dependency order
3. register every module
4. `after_register`
5. boot selected capabilities in dependency order
6. boot every module
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

## Dependency injection

The PSR-11 container supports constructor autowiring, explicit interface
bindings, factories and four lifetimes: transient, singleton, request and
worker. Request and worker scopes are explicit boundaries; resolving a scoped
service outside its active boundary is an error.

Bindings are lazy. A service is built only when requested, and `lazy()` can
defer even the container lookup until a value is actually consumed.

## CLI

The CLI has its own small command kernel, input parser and output contracts. It
does not require HTTP or database capabilities. Commands are registered
explicitly and return stable process exit codes (`0`, `1` or `2`).

Project generation supports interactive and non-interactive operation. File
generators never overwrite an existing file.
