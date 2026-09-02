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
2. register every module
3. `after_register`
4. boot every module
5. `after_boot`

Shutdown runs modules in reverse registration order between `before_shutdown`
and `after_shutdown`.

## Dependency injection

The PSR-11 container supports constructor autowiring, explicit interface
bindings, factories and four lifetimes: transient, singleton, request and
worker. Request and worker scopes are explicit boundaries; resolving a scoped
service outside its active boundary is an error.

Bindings are lazy. A service is built only when requested, and `lazy()` can
defer even the container lookup until a value is actually consumed.
