# Docker — Fase 14

Docker es una capability opcional. Nexus no requiere contenedores para ejecutar una aplicación.

## Inicialización

```bash
nexus docker:init --runtime=frankenphp --services=postgres,redis,mailpit
```

Runtimes soportados: `frankenphp`, `php-fpm-nginx`, `roadrunner`, `openswoole`.

Servicios seleccionables: PostgreSQL, MySQL, Redis, MongoDB, SQL Server, RabbitMQ, Kafka y Mailpit.

Comandos operativos iniciales:

```bash
nexus docker:up
nexus docker:down
nexus docker:restart
nexus docker:status
nexus docker:logs
```

`--production` agrega un perfil documental para despliegue, pero los secretos, límites de recursos y políticas de infraestructura deben revisarse antes de producción.

Principio: Docker facilita desarrollo y despliegue; nunca es una dependencia del Core.
