<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Bundle\LayoutBundle\Layout\TwigLayoutRenderer;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Twig\Environment;

class TwigLayoutRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testEnvironmentSet(): void
    {
        $innerRenderer = $this->createMock(TwigRendererInterface::class);
        $formRenderer = $this->createMock(FormRendererEngineInterface::class);
        $placeholderRenderer = $this->createMock(PlaceholderRenderer::class);

        $environment = $this->createMock(Environment::class);
        $newEnvironment = clone $environment;

        $innerRenderer->expects(self::exactly(2))
            ->method('setEnvironment')
            ->withConsecutive([self::identicalTo($environment)], [self::identicalTo($newEnvironment)]);

        $renderer = new TwigLayoutRenderer($innerRenderer, $formRenderer, $environment, $placeholderRenderer);

        self::assertSame($environment, $renderer->getEnvironment());

        $renderer->setEnvironment($newEnvironment);
        self::assertSame($newEnvironment, $renderer->getEnvironment());
    }
}
