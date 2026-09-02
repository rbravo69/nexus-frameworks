# Capabilities

Capabilities keep optional infrastructure outside Nexus Core. A project records
its selection in `nexus.json` and Composer owns the corresponding packages.

```bash
nexus add redis
nexus remove redis
```

`add` resolves dependencies first, installs their packages and writes the full
selection only after successful installation. `remove` refuses to break an
installed dependent and restores the manifest if Composer fails.

## Provider contract

Every capability package exposes a provider implementing
`Nexus\Contracts\CapabilityInterface`:

```php
final class ExampleCapability implements CapabilityInterface
{
    public function register(Application $application): void {}
    public function boot(Application $application): void {}
    public function shutdown(Application $application): void {}
}
```

Definitions connect the public name with its package, provider and dependencies:

```php
new CapabilityDefinition(
    name: 'cache',
    package: 'nexus/cache',
    provider: CacheCapability::class,
    dependencies: ['redis'],
);
```

Applications may provide their own `CapabilityCatalog` to `Bootstrap::create()`.
Nexus resolves selected providers through its PSR-11 container and runs them in
dependency order. Providers absent from `nexus.json` are never loaded.

The official catalog initially reserves `redis` for the future `nexus/redis`
package. This phase implements its lifecycle; the Redis adapter itself belongs
to the Redis and Cache phase.
