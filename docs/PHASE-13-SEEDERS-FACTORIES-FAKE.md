# Phase 13 — Seeders, Factories and Fake Data

Nexus provides ORM-independent data seeding primitives.

## Seeders

`SeederInterface` receives a `SeederContext` containing the scenario, environment, deterministic random seed and an optional persistence store. `SeederRunner` executes seeders in registration order.

Supported scenarios are `minimal`, `dev`, `test`, `demo`, `qa`, `performance`, `stress` and `full`.

## Factories

Factories extend `Nexus\Factory\Factory` and define one record from `FakeGenerator`. The same factory can produce one or many records and remains independent of Eloquent or any database driver.

## Fake data

`FakeGenerator` is deterministic when initialized with the same seed. The initial API includes integers, booleans, selections, names, emails and words. It intentionally avoids global random state so test suites remain reproducible.

## Persistence

`SeedStoreInterface` is the persistence boundary. Official initial adapters are:

- `SqlSeedStore` for Nexus relational connections.
- `MongoSeedStore` for Nexus MongoDB connections.

This allows seeders and factories to be reused across SQL, MongoDB, tests and future adapters without depending on an ORM.

Future work can enrich generation from Schema metadata, relationships, unique constraints, enums and locale-aware providers without changing the core contracts.
