# RC-04 — MongoDB Adapter

## Objective

Turn the v0.1 MongoDB support from contracts-only infrastructure into an official optional adapter while preserving Nexus' pay-only-for-what-you-use rule.

## Runtime model

Nexus itself still does not require `ext-mongodb` or `mongodb/mongodb`. The official adapter checks for `MongoDB\\Client` only when a configured MongoDB connection is actually opened. Applications that use MongoDB install both optional requirements.

## Official adapter

`MongoLibraryConnection` adapts the official `mongodb/mongodb` library to `MongoConnectionInterface` and provides:

- insert, find, update and delete document operations;
- index creation;
- collection listing;
- index listing;
- named, lazily configured connections through `MongoManager`.

`MongoRepository` offers a small collection-oriented facade without introducing a custom ODM.

## Database First

MongoDB is schemaless, so Nexus does not fabricate a relational-style schema. `MongoIntrospector` conservatively returns collections and their indexes. Field inference from sampled documents is intentionally deferred until it can be explicit about confidence and sampling limits.

## Testing

The adapter is tested against a protocol-compatible fake client so the root CI remains installable without MongoDB extensions. A live MongoDB integration workflow is still required before the final v0.1 release claim is considered fully production-validated.
