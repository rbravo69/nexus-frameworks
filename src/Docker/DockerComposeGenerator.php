<?php

declare(strict_types=1);

namespace Nexus\Docker;

final class DockerComposeGenerator
{
    /** @return array<string, string> */
    public function files(DockerConfig $config): array
    {
        $files = [
            'compose.yaml' => $this->compose($config),
            'docker/php/Dockerfile' => $this->dockerfile($config->runtime, $config->production),
            '.dockerignore' => ".git\nvendor\nnode_modules\nvar/cache\n.env\n",
        ];

        return array_replace($files, $this->runtimeFiles($config->runtime));
    }

    public function compose(DockerConfig $config): string
    {
        $services = $this->applicationServices($config);

        foreach ($config->services as $service) {
            $services[$service->value] = $this->service($service, $config->production);
        }

        $yaml = "name: nexus-app\nservices:\n";
        foreach ($services as $name => $lines) {
            $yaml .= "  {$name}:\n";
            foreach ($lines as $line) {
                $yaml .= "    {$line}\n";
            }
        }

        return $yaml;
    }

    /** @return array<string, list<string>> */
    private function applicationServices(DockerConfig $config): array
    {
        $app = [
            'build:',
            '  context: .',
            '  dockerfile: docker/php/Dockerfile',
            'working_dir: /app',
            'environment:',
            '  APP_ENV: ' . ($config->production ? 'production' : 'development'),
        ];

        if (!$config->production) {
            $app[] = 'volumes:';
            $app[] = '  - .:/app';
        } else {
            $app[] = 'restart: unless-stopped';
        }

        if ($config->runtime !== DockerRuntime::PhpFpmNginx) {
            $app[] = 'ports:';
            $app[] = '  - "8080:8080"';
        }

        if ($config->runtime !== DockerRuntime::PhpFpmNginx) {
            return ['app' => $app];
        }

        $nginx = [
            'build:',
            '  context: .',
            '  dockerfile: docker/nginx/Dockerfile',
            'depends_on:',
            '  - app',
        ];
        if (!$config->production) {
            $nginx[] = 'volumes:';
            $nginx[] = '  - .:/app:ro';
        } else {
            $nginx[] = 'restart: unless-stopped';
        }
        $nginx[] = 'ports:';
        $nginx[] = '  - "8080:80"';

        return ['app' => $app, 'nginx' => $nginx];
    }

    /** @return list<string> */
    private function service(DockerService $service, bool $production): array
    {
        $ports = static fn (string $mapping): array => $production ? [] : ['ports:', '  - "127.0.0.1:' . $mapping . '"'];

        return match ($service) {
            DockerService::Postgres => ['image: postgres:18-alpine', 'environment:', '  POSTGRES_DB: ${POSTGRES_DB:-nexus}', '  POSTGRES_USER: ${POSTGRES_USER:-nexus}', '  POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-nexus}', ...$ports('5432:5432')],
            DockerService::MySql => ['image: mysql:8.4', 'environment:', '  MYSQL_DATABASE: ${MYSQL_DATABASE:-nexus}', '  MYSQL_USER: ${MYSQL_USER:-nexus}', '  MYSQL_PASSWORD: ${MYSQL_PASSWORD:-nexus}', '  MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD:-root}', ...$ports('3306:3306')],
            DockerService::Redis => ['image: redis:8-alpine', ...$ports('6379:6379')],
            DockerService::Mongo => ['image: mongo:8', ...$ports('27017:27017')],
            DockerService::SqlServer => ['image: mcr.microsoft.com/mssql/server:2025-latest', 'environment:', '  ACCEPT_EULA: "Y"', '  MSSQL_SA_PASSWORD: ${MSSQL_SA_PASSWORD:-ChangeMe_Nexus123!}', ...$ports('1433:1433')],
            DockerService::RabbitMq => ['image: rabbitmq:4-management-alpine', ...($production ? [] : ['ports:', '  - "127.0.0.1:5672:5672"', '  - "127.0.0.1:15672:15672"'])],
            DockerService::Kafka => ['image: apache/kafka:4.1.0', ...$ports('9092:9092')],
            DockerService::Mailpit => ['image: axllent/mailpit:latest', ...($production ? [] : ['ports:', '  - "127.0.0.1:1025:1025"', '  - "127.0.0.1:8025:8025"'])],
        };
    }

