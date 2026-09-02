<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;

enum ProjectType: string
{
    case Api = 'api';
    case Microservice = 'microservice';
    case Grpc = 'grpc';
    case Module = 'module';
    case Monolith = 'monolith';
    case ModularMonolith = 'modular-monolith';

    public function label(): string
    {
        return match ($this) {
            self::Api => 'API REST',
            self::Microservice => 'Microservice',
            self::Grpc => 'gRPC service',
            self::Module => 'Module',
            self::Monolith => 'Traditional monolith',
            self::ModularMonolith => 'Modular monolith',
        };
    }

    /** @return non-empty-array<string, string> */
    public static function choices(): array
    {
        return [
            self::Api->value => self::Api->label(),
            self::Microservice->value => self::Microservice->label(),
            self::Grpc->value => self::Grpc->label(),
            self::Module->value => self::Module->label(),
            self::Monolith->value => self::Monolith->label(),
            self::ModularMonolith->value => self::ModularMonolith->label(),
        ];
    }

    public static function parse(string $value): self
    {
        return self::tryFrom(strtolower(trim($value)))
            ?? throw new InvalidInputException(sprintf(
                'Unknown project type "%s". Expected one of: %s.',
                $value,
                implode(', ', array_keys(self::choices())),
            ));
    }
}
