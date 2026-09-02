# Design principles

1. **Pure PHP core.** Native extensions or other languages are never an
   architectural requirement.
2. **Pay only for what you use.** An unused capability is not installed,
   initialized or represented by empty folders.
3. **Representative filesystem.** Project folders describe the application,
   not the framework.
4. **Progressive architecture.** Route and model may evolve into services,
   modules, Hexagonal Architecture, DDD or CQRS when complexity justifies it.
5. **Optional architecture.** Nexus can recommend patterns but does not impose
   them.
6. **Replaceable infrastructure.** Domain code depends on contracts, never on
   PostgreSQL, MongoDB, Redis, Kafka, RabbitMQ or gRPC directly.
7. **Measured performance.** Optimize after producing a reproducible benchmark.
8. **Developer experience with purpose.** A feature belongs only when it makes
   Nexus simpler, more powerful or measurably faster.
9. **Interoperability.** Follow modern PHP and applicable PSR standards where
   they create real ecosystem value.
10. **Stable evolution.** Semantic versioning and a backward-compatibility
    policy will protect public APIs from v1.0 onward.
