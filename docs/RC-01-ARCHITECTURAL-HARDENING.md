# RC-01 — Architectural Hardening

This release-candidate hardening pass repairs architectural boundaries found during the v0.1 audit.

## Changes

- Capability manifest ownership moved from `Nexus\Cli` to `Nexus\Capability` so application bootstrap no longer depends on CLI code.
- Process execution now has a framework-level contract in `Nexus\Contracts`; the CLI interface remains as a deprecated compatibility alias.
- `HttpKernel` no longer depends directly on REST or Validation. Optional exception rendering is provided through `ExceptionRendererInterface`.
- REST owns the Validation-to-Problem-Details adapter through `ValidationExceptionRenderer`.
- PDO is no longer a mandatory dependency of the framework package. It is suggested only for relational database support.
- Architecture tests now enforce the Core/CLI and HTTP/REST/Validation boundaries.

## Compatibility

The public `Nexus\Cli\ProcessRunnerInterface` remains available as a deprecated compatibility alias during the pre-1.0 cycle. `CapabilityManifest` moved to `Nexus\Capability\CapabilityManifest` because the previous namespace incorrectly made the application runtime depend on the CLI layer.

## Follow-up

RC-02 will address the package/capability model so the monorepo and optional Composer packages follow one coherent installation strategy.
