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
        $environment = $this->createMock(Environment::class);
        $rendererCache = $this->createMock(RenderCache::class);
        $placeholderRenderer = $this->createMock(PlaceholderRenderer::class);
        $logger = $this->createMock(LoggerInterface::class);

        $engine = $this->createMock(TwigRendererEngineInterface::class);
        $engine->expects($this->once())
            ->method('setEnvironment')
            ->with($environment);

        $renderer = new TwigRenderer($engine, $rendererCache, $placeholderRenderer, $logger);
        $renderer->setEnvironment($environment);
    }
}
