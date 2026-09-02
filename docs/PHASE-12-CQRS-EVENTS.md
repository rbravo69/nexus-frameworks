# Fase 12 — CQRS y Events

## Objetivo

Agregar mensajería síncrona y explícita para comandos, consultas y eventos sin obligar a adoptar CQRS en toda la aplicación y sin mezclarlo con Event Sourcing, colas o transporte distribuido.

## Componentes

### Command Bus

`Nexus\Cqrs\CommandBus` registra una clase de comando con exactamente un handler invokable. El handler se resuelve por PSR-11, por lo que puede recibir dependencias mediante el contenedor de Nexus.

### Query Bus

`Nexus\Cqrs\QueryBus` separa lecturas de comandos y devuelve el resultado producido por su handler. También mantiene un único handler por tipo de query.

### Event Bus

`Nexus\Events\EventBus` permite múltiples listeners por evento y los ejecuta en orden de registro. Los listeners se resuelven desde el contenedor y son invocables.

## Decisiones

- CQRS es opcional y puede utilizarse por módulo.
- Command y Query son buses distintos.
- Event Bus es independiente de CQRS.
- No se implementa Event Sourcing en esta fase.
- No se implementa ejecución asíncrona en esta fase.
- Queue y mensajería distribuida podrán adaptarse posteriormente sin cambiar los contratos de aplicación.
- Los mapas de handlers/listeners son inspeccionables para futuros comandos como `cqrs:list` y `events:list`.

## Ejemplo

```php
$commands->register(CreateOrder::class, CreateOrderHandler::class);
$commands->dispatch(new CreateOrder($data));

$queries->register(FindOrder::class, FindOrderHandler::class);
$order = $queries->ask(new FindOrder($id));

$events->listen(OrderCreated::class, SendConfirmation::class);
$events->dispatch(new OrderCreated($id));
```

## Fuera de alcance

- Event Store
- aggregates con replay
- brokers
- retries y DLQ
- handlers asíncronos
- middleware/pipeline avanzado

Esas capacidades deben agregarse después como módulos o adaptadores, no como carga obligatoria del core.
