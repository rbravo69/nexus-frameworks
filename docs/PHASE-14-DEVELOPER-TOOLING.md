# Phase 14 — Developer Tooling

## Objective

Improve day-to-day Nexus development without adding framework-wide runtime coupling.
This phase expands the existing CLI instead of creating parallel tooling systems.

## Generators

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

## Inspection and optimization

- `nexus config` prints the normalized `nexus.json` manifest using dot-notation keys.
- `nexus config project.type` reads an individual manifest value without booting the application.
- `nexus optimize` runs Composer authoritative classmap generation for production deployments.
- `nexus optimize:clear` removes only `.nexus/cache`, including renderer/runtime caches beneath it.
- `nexus doctor` now checks PHP, JSON, working-directory writability, `composer.json`, `nexus.json`, and `vendor/autoload.php`.

A `routes` command is intentionally deferred until generated applications expose a stable route-loading contract. Nexus will not ship a regex-based route scanner that can silently report incomplete runtime state.

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
- Config inspection reads project metadata only; it does not expose `.env` secrets.
- Optimization delegates Composer-specific work to Composer rather than reimplementing autoload behavior.
- Cache clearing is constrained to `.nexus/cache`.
- Existing CLI APIs remain backwards compatible.

## Verification

Focused tests cover generator registration and output, dot-notation config inspection, authoritative Composer optimization, cache cleanup boundaries, and the expanded doctor checks.
