<?php

declare(strict_types=1);

namespace Nexus\Database\Eloquent;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;

final class EloquentManager
{
    private Capsule $capsule;
    private bool $booted = false;

    public function __construct(?Capsule $capsule = null)
    {
        if (!class_exists(Capsule::class)) {
            throw new \RuntimeException('Eloquent integration requires illuminate/database. Install it before booting Eloquent.');
        }

        $this->capsule = $capsule ?? new Capsule();
    }

    public function addConnection(EloquentConfig $config): self
    {
        $this->capsule->addConnection($config->toIlluminate(), $config->name);

        if ($config->global) {
            $this->capsule->setAsGlobal();
        }

        return $this;
    }

    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        $this->capsule->bootEloquent();
        $this->booted = true;

        return $this;
    }

    public function connection(?string $name = null): Connection
    {
        return $this->capsule->getConnection($name);
    }

    public function useConnection(string $name): void
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Eloquent connection name cannot be empty.');
        }

        $this->capsule->getDatabaseManager()->setDefaultConnection($name);
    }

    public function setModelConnection(Model $model, string $name): Model
    {
        $model->setConnection($name);

        return $model;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
