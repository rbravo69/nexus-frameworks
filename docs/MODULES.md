# Modules

Nexus modules are independent business boundaries. Each module chooses the
least architecture it needs and may evolve without forcing the rest of the
application to adopt the same structure.

## Generate a module

```bash
nexus make:module Catalog
nexus make:module Booking --architecture=hexagonal --depends=catalog,identity
nexus make:module Reporting --architecture=cqrs --depends=booking
```

Available architectures:

- `minimal`: module provider and metadata only.
- `mvc`: controller, model and view.
- `layered`: application, domain and infrastructure.
- `modular`: public contract and internal implementation.
- `hexagonal`: domain, application, port and adapter.
- `clean`: entity, use case, interface adapter and framework boundary.
- `ddd`: aggregate, repository, application service and infrastructure.
- `cqrs`: commands, queries and their handlers.
- `custom`: module provider and metadata, ready for a user-defined structure.

Every generated module contains `module.json` with its architecture and direct
dependencies. Directories are created only as a consequence of writing a real
file; presets never emit empty folders.

## Runtime order

Modules with dependencies implement `DependentModuleInterface`. Generated
modules use `ArchitecturalModuleInterface`, which also exposes their selected
`ModuleArchitecture`.

Before registration, Nexus resolves the complete graph. Dependencies register
and boot before their dependents. Shutdown uses the reverse order. Missing
dependencies and cycles fail before any module executes:

```text
catalog -> checkout -> catalog
```

Plain `ModuleInterface` implementations remain valid and are treated as modules
without dependencies, preserving the minimal starting point.
