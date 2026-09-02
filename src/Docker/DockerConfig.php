<?php

declare(strict_types=1);

namespace Nexus\Docker;

final readonly class DockerConfig
{
    /** @param list<DockerService> $services */
    public function __construct(
        public DockerRuntime $runtime = DockerRuntime::FrankenPhp,
        public array $services = [],
        public bool $production = false,
    ) {
    }
}
