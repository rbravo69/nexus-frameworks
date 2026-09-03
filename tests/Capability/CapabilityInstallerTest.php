<?php

declare(strict_types=1);

namespace Nexus\Tests\Capability;

use Nexus\Capability\CapabilityCatalog;
use Nexus\Capability\CapabilityDefinition;
use Nexus\Capability\CapabilityDistribution;
use Nexus\Capability\CapabilityInstaller;
use Nexus\Capability\CapabilityManifest;
use Nexus\Capability\CapabilityResolver;
use Nexus\Exception\CapabilityInUseException;
use Nexus\Tests\Support\CacheCapability;
use Nexus\Tests\Support\DatabaseCapability;
use Nexus\Tests\Support\RecordingPackageManager;
use Nexus\Tests\Support\TemporaryDirectory;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class CapabilityInstallerTest extends TestCase
{
    private ?TemporaryDirectory $temporaryDirectory = null;

    #[After]
    public function cleanUp(): void
    {
        $this->temporaryDirectory?->remove();
    }

    public function testItInstallsDependenciesAndPersistsTheCompleteSelection(): void
    {
        [$installer, $manifest, $packages] = $this->fixture();

        self::assertSame(['database', 'cache'], $installer->install('cache'));
        self::assertSame(['cache', 'database'], $manifest->all());
        self::assertSame(['nexus/database', 'nexus/cache'], $packages->installed);
        self::assertSame([], $installer->install('cache'));
    }

    public function testBundledCapabilitiesOnlyMutateTheManifest(): void
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $catalog = (new CapabilityCatalog())->add(new CapabilityDefinition(
            'cache',
            'nexus/framework',
            CacheCapability::class,
            distribution: CapabilityDistribution::Bundled,
        ));
        $manifest = new CapabilityManifest($this->temporaryDirectory->path());
        $packages = new RecordingPackageManager();
        $installer = new CapabilityInstaller($catalog, new CapabilityResolver($catalog), $manifest, $packages);

        self::assertSame(['cache'], $installer->install('cache'));
        self::assertSame(['cache'], $manifest->all());
        self::assertSame([], $packages->installed);

        self::assertTrue($installer->remove('cache'));
        self::assertSame([], $manifest->all());
        self::assertSame([], $packages->removed);
    }

    public function testItRollsBackPackagesWhenInstallationFails(): void
    {
        [$installer, $manifest, $packages] = $this->fixture('nexus/cache');

        try {
            $installer->install('cache');
            self::fail('The simulated package failure was not raised.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Simulated package installation failure.', $exception->getMessage());
        }

        self::assertSame([], $manifest->all());
        self::assertSame(['nexus/database'], $packages->removed);
    }

    public function testItPreventsRemovingARequiredCapability(): void
    {
        [$installer] = $this->fixture();
        $installer->install('cache');

        $this->expectException(CapabilityInUseException::class);
        $this->expectExceptionMessage('required by: cache');

        $installer->remove('database');
    }

    public function testItRemovesAnIndependentCapabilityAndItsPackage(): void
    {
        [$installer, $manifest, $packages] = $this->fixture();
        $installer->install('cache');

        self::assertTrue($installer->remove('cache'));
        self::assertFalse($installer->remove('cache'));
        self::assertSame(['database'], $manifest->all());
        self::assertSame(['nexus/cache'], $packages->removed);
    }

    /** @return array{CapabilityInstaller, CapabilityManifest, RecordingPackageManager} */
    private function fixture(?string $failingPackage = null): array
    {
        $this->temporaryDirectory = new TemporaryDirectory();
        $catalog = (new CapabilityCatalog())
            ->add(new CapabilityDefinition('database', 'nexus/database', DatabaseCapability::class))
            ->add(new CapabilityDefinition('cache', 'nexus/cache', CacheCapability::class, ['database']));
        $manifest = new CapabilityManifest($this->temporaryDirectory->path());
        $packages = new RecordingPackageManager($failingPackage);

        return [
            new CapabilityInstaller($catalog, new CapabilityResolver($catalog), $manifest, $packages),
            $manifest,
            $packages,
        ];
    }
}
