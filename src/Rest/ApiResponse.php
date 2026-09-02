<?php

declare(strict_types=1);

namespace Nexus\Rest;

use Nexus\Http\Response;

final class ApiResponse
{
    /** @param array<string, mixed> $meta */
    public static function data(mixed $data, array $meta = [], int $status = 200): Response
    {
        $payload = ['data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return Response::json($payload, $status);
    }

    /** @param array<string, mixed> $meta */
    public static function created(mixed $data, ?string $location = null, array $meta = []): Response
    {
        $response = self::data($data, $meta, 201);

        return $location === null ? $response : $response->withHeader('location', $location);
    }

    public static function noContent(): Response
    {
        return new Response(204);
    }

    public static function problem(ProblemDetails $problem): Response
    {
        return Response::json(
            $problem->toArray(),
            $problem->status,
            ['content-type' => 'application/problem+json; charset=utf-8'],
        );
    }
}
