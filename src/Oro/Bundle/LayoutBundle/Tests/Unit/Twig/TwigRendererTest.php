<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface;
use Oro\Bundle\LayoutBundle\Twig\TwigRenderer;
use Psr\Log\LoggerInterface;
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

        /** @var RenderCache $rendererCache */
        $rendererCache = $this->createMock(RenderCache::class);
        /** @var PlaceholderRenderer $placeholderRenderer */
        $placeholderRenderer = $this->createMock(PlaceholderRenderer::class);
        /** @var LoggerInterface $engine */
        $logger = $this->createMock(LoggerInterface::class);

        $renderer = new TwigRenderer($engine, $rendererCache, $placeholderRenderer, $logger);
        $renderer->setEnvironment($environment);
    }
}
