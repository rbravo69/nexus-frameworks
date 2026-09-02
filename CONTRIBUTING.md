# Contributing to Nexus

Nexus is in its foundation stage. Before opening a large pull request, start a
discussion or RFC so public contracts remain intentional and small.

## Local checks

```bash
composer install
composer quality
```

Every behavior change must include tests. Core changes must not introduce a
dependency on HTTP, database, Redis, an ORM, or another optional capability.

Use focused commits and explain both the problem and the architectural tradeoff
in the pull request.

## Coding rules

- PHP 8.4+ with strict types.
- PSR-4 autoloading and PER Coding Style conventions.
- Final classes by default; use interfaces at real extension boundaries.
- Immutable value objects where practical.
- No optimization without a reproducible benchmark.

Substantial changes to public contracts follow the process in
[`docs/RFC_PROCESS.md`](docs/RFC_PROCESS.md).
