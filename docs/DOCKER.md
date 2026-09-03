# Docker — Fase 14

Docker es una capability opcional. Nexus no requiere contenedores para ejecutar
una aplicación.

## Inicialización

```bash
nexus docker:init --runtime=frankenphp --services=postgres,redis,mailpit
```

Runtimes generados actualmente: `frankenphp`, `php-fpm-nginx`, `roadrunner`,
`openswoole`.

Servicios seleccionables en Compose: PostgreSQL, MySQL, Redis, MongoDB, SQL
Server, RabbitMQ, Kafka y Mailpit.

La presencia de un servicio en el generador Docker significa que Nexus puede
generar su topología Compose; **no** significa que el framework tenga un adapter
de aplicación de primera clase para todos ellos. En particular, RabbitMQ y
Kafka siguen siendo trabajo futuro del subsistema de mensajería.

Comandos operativos actuales:

```bash
nexus docker:init
nexus docker:up
nexus docker:down
nexus docker:restart
nexus docker:status
nexus docker:logs
```

## Desarrollo y producción

El modo de desarrollo monta el código fuente y publica puertos de infraestructura
en `127.0.0.1`. El modo `--production` genera una topología distinta: elimina
los bind mounts de desarrollo, evita publicar por defecto puertos de bases de
datos/cache, usa instalación Composer de producción y añade políticas de
reinicio donde corresponde.

Los cuatro runtimes tienen CI de **generación, `docker compose config` y build**.
Eso valida que la topología generada sea construible; no equivale a afirmar que
una aplicación concreta fue desplegada, sometida a carga o endurecida para cada
proveedor de infraestructura.

RoadRunner además requiere que el `public/index.php` de la aplicación exponga el
contrato callable esperado por el worker generado. OpenSwoole usa su propio
servidor HTTP generado. Estos contratos de entrada deben validarse en la
aplicación consumidora.

Los secretos, límites de recursos, TLS, networking, observabilidad, backups y
políticas del proveedor siguen siendo responsabilidad del despliegue.

Principio: Docker facilita desarrollo y despliegue; nunca es una dependencia del
Core.

Consulta también [RC-06](RC-06-PRODUCTION-DOCKER-RUNTIMES.md) y la
[matriz de soporte verificado](SUPPORT_MATRIX.md).
