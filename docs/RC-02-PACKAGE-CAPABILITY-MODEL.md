# RC-02 — Package & Capability Model

## Decision

Nexus v0.1 keeps a single distributable Composer package, `nexus/framework`, while preserving capability-level runtime selection.

Official v0.1 capabilities are therefore **bundled**: their code ships with `nexus/framework`, and `nexus add <capability>` only enables the capability in `nexus.json`. It must not execute `composer require nexus/<capability>` for packages that do not exist yet.

The capability model also supports **Composer-distributed** capabilities. Those definitions may install/remove an external Composer package through the existing `PackageManagerInterface`.

This gives Nexus two explicit distribution modes:

- `bundled`: code is already present in the installed Nexus distribution;
- `composer`: code lives in a separately installable Composer package.

The developer-facing command does not need to change if a bundled capability is split into its own package later.

## Official bundled capabilities in v0.1

- `database`
- `eloquent` → depends on `database`
- `mongo`
- `cache`
- `redis` → depends on `cache`
- `cqrs`
- `events`
- `docker`

All currently resolve to the `nexus/framework` distribution and a valid bundled lifecycle provider.

## Why this model

The previous catalog declared `redis` as package `nexus/redis` with provider `Nexus\\Redis\\RedisCapability`, although neither the package nor provider existed. That made `nexus add redis` conceptually inconsistent with the repository layout.

RC-02 makes installed distribution and runtime activation separate concepts:

```text
Composer installation            Nexus activation
---------------------            ----------------
nexus/framework        +         nexus add redis
                                 nexus add database
                                 nexus add eloquent
```

For a future separately distributed capability:

```text
nexus add vendor-feature
  -> composer require vendor/package
  -> persist capability in nexus.json
  -> load its provider at runtime
```

## Package split policy

Splitting official capabilities into separate Composer packages is intentionally deferred until there is a concrete release/maintenance benefit. When that happens, the capability definition changes from `Bundled` to `Composer`; the CLI contract remains the same.

This avoids premature repository fragmentation while preventing Nexus from pretending that unpublished packages already exist.
