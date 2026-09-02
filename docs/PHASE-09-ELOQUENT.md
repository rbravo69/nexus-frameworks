# Fase 09 — Integración opcional con Eloquent

## Objetivo

Permitir usar Eloquent como capa de persistencia oficial opcional sobre Nexus sin convertirlo en una dependencia obligatoria del framework.

## Alcance

- Adaptación de `DatabaseConfig` a configuración de Illuminate.
- Registro de una o varias conexiones Eloquent.
- Boot explícito de Eloquent.
- Selección de conexión por nombre.
- Selección de conexión por modelo.
- PostgreSQL, MySQL y SQLite a través de la configuración ya soportada por Database Core.
- Integración probada con SQLite en memoria.

## Principios

Eloquent es una capability y no forma parte del núcleo obligatorio. El código de aplicación puede elegir Eloquent, SQL nativo, Repository/Mapper u otra estrategia de persistencia.

`illuminate/database` se mantiene como dependencia de desarrollo del monorepo para ejecutar tests y análisis estático, y como paquete sugerido para consumidores que habiliten esta integración.

## Fuera de alcance

Esta fase no implementa migraciones de Nexus, Code First, Database First, un ORM propio ni reemplaza las APIs nativas de Eloquent.

## Uso

```php
use Nexus\Database\DatabaseConfig;
use Nexus\Database\Eloquent\EloquentConfig;
use Nexus\Database\Eloquent\EloquentManager;

$eloquent = (new EloquentManager())
    ->addConnection(new EloquentConfig(
        new DatabaseConfig('pgsql', 'app', '127.0.0.1', 5432, 'user', 'password'),
        global: true,
    ))
    ->boot();
```

Los modelos siguen siendo modelos Eloquent normales y pueden usar relaciones, scopes, casts y demás capacidades del paquete Illuminate.
