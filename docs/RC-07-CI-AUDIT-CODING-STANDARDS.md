# RC-07 — CI, Dependency Audit and Coding Standards

RC-07 turns Nexus quality expectations into reproducible local and CI gates.

## Goals

- Keep PHPUnit and PHPStan level max green on PHP 8.4 and PHP 8.5.
- Enforce one project-wide PHP coding standard.
- Fail CI when Composer reports known vulnerable dependencies.
- Expose the same checks through Composer scripts for local development.

## Coding standard

Nexus uses PHP-CS-Fixer with the current `@PER-CS2x0` rule set as the baseline. A few explicit compatibility overrides preserve established Nexus formatting instead of creating a repository-wide mechanical rewrite:

- empty bodies remain multiline;
- short arrow functions keep one space before `(`;
- anonymous-class constructor arguments keep the existing spacing;
- function opening braces keep the established Nexus multiline style;
- multiline calls containing heredoc keep their existing layout;
- heredoc arguments are not forced to gain trailing commas.

Strict type declarations remain enforced.

Commands:

```bash
composer cs:check
composer cs:fix
```

`cs:check` never modifies files and is the CI gate. `cs:fix` is the developer convenience command.

## Dependency audit

```bash
composer security:audit
```

The script delegates to Composer's native `audit --no-interaction` command. The audit includes installed production and development dependencies. A reported security advisory fails the standards job.

## Quality scripts

```bash
composer quality
composer verify
```

`quality` runs PHPUnit and PHPStan. `verify` additionally runs coding standards and the dependency audit.

## CI layout

The Quality workflow has two responsibilities:

1. `quality` matrix on PHP 8.4 and 8.5: Composer validation, PHPUnit, PHPStan max and benchmark smoke.
2. `standards` on PHP 8.4: Composer validation, PER-CS check and dependency audit. Running the formatter on the minimum supported PHP version prevents it from normalizing code toward syntax unavailable to that minimum.

Database integration and Docker runtime workflows remain independent gates so failures stay attributable to the subsystem that caused them.
