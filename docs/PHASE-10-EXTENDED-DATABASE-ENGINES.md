# Phase 10 extension — SQL Server, Oracle and MongoDB

Nexus extends database support before moving to cache and messaging.

> Historical note: this phase originally introduced MongoDB contracts only.
> RC-04 later added the concrete official-library adapter. Current support is
> summarized in [SUPPORT_MATRIX.md](SUPPORT_MATRIX.md).

## Relational engines

`DatabaseConfig` and `ConnectionFactory` support:

- PostgreSQL (`pgsql`)
- MySQL (`mysql`)
- SQLite (`sqlite`)
- SQL Server (`sqlsrv`)
- Oracle (`oci`)

SQL Server uses PDO SQLSRV and Oracle uses PDO OCI. Their PHP extensions remain
optional and are only required when those drivers are used.

The neutral Schema Model, Code First SQL compiler and basic Database First
introspector also understand SQL Server and Oracle. RC-05 adds live CI probes
for PostgreSQL, MySQL, SQL Server and Oracle. SQLite remains covered through
in-memory/unit integration tests.

This does not imply support for every advanced database object. Views,
materialized views, procedures, functions, triggers and sequences remain later
introspection work unless specifically documented otherwise.

## Eloquent boundary

Nexus Database Core support and Eloquent driver support are separate claims.
SQL Server can be mapped through Illuminate's native connection configuration.
Oracle is supported by Nexus Database Core, but `illuminate/database` does not
provide a native Oracle driver; applications that want Eloquent on Oracle must
select an explicit compatible third-party adapter.

## MongoDB

MongoDB is intentionally not modeled as SQL/PDO. It is an optional document
persistence capability under `Nexus\Database\Mongo`.

The Phase 10 foundation introduced:

- `MongoConfig`;
- `MongoConnectionInterface`;
- `MongoManager` with named connections;
- document CRUD and index contracts.

RC-04 subsequently added `MongoLibraryConnection` over the official
`mongodb/mongodb` package, collection/index listing, `MongoRepository` and
conservative collection/index introspection through `MongoIntrospector`.

Nexus does not claim a custom ODM or inferred relational schema for MongoDB.
`ext-mongodb` and `mongodb/mongodb` remain optional dependencies, loaded only by
applications that use the MongoDB adapter.

The current release-candidate validation uses protocol-compatible test doubles
for the MongoDB adapter. A live MongoDB CI service is not yet part of the
verified matrix, so MongoDB must not be described as live-CI validated.
