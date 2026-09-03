# RC-08 — Documentation and support-claim reconciliation

## Objective

Make the public documentation describe what Nexus actually implements and
validates today, not what the long-term architecture intends to provide.

## Canonical support snapshot

`SUPPORT_MATRIX.md` is the release-candidate source of truth for support claims.
It separates:

- implemented code;
- live-service CI validation;
- Docker build/topology CI validation;
- planned roadmap work.

The roadmap remains useful for direction, but a roadmap item is not evidence of
runtime support.

## Reconciled boundaries

### Database

- PostgreSQL, MySQL, SQLite, SQL Server and Oracle are supported by relational
  Database Core at the documented basic schema/Code First/Database First level.
- PostgreSQL, MySQL, SQL Server and Oracle have real-service CI probes.
- SQLite is validated in memory/unit integration tests.
- Eloquent is optional and follows Illuminate driver support. Nexus does not
  claim native Eloquent-on-Oracle support.
- MongoDB now has the official-library adapter added in RC-04, but its current
  CI validation is adapter-level rather than a live MongoDB service workflow.

### Docker

FrankenPHP, PHP-FPM + Nginx, RoadRunner and OpenSwoole are generated as distinct
runtime topologies. CI validates Compose configuration and image builds. This is
not presented as proof of an application-specific production deployment or load
test.

Infrastructure services available in Docker generation do not automatically
become first-class Nexus application adapters. RabbitMQ and Kafka remain future
messaging work.

### HTTP and project presets

The HTTP/routing stack is implemented. The `gRPC service` project-generation
preset expresses project intent only; v0.1 does not yet contain a gRPC transport
runtime.

### CQRS and events

Current buses are synchronous. Nexus does not claim Event Sourcing, queue-backed
events, brokers, retries or DLQ behavior in v0.1.

## Files reconciled

- `README.md`
- `docs/ARCHITECTURE.md`
- `docs/DOCKER.md`
- `docs/PHASE-10-EXTENDED-DATABASE-ENGINES.md`
- `docs/SUPPORT_MATRIX.md`
- `docs/ROADMAP.md`

## Release rule

Before `v0.1.0-rc.1`, new public support claims should satisfy all three:

1. implementation exists in the repository;
2. tests validate the stated contract;
3. documentation identifies any important CI/runtime boundary.

Claims that fail those conditions remain roadmap language rather than current
support language.
