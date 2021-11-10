<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Bundle\LayoutBundle\Layout\TwigLayoutRenderer;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Twig\Environment;

class TwigLayoutRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testEnvironmentSet()
    {
        $innerRenderer = $this->createMock(TwigRendererInterface::class);
        $environment = $this->createMock(Environment::class);
        $formRenderer = $this->createMock(FormRendererEngineInterface::class);
        $placeholderRenderer = $this->createMock(PlaceholderRenderer::class);

        $innerRenderer->expects($this->once())
            ->method('setEnvironment')
            ->with($this->identicalTo($environment));

        new TwigLayoutRenderer($innerRenderer, $formRenderer, $environment, $placeholderRenderer);
    }
}
