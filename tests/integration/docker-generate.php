<?php

declare(strict_types=1);

use Nexus\Docker\DockerComposeGenerator;
use Nexus\Docker\DockerConfig;
use Nexus\Docker\DockerRuntime;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$runtime = DockerRuntime::parse($argv[1] ?? 'frankenphp');
$output = $argv[2] ?? throw new RuntimeException('Output directory required.');
$production = ($argv[3] ?? 'production') === 'production';

@mkdir($output, 0777, true);

$files = (new DockerComposeGenerator())->files(new DockerConfig($runtime, [], $production));
foreach ($files as $path => $content) {
    $target = rtrim($output, '/') . '/' . $path;
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, $content);
}

copy(dirname(__DIR__, 2) . '/composer.json', $output . '/composer.json');
@mkdir($output . '/src', 0777, true);
@mkdir($output . '/public', 0777, true);
file_put_contents($output . '/public/index.php', "<?php\n\ndeclare(strict_types=1);\n\necho 'nexus';\n");
