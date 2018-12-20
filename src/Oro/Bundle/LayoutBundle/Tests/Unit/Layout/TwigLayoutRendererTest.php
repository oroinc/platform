<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Bundle\LayoutBundle\Layout\TwigLayoutRenderer;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

class TwigLayoutRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testEnvironmentSet()
    {
        /** @var TwigRendererInterface|\PHPUnit\Framework\MockObject\MockObject $innerRenderer */
        $innerRenderer = $this->createMock('Oro\Bundle\LayoutBundle\Form\TwigRendererInterface');
        /** @var \Twig_Environment $environment */
        $environment   = $this->getMockBuilder('\Twig_Environment')->getMock();

        $innerRenderer->expects($this->once())
            ->method('setEnvironment')
            ->with($this->identicalTo($environment));
        /** @var FormRendererEngineInterface $formRenderer */
        $formRenderer = $this->createMock('Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface');

        new TwigLayoutRenderer($innerRenderer, $formRenderer, $environment);
    }
}
