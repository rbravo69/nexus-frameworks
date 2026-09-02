# Fase 11 — Cache y Redis

## Objetivo

Agregar una capa de cache independiente de infraestructura y una capability Redis reutilizable sin convertir Redis en una dependencia global del framework.

## Cache Core

- `CacheInterface`
- `ArrayCache`
- `FilesystemCache`
- TTL con segundos o `DateInterval`
- `remember()`
- `get`, `set`, `delete`, `has`, `clear`

## Redis

- `RedisClientInterface`
- `RedisCache`
- prefijos de keys
- TTL
- locks distribuidos con token de propietario
- Redis queda preparado para futuros usos en queue, rate limiting, sesiones y pub/sub

## Dependencias

Redis es opcional. Los adaptadores concretos pueden usar `ext-redis` o `predis/predis` sin obligar al Core a instalar ninguno.

## Decisiones

- Cache no depende de Redis.
- Redis no se limita a cache.
- Los locks solo pueden liberarse con el token que adquirió el lock.
- Las implementaciones locales permiten desarrollo y tests sin infraestructura externa.
