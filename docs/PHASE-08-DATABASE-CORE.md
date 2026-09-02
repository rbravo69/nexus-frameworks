# Fase 08 — Database Core

## Objetivo

Crear una capa de acceso a datos mínima, explícita y desacoplada de cualquier ORM.

## Alcance

- contrato `ConnectionInterface`
- implementación PDO
- configuración tipada con `DatabaseConfig`
- soporte inicial para PostgreSQL, MySQL y SQLite
- DSN con valores por defecto seguros
- consultas preparadas para lectura y escritura
- transacciones con commit y rollback automáticos
- múltiples conexiones mediante `DatabaseManager`
- conexiones lazy y posibilidad de desconectar/recrear
- excepción de base de datos propia sin exponer mensajes PDO al consumidor

## Fuera de alcance

Esta fase no incluye Query Builder, ORM, migrations, schema builder ni Database First. Esas capacidades se construyen encima del Database Core en fases posteriores.

## Principio

La aplicación puede usar SQL nativo o cualquier capa de persistencia futura sin que el núcleo de Nexus dependa de Eloquent, Doctrine u otro ORM.
