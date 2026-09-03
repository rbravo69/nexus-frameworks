# RC-07 — CI, Dependency Audit and Coding Standards

RC-07 turns Nexus quality expectations into reproducible local and CI gates.

## Goals

- Keep PHPUnit and PHPStan level max green on PHP 8.4 and PHP 8.5.
- Enforce one project-wide PHP coding standard.
- Fail CI when Composer reports known vulnerable dependencies.
- Expose the same checks through Composer scripts for local development.

## Coding standard

Nexus uses PHP-CS-Fixer with PER-CS 2.0 as the baseline plus a small set of deterministic rules:

- short array syntax;
- strict types declarations;
- removal of unused imports;
- alphabetically ordered imports;
- single-quoted strings when semantics allow it.

Commands:

```bash
composer cs:check
composer cs:fix
```

`cs:check` never modifies files and is the CI gate. `cs:fix` is the developer convenience command.

## Dependency audit

```bash
composer audit --no-interaction
```

The audit includes installed production and development dependencies. A reported security advisory fails the standards job.

## Quality scripts

```bash
composer quality
composer verify
```

`quality` runs PHPUnit and PHPStan. `verify` additionally runs coding standards and the dependency audit.

## CI layout

The Quality workflow has two responsibilities:

1. `quality` matrix on PHP 8.4 and 8.5: Composer validation, PHPUnit, PHPStan max and benchmark smoke.
2. `standards` on PHP 8.5: Composer validation, PER-CS check and dependency audit.

Database integration and Docker runtime workflows remain independent gates so failures stay attributable to the subsystem that caused them.
