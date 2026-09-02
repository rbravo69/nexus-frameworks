<?php

declare(strict_types=1);

namespace Nexus\Contracts;

use Nexus\Container\Lazy;
use Nexus\Container\Scope;

interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function make(string $id): object;

    public function bind(
        string $abstract,
        string $concrete,
        Scope $scope = Scope::Transient,
    ): self;

    /** @param callable(self): mixed $factory */
    public function factory(
        string $abstract,
        callable $factory,
        Scope $scope = Scope::Transient,
    ): self;

    public function singleton(string $abstract, ?string $concrete = null): self;

    public function transient(string $abstract, ?string $concrete = null): self;

    public function requestScoped(string $abstract, ?string $concrete = null): self;

    public function workerScoped(string $abstract, ?string $concrete = null): self;

    public function instance(string $abstract, mixed $instance): self;

    /** @return Lazy<mixed> */
    public function lazy(string $id): Lazy;

    public function beginScope(Scope $scope): void;

    public function endScope(Scope $scope): void;

    /** @param callable(self): mixed $callback */
    public function runInScope(Scope $scope, callable $callback): mixed;
}
