# Phase 15 — Runtime Inspection & Architecture Guard

## Objective

Make Nexus architecture and HTTP runtime observable and enforceable without coupling applications to a specific ORM, transport, or deployment model.

## Architecture guard

- `nexus architecture:check`
- recursive discovery of generated `src/**/module.json` manifests
- validation of module names and architecture identifiers
- validation of dependency-list shape
- detection of self-dependencies
- detection of duplicate module names
- detection of dependencies on unknown modules
- detection of circular module dependency graphs
- deterministic, CI-friendly success/failure exit codes

## CLI entry points

When working directly from a cloned Nexus repository after `composer install`, use:

```bash
php nexus architecture:check
```

`php bin/nexus architecture:check` is equivalent.

When Nexus is installed as a Composer dependency in another project, use:

```bash
vendor/bin/nexus architecture:check
```

A repository clone is not expected to contain its own `vendor/bin/nexus`, because Composer only creates that proxy when Nexus is installed as a dependency of a consumer project.

## API runtime and interactive documentation

API projects now receive a runtime-backed HTTP stack from `Bootstrap`:

- `Router` registered in the application container
- `HttpKernel` registered in the application container
- OpenAPI 3.1 generated from the actual runtime `Router`
- `GET /openapi.json` for the machine-readable schema
- `GET /docs` for Swagger UI
- validation exception rendering attached to the HTTP kernel
- `nexus serve` routes requests through the Nexus HTTP runtime

Swagger/OpenAPI endpoints are mounted automatically only when `config/app.php` declares `type => api`. Non-API project types do not pay for or expose the docs endpoints by default.

The schema endpoint deliberately removes `/docs` and `/openapi.json` from the generated API document so framework documentation infrastructure does not appear as application business API surface.

Development usage:

```bash
vendor/bin/nexus serve
```

Then open:

```text
http://127.0.0.1:8000/docs
http://127.0.0.1:8000/openapi.json
```

Swagger UI is intentionally a presentation layer over Nexus OpenAPI metadata; the OpenAPI document remains the source of truth and is generated from the runtime router rather than by scanning PHP source files.

## Design

The architecture guard works from the stable module-manifest contract already emitted by `nexus make:module`. It does not parse PHP source code or guess architecture from folder names.

Runtime HTTP inspection follows the same principle: Nexus inspects the real `Router` used by `HttpKernel`, rather than maintaining a second route description or a regex-based source scanner.

This gives CI deterministic architecture gates while keeping runtime inspection aligned with what the application actually executes.

## Follow-up

Later increments will add:

- `nexus routes` over the same runtime Router
- middleware-chain inspection
- richer OpenAPI request/response schemas and security schemes
- module boundary policies beyond graph correctness
- architecture-specific dependency rules for Hexagonal, Clean and DDD modules
- machine-readable architecture output for CI and external tooling
- observability integration points
