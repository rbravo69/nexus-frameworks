<?php

declare(strict_types=1);

namespace Nexus\Cli;

use Nexus\Exception\InvalidInputException;

final readonly class FrontendSelection
{
    public function __construct(
        public FrontendRenderer $renderer,
        public FrontendInteractivity $interactivity = FrontendInteractivity::None,
        public CssFramework $css = CssFramework::None,
        public ComponentLibrary $components = ComponentLibrary::None,
    ) {
        $this->validate();
    }

    public static function none(): self
    {
        return new self(FrontendRenderer::None);
    }

    /** @return array{renderer: string, interactivity: string, css: string, components: string} */
    public function toArray(): array
    {
        return [
            'renderer' => $this->renderer->value,
            'interactivity' => $this->interactivity->value,
            'css' => $this->css->value,
            'components' => $this->components->value,
        ];
    }

    private function validate(): void
    {
        if ($this->renderer === FrontendRenderer::None) {
            if (
                $this->interactivity !== FrontendInteractivity::None
                || $this->css !== CssFramework::None
                || $this->components !== ComponentLibrary::None
            ) {
                throw new InvalidInputException('Frontend options require a frontend renderer.');
            }

            return;
        }

        if (!$this->renderer->isServerRendered() && $this->interactivity !== FrontendInteractivity::None) {
            throw new InvalidInputException('HTMX and Alpine.js are available only with Twig or PHP Native rendering.');
        }

        if ($this->components === ComponentLibrary::DaisyUi && $this->css !== CssFramework::Tailwind) {
            throw new InvalidInputException('DaisyUI requires Tailwind CSS.');
        }

        if ($this->components === ComponentLibrary::MaterialUi && $this->renderer !== FrontendRenderer::React) {
            throw new InvalidInputException('Material UI requires React.');
        }
    }
}
