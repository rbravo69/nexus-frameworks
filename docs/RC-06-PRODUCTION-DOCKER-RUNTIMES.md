# RC-06 — Production-ready Docker runtimes

## Objective

Make every Docker runtime declared by Nexus correspond to a real runtime topology instead of falling back to the PHP development server.

## Runtimes

### FrankenPHP

Generates a FrankenPHP image plus a Caddyfile and exposes the application directly on port 8080.

### PHP-FPM + Nginx

Generates two distinct services: a PHP-FPM application container on port 9000 and an Nginx frontend with FastCGI configuration. Production images contain the application assets instead of depending on a source bind mount.

### RoadRunner

Generates a RoadRunner binary image, `.rr.yaml`, an HTTP worker and the required RoadRunner PHP worker packages. The long-running worker contract requires the application front controller to expose a callable request handler suitable for RoadRunner.

### OpenSwoole

Builds and enables the OpenSwoole extension and starts an OpenSwoole HTTP server instead of delegating to `nexus serve`.

## Development vs production

Development stacks mount the source tree and bind infrastructure ports only to `127.0.0.1`. Production stacks remove development source mounts, do not publish database/cache ports by default, set `APP_ENV=production`, use Composer's production install mode and enable container restart policies.

Infrastructure credentials use environment substitutions so deployments can override defaults without editing generated Compose files.

## CI

The `Docker Runtimes` workflow generates every production variant, validates it with `docker compose config`, builds the PHP image for all four runtimes and additionally builds the Nginx image for the FPM topology.

This validates container topology and buildability. Application-specific long-running bootstrap behavior remains the responsibility of the application's HTTP entrypoint contract.
