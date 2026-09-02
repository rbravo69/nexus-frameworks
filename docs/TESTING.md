# Testing, Static Analysis and Benchmarks

Phase 15 closes the Nexus v0.1 foundation with executable quality gates.

## Testing

The default suite uses PHPUnit and remains a development-only dependency.

```bash
composer test
```

Architecture-focused tests can also be run independently:

```bash
composer test:architecture
```

The first architecture rule protects Foundation namespaces from accidental dependencies on optional infrastructure such as Docker and Eloquent.

## Static analysis

PHPStan runs at `level: max` over both `src` and `tests`:

```bash
composer analyse
```

The complete local quality gate is:

```bash
composer quality
```

## Benchmarks

Nexus includes a small benchmark harness based on `hrtime()` rather than tying the framework to a benchmark package.

```bash
php bin/nexus benchmark
php bin/nexus benchmark --iterations=10000 --warmup=500
composer benchmark
```

The initial suite measures a PHP no-op baseline and cached container resolution. Results report average nanoseconds per operation, operations per second, minimum, maximum and iteration count.

Benchmarks are diagnostic data, not release promises. Performance regressions should be investigated against comparable hardware, PHP versions and runtime settings.

## CI

GitHub Actions validates PHP 8.4 and 8.5 with:

1. strict Composer metadata validation;
2. PHPUnit;
3. PHPStan max;
4. a lightweight benchmark smoke test.

Testing and benchmarking tooling never becomes a production runtime dependency.
