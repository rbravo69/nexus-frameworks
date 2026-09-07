# Phase 14 — Developer Tooling

## Objective

Improve day-to-day Nexus development without adding framework-wide runtime coupling.
The first delivery expands the existing CLI generator model instead of creating a second scaffolding system.

## Delivered

- `nexus make:service <name>`
- `nexus make:repository <name>`
- `nexus make:middleware <name>`
- `nexus make:request <name>`
- `nexus make:event <name>`
- `nexus make:listener <name>`
- Existing `make:module`, `make:controller` and `make:model` remain compatible.
- All make commands are registered from `GeneratorType`, so adding a new artifact type does not require duplicating factory registration.
- Middleware scaffolds implement the Nexus HTTP middleware contract and pass through to the next handler by default.
- Listener scaffolds expose an invokable event entry point without coupling generated applications to a specific event payload type.
- Generated files continue to use the existing `Filesystem` safeguards and are never silently overwritten.

## Generated locations

| Command | Default path |
| --- | --- |
| `make:controller` | `src/Controller/*Controller.php` |
| `make:model` | `src/Model/*.php` |
| `make:service` | `src/Service/*Service.php` |
| `make:repository` | `src/Repository/*Repository.php` |
| `make:middleware` | `src/Http/Middleware/*Middleware.php` |
| `make:request` | `src/Http/Request/*Request.php` |
| `make:event` | `src/Event/*Event.php` |
| `make:listener` | `src/Event/Listener/*Listener.php` |

## Design constraints

- Dependency-light: generators emit plain PHP unless a Nexus contract materially improves correctness.
- No ORM assumption in repository scaffolds.
- No validator implementation is forced into request scaffolds.
- No queue or async dependency is forced into events/listeners.
- Existing CLI APIs remain backwards compatible.

## Follow-up

Later Phase 14 increments can add runtime inspection and optimization commands such as route/config inspection, cache optimization and richer diagnostics once their stable runtime contracts are defined.
