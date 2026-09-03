# RC-05 — Relational Integration Matrix

## Objective

Validate Nexus Database Core against real relational engines in CI, not only DSN/unit-level doubles.

## Live engines

- PostgreSQL 17 via `postgres:17`
- MySQL 8.4 via `mysql:8.4`
- SQL Server 2022 via Microsoft's official Linux container
- Oracle Free 23 via `gvenzl/oracle-free:23-slim-faststart`

## Probe

`tests/integration/relational.php` exercises the same `DatabaseConfig`, `ConnectionFactory` and `PdoConnection` used by Nexus applications. For every engine it verifies:

1. real connection and health query;
2. DDL table creation;
3. prepared insert;
4. select/fetch normalization;
5. table cleanup.

The workflow installs only the PDO driver needed by each job. This preserves the Nexus rule that optional database engines never become mandatory runtime dependencies.

## Scope

RC-05 validates connectivity and the common Database Core contract against real engines. It does not claim exhaustive coverage of every vendor-specific schema feature. Advanced views, procedures, functions, triggers and sequences remain post-v0.1 work.
