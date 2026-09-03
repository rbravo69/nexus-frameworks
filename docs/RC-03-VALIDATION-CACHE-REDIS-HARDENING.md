# RC-03 — Validation, Cache and Redis Hardening

## Objective

Close three release-candidate risks found during the v0.1 audit without expanding runtime responsibilities.

## Validation

String length rules no longer assume `ext-mbstring` is installed. Nexus uses `mb_strlen()` when available and a Unicode-aware PCRE fallback otherwise.

## Cache serialization

Filesystem and Redis cache now depend on `SerializerInterface`. The default `PhpSerializer` disables class hydration during unserialization (`allowed_classes=false`). Applications that intentionally cache trusted objects may inject a serializer configured with an explicit class allow-list.

## Redis clearing

`RedisCache::clear()` no longer delegates to a global client clear operation. `RedisClientInterface` exposes `deleteByPrefix()` and the cache only clears its configured namespace. This prevents cache clearing from deleting queues, locks, sessions, rate limits, or unrelated application keys.

## Compatibility

This is a pre-1.0 hardening change. Implementations of `RedisClientInterface` must replace `clear()` with `deleteByPrefix(string $prefix)`.
