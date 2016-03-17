<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Symfony\Bridge\Twig\Form\TwigRendererEngineInterface;

use Oro\Bundle\LayoutBundle\Twig\TwigRenderer;

class TwigRendererTest extends \PHPUnit_Framework_TestCase
{
    public function testSetEnvironment()
    {
        /** @var \Twig_Environment $environment */
        $environment = $this->getMock('Twig_Environment');

        /** @var TwigRendererEngineInterface|\PHPUnit_Framework_MockObject_MockObject $engine */
        $engine = $this->getMock('Symfony\Bridge\Twig\Form\TwigRendererEngineInterface');
        $engine->expects($this->once())
            ->method('setEnvironment')
            ->with($environment);

        $renderer = new TwigRenderer($engine);
        $renderer->setEnvironment($environment);
    }
}
