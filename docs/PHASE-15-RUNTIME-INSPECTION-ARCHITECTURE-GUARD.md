# Phase 15 — Runtime Inspection & Architecture Guard

## Objective

Make Nexus architecture observable and enforceable without coupling applications to a specific ORM, transport, or deployment model.

## First delivery

- `nexus architecture:check`
- recursive discovery of generated `src/**/module.json` manifests
- validation of module names and architecture identifiers
- validation of dependency-list shape
- detection of self-dependencies
- detection of duplicate module names
- detection of dependencies on unknown modules
- detection of circular module dependency graphs
- deterministic, CI-friendly success/failure exit codes

## Design

The first guard works from the stable module-manifest contract already emitted by `nexus make:module`. It does not parse PHP source code or guess architecture from folder names.

This gives CI a deterministic architecture gate while keeping the implementation dependency-light.

## Runtime inspection follow-up

Route inspection will be added only after generated applications expose a stable route-loading/runtime registry contract. `nexus routes` must report the same routes the running application sees, including groups and middleware; Nexus will not ship a regex-based source scanner that can silently omit routes.

Later increments will add:

- runtime route registry inspection
- middleware-chain inspection
- module boundary policies beyond graph correctness
- architecture-specific dependency rules for Hexagonal, Clean and DDD modules
- machine-readable output for CI and external tooling
- observability integration points
