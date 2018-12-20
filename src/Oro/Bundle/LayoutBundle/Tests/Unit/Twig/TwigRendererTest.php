<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface;
use Oro\Bundle\LayoutBundle\Twig\TwigRenderer;

class TwigRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testSetEnvironment()
    {
        /** @var \Twig_Environment $environment */
        $environment = $this->createMock('Twig_Environment');

        /** @var TwigRendererEngineInterface|\PHPUnit\Framework\MockObject\MockObject $engine */
        $engine = $this->createMock('Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface');
        $engine->expects($this->once())
            ->method('setEnvironment')
            ->with($environment);

        $renderer = new TwigRenderer($engine);
        $renderer->setEnvironment($environment);
    }
}
