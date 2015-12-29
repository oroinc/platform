<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout;

use Symfony\Bridge\Twig\Form\TwigRendererInterface;

use Oro\Bundle\LayoutBundle\Layout\TwigLayoutRenderer;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;

class TwigLayoutRendererTest extends \PHPUnit_Framework_TestCase
{
    public function testEnvironmentSet()
    {
        /** @var TwigRendererInterface|\PHPUnit_Framework_MockObject_MockObject $innerRenderer */
        $innerRenderer = $this->getMock('Symfony\Bridge\Twig\Form\TwigRendererInterface');
        /** @var \Twig_Environment $environment */
        $environment   = $this->getMockBuilder('\Twig_Environment')->getMock();

        $innerRenderer->expects($this->once())
            ->method('setEnvironment')
            ->with($this->identicalTo($environment));
        /** @var FormRendererEngineInterface $formRenderer */
        $formRenderer = $this->getMock('Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface');

        new TwigLayoutRenderer($innerRenderer, $formRenderer, $environment);
    }
}
