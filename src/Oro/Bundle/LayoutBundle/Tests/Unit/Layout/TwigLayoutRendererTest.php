<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Bundle\LayoutBundle\Layout\TwigLayoutRenderer;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Twig\Environment;

class TwigLayoutRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testEnvironmentSet()
    {
        /** @var TwigRendererInterface|\PHPUnit\Framework\MockObject\MockObject $innerRenderer */
        $innerRenderer = $this->createMock(TwigRendererInterface::class);
        /** @var Environment $environment */
        $environment   = $this->createMock(Environment::class);

        $innerRenderer->expects($this->once())
            ->method('setEnvironment')
            ->with($this->identicalTo($environment));
        /** @var FormRendererEngineInterface $formRenderer */
        $formRenderer = $this->createMock(FormRendererEngineInterface::class);

        new TwigLayoutRenderer($innerRenderer, $formRenderer, $environment);
    }
}
