<?php

declare(strict_types=1);

namespace Nexus\Tests\Configuration;

use Nexus\Configuration\ConfigurationLoader;
use Nexus\Exception\ConfigurationException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class ConfigurationLoaderTest extends TestCase
{
    private ?string $temporaryDirectory = null;

    #[After]
    public function removeTemporaryFiles(): void
    {
        if ($this->temporaryDirectory === null) {
            return;
        }

        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->temporaryDirectory);
    }

    public function testItLoadsPhpFilesByFilename(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/nexus-config-' . bin2hex(random_bytes(8));
        mkdir($this->temporaryDirectory, 0777, true);
        file_put_contents($this->temporaryDirectory . '/app.php', '<?php return ["name" => "Nexus"];');

        $configuration = (new ConfigurationLoader())->load($this->temporaryDirectory);

        self::assertSame('Nexus', $configuration->get('app.name'));
    }

    public function testItRejectsConfigurationFilesThatDoNotReturnArrays(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/nexus-config-' . bin2hex(random_bytes(8));
        mkdir($this->temporaryDirectory, 0777, true);
        file_put_contents($this->temporaryDirectory . '/invalid.php', '<?php return "invalid";');

        $this->expectException(ConfigurationException::class);

        (new ConfigurationLoader())->load($this->temporaryDirectory);
    }
}
