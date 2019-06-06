<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface;
use Oro\Bundle\LayoutBundle\Twig\TwigRenderer;
use Twig\Environment;

class TwigRendererTest extends \PHPUnit\Framework\TestCase
{
    public function testSetEnvironment()
    {
        /** @var Environment $environment */
        $environment = $this->createMock(Environment::class);

        /** @var TwigRendererEngineInterface|\PHPUnit\Framework\MockObject\MockObject $engine */
        $engine = $this->createMock('Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface');
        $engine->expects($this->once())
            ->method('setEnvironment')
            ->with($environment);

        $renderer = new TwigRenderer($engine);
        $renderer->setEnvironment($environment);
    }
}
