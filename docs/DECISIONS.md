# Architectural decisions

## Accepted

- The core is pure PHP 8.4+.
- Nexus recommends architecture; the developer decides.
- Capabilities are optional packages and do not create unused project files.
- Eloquent is a first-class optional integration, not a core dependency.
- Code First, Database First and hybrid workflows are planned.
- PostgreSQL, MySQL and SQLite are the first relational adapters.
- MongoDB is optional document persistence; Redis is optional cross-cutting
  infrastructure.
- CQRS is optional and does not imply Event Sourcing, messaging or async.
- Docker integration is official but never required to run Nexus.
- The base framework is open source under the MIT License.
- Seeders, factories and fake data belong to the v0.1 database/testing scope.
- Reporting, pagination and mail are independent of a specific ORM or provider.
- Complex optimizations require benchmarks that demonstrate their need.

This file records durable decisions. Significant amendments require an RFC.
