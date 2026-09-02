# Phase 10 extension — SQL Server, Oracle and MongoDB

Nexus extends database support before moving to cache and messaging.

## Relational engines

`DatabaseConfig` and `ConnectionFactory` support:

- PostgreSQL (`pgsql`)
- MySQL (`mysql`)
- SQLite (`sqlite`)
- SQL Server (`sqlsrv`)
- Oracle (`oci`)

SQL Server uses PDO SQLSRV and Oracle uses PDO OCI. Their PHP extensions remain optional and are only required when those drivers are used.

The neutral Schema Model, Code First SQL compiler and Database First introspector also understand SQL Server and Oracle.

## MongoDB

MongoDB is intentionally not modeled as SQL/PDO. It is an optional document persistence capability under `Nexus\Database\Mongo`.

The initial capability provides:

- `MongoConfig`
- `MongoConnectionInterface`
- `MongoManager` with named connections
- document CRUD contract
- index creation contract

Concrete adapters may use the official `mongodb/mongodb` PHP library. The MongoDB extension and library are suggested dependencies rather than core requirements.

This separation preserves the rule that relational schema mechanics must not leak into document persistence.
