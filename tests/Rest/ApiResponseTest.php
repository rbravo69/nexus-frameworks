<?php

declare(strict_types=1);

namespace Nexus\Tests\Rest;

use Nexus\Rest\ApiResponse;
use Nexus\Rest\ProblemDetails;
use PHPUnit\Framework\TestCase;

final class ApiResponseTest extends TestCase
{
    public function testItBuildsDataAndCreatedResponses(): void
    {
        $response = ApiResponse::data(['id' => 7], ['request_id' => 'abc']);
        self::assertSame(200, $response->status());
        self::assertSame('{"data":{"id":7},"meta":{"request_id":"abc"}}', $response->body());

        $created = ApiResponse::created(['id' => 8], '/users/8');
        self::assertSame(201, $created->status());
        self::assertSame('/users/8', $created->headers()['location'] ?? null);
    }

    public function testItBuildsProblemDetailsResponses(): void
    {
        $problem = new ProblemDetails(
            'https://example.test/problems/not-found',
            'Not Found',
            404,
            'The resource does not exist.',
            '/users/99',
            ['trace_id' => 'xyz'],
        );

        $response = ApiResponse::problem($problem);

        self::assertSame(404, $response->status());
        self::assertSame('application/problem+json; charset=utf-8', $response->headers()['content-type'] ?? null);
        self::assertStringContainsString('"trace_id":"xyz"', $response->body());
    }
}
