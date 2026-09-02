# Fase 10 — Migrations, Code First y Database First básico

## Objetivo

Agregar una capa de esquema neutral e independiente del ORM para que Nexus pueda evolucionar bases de datos desde código, inspeccionar una base existente y ejecutar migraciones versionadas.

## Schema Model

`Schema`, `Table` y `Column` representan el esquema deseado sin depender de Eloquent, Doctrine ni un motor específico.

## Code First

`CodeFirst` inspecciona la base actual, compara contra el `Schema` deseado y genera operaciones. En esta primera versión soporta:

- crear tablas;
- eliminar tablas;
- agregar columnas;
- eliminar columnas;
- generar SQL para PostgreSQL, MySQL y SQLite;
- bloquear operaciones destructivas salvo aprobación explícita.

Los cambios de tipo, rename, índices, foreign keys y constraints avanzados se agregan después para evitar inferencias peligrosas.

## Database First básico

`SchemaIntrospector` obtiene tablas y columnas y las convierte al mismo Schema Model. SQLite usa `sqlite_master` y `PRAGMA table_info`; PostgreSQL y MySQL usan `information_schema.columns`.

## Migrations

`Migration` define `id()`, `up()` y `down()`. `MigrationRunner` mantiene `nexus_migrations`, aplica únicamente migraciones pendientes, usa batches y permite rollback del último batch.

## Principio

La base de datos es una capability de Nexus. El esquema pertenece a Nexus Database, no al ORM elegido por la aplicación.
