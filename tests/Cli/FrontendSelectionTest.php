<?php

declare(strict_types=1);

namespace Nexus\Tests\Cli;

use Nexus\Cli\ComponentLibrary;
use Nexus\Cli\CssFramework;
use Nexus\Cli\FrontendInteractivity;
use Nexus\Cli\FrontendRenderer;
use Nexus\Cli\FrontendSelection;
use Nexus\Exception\InvalidInputException;
use PHPUnit\Framework\TestCase;

final class FrontendSelectionTest extends TestCase
{
    public function testItAcceptsTwigWithHtmxAlpineTailwindAndDaisyUi(): void
    {
        $selection = new FrontendSelection(
            FrontendRenderer::Twig,
            FrontendInteractivity::HtmxAlpine,
            CssFramework::Tailwind,
            ComponentLibrary::DaisyUi,
        );

        self::assertSame([
            'renderer' => 'twig',
            'interactivity' => 'htmx-alpine',
            'css' => 'tailwind',
            'components' => 'daisyui',
        ], $selection->toArray());
    }

    public function testDaisyUiRequiresTailwind(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('DaisyUI requires Tailwind CSS.');

        new FrontendSelection(
            FrontendRenderer::Twig,
            FrontendInteractivity::None,
            CssFramework::Bootstrap,
            ComponentLibrary::DaisyUi,
        );
    }

    public function testMaterialUiRequiresReact(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Material UI requires React.');

        new FrontendSelection(
            FrontendRenderer::Vue,
            FrontendInteractivity::None,
            CssFramework::None,
            ComponentLibrary::MaterialUi,
        );
    }

    public function testHtmxAndAlpineAreRestrictedToServerRenderedFrontends(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('HTMX and Alpine.js are available only with Twig or PHP Native rendering.');

        new FrontendSelection(
            FrontendRenderer::React,
            FrontendInteractivity::Htmx,
        );
    }

    public function testNoneRejectsAdditionalFrontendOptions(): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Frontend options require a frontend renderer.');

        new FrontendSelection(
            FrontendRenderer::None,
            FrontendInteractivity::None,
            CssFramework::Tailwind,
        );
    }
}
