<?php

declare(strict_types=1);

namespace Nexus\OpenApi\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Operation
{
    /**
     * @param list<string> $tags
     * @param array<int, string> $responses
     */
    public function __construct(
        public ?string $summary = null,
        public ?string $description = null,
        public ?string $operationId = null,
        public array $tags = [],
        public array $responses = [200 => 'Successful response'],
    ) {
    }
}
