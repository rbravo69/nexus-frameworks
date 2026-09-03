# Roadmap

## v0.1 — Foundation

- Core, lifecycle, configuration and module registry ✅
- Dependency injection container ✅
- CLI and project generation ✅
- Capability installation and runtime loading ✅
- Modules and architectural presets ✅
- HTTP and routing ✅
- REST, validation and OpenAPI ✅
- Database core, PostgreSQL, MySQL and SQLite ✅
- SQL Server and Oracle relational database support ✅
- Optional MongoDB document persistence ✅
- Optional Eloquent integration ✅
- Migrations, Code First and basic Database First ✅
- Extend Schema, migrations and Database First to SQL Server and Oracle ✅
- MongoDB collections, document repositories, indexes and basic Database First ✅
- Cache core and optional Redis ✅
- CQRS and events ✅
- Seeders, factories and fake data ✅
- Optional Docker integration ✅
- Testing, static analysis and benchmarks ✅

Current support boundaries and validation levels are defined in
[SUPPORT_MATRIX.md](SUPPORT_MATRIX.md). This roadmap describes delivery status
and future direction; it is not, by itself, a runtime support contract.

## v0.1 — Release Candidate Hardening

- RC-01 Architectural Hardening ✅
- RC-02 Package & Capability Model ✅
- RC-03 Validation, Cache and Redis hardening ✅
- RC-04 MongoDB concrete adapter and real persistence ✅
- RC-05 Relational integration matrix ✅
- RC-06 Production-ready Docker runtimes ✅
- RC-07 CI, dependency audit and coding standards ✅
- RC-08 Documentation and support-claim reconciliation
- RC-09 v0.1.0-rc.1
- RC-10 v0.1.0 release validation

## After v0.1

- Advanced MongoDB capabilities and transactions where supported
- Async, gRPC and messaging
- RabbitMQ, Kafka and SQS adapters
- Advanced relational introspection: views, materialized views, procedures, functions, triggers and sequences
- Architecture Guard and observability
- Smart project wizard and developer tooling

Reporting (PDF, CSV and TXT), ORM-independent pagination, and mail are planned
as optional packages.