    /** @return array<string, string> */
    private function runtimeFiles(DockerRuntime $runtime): array
    {
        return match ($runtime) {
            DockerRuntime::FrankenPhp => [
                'docker/frankenphp/Caddyfile' => ":8080 {\n    root * /app/public\n    php_server\n    encode zstd gzip\n}\n",
            ],
            DockerRuntime::PhpFpmNginx => [
                'docker/nginx/default.conf' => "server {\n    listen 80;\n    root /app/public;\n    index index.php;\n\n    location / { try_files \$uri \$uri/ /index.php?\$query_string; }\n    location ~ \\.php$ {\n        include fastcgi_params;\n        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n        fastcgi_pass app:9000;\n    }\n}\n",
                'docker/nginx/Dockerfile' => "FROM nginx:1.27-alpine\nCOPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf\nCOPY public /app/public\n",
            ],
            DockerRuntime::RoadRunner => [
                'docker/roadrunner/.rr.yaml' => "version: '3'\nserver:\n  command: 'php docker/roadrunner/worker.php'\nhttp:\n  address: 0.0.0.0:8080\n  pool:\n    num_workers: 2\nlogs:\n  mode: production\n",
                'docker/roadrunner/worker.php' => $this->roadRunnerWorker(),
            ],
            DockerRuntime::OpenSwoole => [
                'docker/openswoole/server.php' => $this->openSwooleServer(),
            ],
        };
    }

    private function dockerfile(DockerRuntime $runtime, bool $production): string
    {
        $install = $production
            ? "RUN composer install --no-dev --classmap-authoritative --no-interaction\n"
            : "RUN composer install --no-interaction\n";

        return match ($runtime) {
            DockerRuntime::FrankenPhp => "FROM dunglas/frankenphp:php8.4\nWORKDIR /app\nCOPY --from=composer:2 /usr/bin/composer /usr/bin/composer\nCOPY . /app\n{$install}COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile\nEXPOSE 8080\nCMD [\"frankenphp\", \"run\", \"--config\", \"/etc/caddy/Caddyfile\"]\n",
            DockerRuntime::PhpFpmNginx => "FROM php:8.4-fpm-alpine\nWORKDIR /app\nCOPY --from=composer:2 /usr/bin/composer /usr/bin/composer\nCOPY . /app\n{$install}EXPOSE 9000\nCMD [\"php-fpm\", \"-F\"]\n",
            DockerRuntime::RoadRunner => "FROM ghcr.io/roadrunner-server/roadrunner:2025.1.5 AS roadrunner\nFROM php:8.4-cli-alpine\nWORKDIR /app\nCOPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr\nCOPY --from=composer:2 /usr/bin/composer /usr/bin/composer\nCOPY . /app\nRUN composer require spiral/roadrunner-worker:^3.0 spiral/roadrunner-http:^3.0 nyholm/psr7:^1.8 --no-interaction --no-scripts\nEXPOSE 8080\nCMD [\"rr\", \"serve\", \"-c\", \"docker/roadrunner/.rr.yaml\"]\n",
            DockerRuntime::OpenSwoole => "FROM php:8.4-cli-alpine\nWORKDIR /app\nRUN apk add --no-cache \$PHPIZE_DEPS linux-headers openssl-dev && pecl install openswoole && docker-php-ext-enable openswoole\nCOPY --from=composer:2 /usr/bin/composer /usr/bin/composer\nCOPY . /app\n{$install}EXPOSE 8080\nCMD [\"php\", \"docker/openswoole/server.php\"]\n",
        };
    }

    private function roadRunnerWorker(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

$factory = new Psr17Factory();
$worker = new PSR7Worker(Worker::create(), $factory, $factory, $factory);

while ($request = $worker->waitRequest()) {
    try {
        $handler = require dirname(__DIR__, 2) . '/public/index.php';
        if (!is_callable($handler)) {
            throw new RuntimeException('public/index.php must return a PSR-7 request handler callable for RoadRunner.');
        }
        $response = $handler($request);
        $worker->respond($response);
    } catch (Throwable $e) {
        $worker->getWorker()->error((string) $e);
    }
}
PHP;
    }

    private function openSwooleServer(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use OpenSwoole\HTTP\Request;
use OpenSwoole\HTTP\Response;
use OpenSwoole\HTTP\Server;

$server = new Server('0.0.0.0', 8080);
$server->on('request', static function (Request $request, Response $response): void {
    $_SERVER = array_change_key_case($request->server ?? [], CASE_UPPER);
    $_GET = $request->get ?? [];
    $_POST = $request->post ?? [];
    ob_start();
    require dirname(__DIR__, 2) . '/public/index.php';
    $response->end((string) ob_get_clean());
});
$server->start();
PHP;
    }
}
