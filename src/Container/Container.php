<?php

declare(strict_types=1);

namespace Nexus\Container;

use Closure;
use Nexus\Contracts\ContainerInterface;
use Nexus\Exception\CircularDependencyException;
use Nexus\Exception\InactiveScopeException;
use Nexus\Exception\ScopeStateException;
use Nexus\Exception\ServiceNotFoundException;
use Nexus\Exception\UnresolvableDependencyException;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

final class Container implements ContainerInterface
{
    /** @var array<string, Binding> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $singletons = [];

    /** @var array<string, array<string, mixed>> */
    private array $scopedInstances = [];

    /** @var array<string, true> */
    private array $activeScopes = [];

    /** @var list<string> */
    private array $resolutionStack = [];

    public function __construct()
    {
        $this->instance(self::class, $this);
        $this->instance(ContainerInterface::class, $this);
        $this->instance(PsrContainerInterface::class, $this);
    }

    public function bind(
        string $abstract,
        string $concrete,
        Scope $scope = Scope::Transient,
    ): self {
        $this->assertIdentifier($abstract);
        $this->assertIdentifier($concrete);
        $this->bindings[$abstract] = new Binding($concrete, $scope);
        $this->forgetResolved($abstract);

        return $this;
    }

    public function factory(
        string $abstract,
        callable $factory,
        Scope $scope = Scope::Transient,
    ): self {
        $this->assertIdentifier($abstract);
        $this->bindings[$abstract] = new Binding(Closure::fromCallable($factory), $scope);
        $this->forgetResolved($abstract);

        return $this;
    }

    public function singleton(string $abstract, ?string $concrete = null): self
    {
        return $this->bind($abstract, $concrete ?? $abstract, Scope::Singleton);
    }

    public function transient(string $abstract, ?string $concrete = null): self
    {
        return $this->bind($abstract, $concrete ?? $abstract);
    }

    public function requestScoped(string $abstract, ?string $concrete = null): self
    {
        return $this->bind($abstract, $concrete ?? $abstract, Scope::Request);
    }

    public function workerScoped(string $abstract, ?string $concrete = null): self
    {
        return $this->bind($abstract, $concrete ?? $abstract, Scope::Worker);
    }

    public function instance(string $abstract, mixed $instance): self
    {
        $this->assertIdentifier($abstract);
        unset($this->bindings[$abstract]);
        $this->forgetResolved($abstract);
        $this->singletons[$abstract] = $instance;

        return $this;
    }

    public function get(string $id): mixed
    {
        $this->assertIdentifier($id);

        if (array_key_exists($id, $this->singletons)) {
            return $this->singletons[$id];
        }

        if (isset($this->bindings[$id])) {
            return $this->resolveBinding($id, $this->bindings[$id]);
        }

        if (!$this->isInstantiableClass($id)) {
            throw ServiceNotFoundException::for($id);
        }

        return $this->resolve($id, fn (): object => $this->autowire($id));
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function make(string $id): object
    {
        $service = $this->get($id);

        if (!is_object($service)) {
            throw new \UnexpectedValueException(sprintf(
                'Service "%s" did not resolve to an object.',
                $id,
            ));
        }

        /** @var T $service */
        return $service;
    }

    public function has(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        return array_key_exists($id, $this->singletons)
            || isset($this->bindings[$id])
            || $this->isInstantiableClass($id);
    }

    public function lazy(string $id): Lazy
    {
        return new Lazy(fn (): mixed => $this->get($id));
    }

    public function beginScope(Scope $scope): void
    {
        $this->assertContextualScope($scope);

        if (isset($this->activeScopes[$scope->value])) {
            throw ScopeStateException::alreadyActive($scope);
        }

        $this->activeScopes[$scope->value] = true;
        $this->scopedInstances[$scope->value] = [];
    }

    public function endScope(Scope $scope): void
    {
        $this->assertContextualScope($scope);

        if (!isset($this->activeScopes[$scope->value])) {
            throw ScopeStateException::notActive($scope);
        }

        unset(
            $this->activeScopes[$scope->value],
            $this->scopedInstances[$scope->value],
        );
    }

    public function runInScope(Scope $scope, callable $callback): mixed
    {
        $this->beginScope($scope);

        try {
            return $callback($this);
        } finally {
            $this->endScope($scope);
        }
    }

    private function resolveBinding(string $id, Binding $binding): mixed
    {
        if ($binding->scope === Scope::Singleton && array_key_exists($id, $this->singletons)) {
            return $this->singletons[$id];
        }

        if ($binding->scope->isContextual()) {
            $scope = $binding->scope->value;

            if (!isset($this->activeScopes[$scope])) {
                throw InactiveScopeException::for($binding->scope, $id);
            }

            if (array_key_exists($id, $this->scopedInstances[$scope])) {
                return $this->scopedInstances[$scope][$id];
            }
        }

        $value = $this->resolve($id, function () use ($id, $binding): mixed {
            if ($binding->resolver instanceof Closure) {
                return ($binding->resolver)($this);
            }

            if ($binding->resolver === $id) {
                return $this->autowire($id);
            }

            return $this->get($binding->resolver);
        });

        return match ($binding->scope) {
            Scope::Singleton => $this->singletons[$id] = $value,
            Scope::Request, Scope::Worker => $this->scopedInstances[$binding->scope->value][$id] = $value,
            Scope::Transient => $value,
        };
    }

    private function resolve(string $id, Closure $resolver): mixed
    {
        $position = array_search($id, $this->resolutionStack, true);

        if ($position !== false) {
            $path = array_slice($this->resolutionStack, $position);
            $path[] = $id;

            throw CircularDependencyException::fromPath($path);
        }

        $this->resolutionStack[] = $id;

        try {
            return $resolver();
        } finally {
            array_pop($this->resolutionStack);
        }
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw ServiceNotFoundException::for($class);
        }

        try {
            /** @var class-string $class */
            /** @var ReflectionClass<object> $reflection */
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException) {
            throw ServiceNotFoundException::for($class);
        }

        if (!$reflection->isInstantiable()) {
            throw ServiceNotFoundException::for($class);
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }

            $arguments[] = $this->resolveParameter($parameter, $class);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(ReflectionParameter $parameter, string $class): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $dependency = $type->getName();

            if ($this->has($dependency)) {
                return $this->get($dependency);
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw UnresolvableDependencyException::forParameter($parameter, $class);
    }

    private function isInstantiableClass(string $id): bool
    {
        if (!class_exists($id)) {
            return false;
        }

        try {
            /** @var class-string $id */
            return (new ReflectionClass($id))->isInstantiable();
        } catch (ReflectionException) {
            return false;
        }
    }

    private function assertIdentifier(string $id): void
    {
        if (trim($id) === '') {
            throw new \InvalidArgumentException('A service identifier cannot be empty.');
        }
    }

    private function assertContextualScope(Scope $scope): void
    {
        if (!$scope->isContextual()) {
            throw ScopeStateException::notContextual($scope);
        }
    }

    private function forgetResolved(string $id): void
    {
        unset($this->singletons[$id]);

        foreach ($this->scopedInstances as &$instances) {
            unset($instances[$id]);
        }

        unset($instances);
    }
}
