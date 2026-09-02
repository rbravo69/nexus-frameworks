# Fase 07 — REST, Validation y OpenAPI

## Objetivo

Agregar una capa REST opcional y ligera sobre HTTP/Router sin acoplarla al ORM, la base de datos o frameworks externos.

## REST

- `ApiResponse::data()` para respuestas con envelope `data` y metadata opcional.
- `ApiResponse::created()` con HTTP 201 y `Location` opcional.
- `ApiResponse::noContent()` para HTTP 204.
- RFC Problem Details mediante `ProblemDetails` y `application/problem+json`.

## Validation

`Validator` funciona sobre arrays y no depende de modelos ni de persistencia.

Reglas iniciales:

- `required`
- `nullable`
- `string`
- `integer`
- `numeric`
- `boolean`
- `array`
- `email`
- `min:n`
- `max:n`
- `in:a,b,c`

El resultado separa datos validados y errores. `throwIfInvalid()` lanza `ValidationException`, que el HTTP Kernel convierte automáticamente en Problem Details 422.

## OpenAPI

`OpenApiGenerator` produce documentos OpenAPI 3.1 directamente desde el `Router`.

- rutas y métodos HTTP
- parámetros de path `{id}`
- `operationId` a partir del nombre de ruta
- metadatos opcionales mediante `#[Operation]`
- respuestas declaradas
- tags, summary y description
- esquema reusable `ProblemDetails`

Las rutas con closures siguen apareciendo en OpenAPI con metadata mínima. Los Attributes enriquecen el documento, pero no son obligatorios.

## Principios

- REST no entra al Core.
- Validation no depende de ORM ni HTTP.
- OpenAPI consume metadata del Router en lugar de crear un segundo sistema de rutas.
- Sin generación mágica de CRUD por defecto.
- Sin dependencias externas obligatorias en esta fase.
