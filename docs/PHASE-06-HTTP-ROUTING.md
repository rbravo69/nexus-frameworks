# Phase 06 — HTTP and Routing

## Goal

Provide a small HTTP execution pipeline and fast, explicit routing without
coupling the Nexus core to HTTP.

## Delivered

- immutable request values and responses;
- text, JSON and streamed responses;
- explicit routes for common HTTP methods;
- parameterized paths;
- HEAD fallback to GET;
- route groups and group middleware;
- middleware pipeline;
- controller dispatch through the Nexus container;
- PHP attribute routes;
- 404, 405 and 500 handling;
- route parameters exposed as request attributes;
- tests for router, middleware, kernel and attributes.

## Design rules

- HTTP remains optional infrastructure.
- Explicit routes and attributes compile to the same route model.
- Middleware does not depend on controllers.
- Controllers are resolved through DI.
- No empty folders are generated.
- Route matching never requires the database, cache or queue.

## Acceptance criteria

- Static and parameterized routes match correctly.
- Missing paths and wrong methods are differentiated.
- Nested route groups can compose prefixes and middleware.
- Middleware can wrap route execution.
- Controller attributes register routes without duplicating router logic.
- PHPStan max and PHPUnit pass on PHP 8.4 and 8.5.
