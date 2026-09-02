<?php

declare(strict_types=1);

namespace Nexus\Docker;

enum DockerService: string
{
    case Postgres = 'postgres';
    case MySql = 'mysql';
    case Redis = 'redis';
    case Mongo = 'mongo';
    case SqlServer = 'sqlserver';
    case RabbitMq = 'rabbitmq';
    case Kafka = 'kafka';
    case Mailpit = 'mailpit';

    /** @return list<self> */
    public static function parseList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $services = [];
        foreach (explode(',', $value) as $name) {
            $name = strtolower(trim($name));
            if ($name === '') {
                continue;
            }

            $service = self::tryFrom($name)
                ?? throw new \InvalidArgumentException(sprintf('Unsupported Docker service "%s".', $name));
            $services[$service->value] = $service;
        }

        return array_values($services);
    }
}
