<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface;
use Oro\Bundle\LayoutBundle\Twig\TwigRenderer;
use Oro\Component\Layout\LayoutContextStack;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class TwigRendererTest extends \PHPUnit\Framework\TestCase
{
    /** @var TwigRendererEngineInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $twigRendererEngine;

    private LayoutContextStack $layoutContextStack;

    private RenderCache|\PHPUnit\Framework\MockObject\MockObject $rendererCache;

    /** @var PlaceholderRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $placeholderRenderer;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->twigRendererEngine = $this->createMock(TwigRendererEngineInterface::class);
        $this->layoutContextStack = new LayoutContextStack();
        $this->rendererCache = $this->createMock(RenderCache::class);
        $this->placeholderRenderer = $this->createMock(PlaceholderRenderer::class);
        $this->environment = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testConstructor(): void
    {
        $this->twigRendererEngine->expects(self::once())
            ->method('setEnvironment')
            ->with(self::identicalTo($this->environment));

        $renderer = new TwigRenderer(
            $this->twigRendererEngine,
            $this->layoutContextStack,
            $this->rendererCache,
            $this->placeholderRenderer,
            $this->environment
        );
        $renderer->setLogger($this->logger);
    }

    public function testSetEnvironment(): void
    {
        $newEnvironment = clone $this->environment;
        $this->twigRendererEngine->expects(self::exactly(3))
            ->method('setEnvironment')
            ->withConsecutive(
                [self::identicalTo($this->environment)],
                [self::identicalTo($newEnvironment)],
                [self::identicalTo($this->environment)]
            );

        $renderer = new TwigRenderer(
            $this->twigRendererEngine,
            $this->layoutContextStack,
            $this->rendererCache,
            $this->placeholderRenderer,
            $this->environment
        );
        $renderer->setLogger($this->logger);

        \Closure::bind(function () {
            $this->blockHierarchy = ['blockHierarchySampleData1'];
            $this->blockNameHierarchyMap = ['blockNameHierarchyMapSampleData1'];
            $this->hierarchyLevelMap = ['hierarchyLevelMapSampleData1'];
            $this->variableStack = ['variableStackSampleData1'];
        }, $renderer, TwigRenderer::class)();

        $engine1 = $renderer->getEngine();
        self::assertEquals($this->twigRendererEngine, $engine1);
        self::assertNotSame($this->twigRendererEngine, $engine1);

        // Switches to the new environment.
        $renderer->setEnvironment($newEnvironment);
        $engine2 = $renderer->getEngine();

        // Checks that TWIG renderer engine is changed after switch to new env.
        self::assertEquals($this->twigRendererEngine, $engine2);
        self::assertNotSame($this->twigRendererEngine, $engine2);
        self::assertNotSame($engine1, $engine2);

        // Checks that local cache is empty after switch to new env.
        \Closure::bind(function ($testCase) {
            $testCase::assertEquals([], $this->blockHierarchy);
            $testCase::assertEquals([], $this->blockNameHierarchyMap);
            $testCase::assertEquals([], $this->hierarchyLevelMap);
            $testCase::assertEquals([], $this->variableStack);
        }, $renderer, TwigRenderer::class)($this);

        // Switches to the original environment.
        $renderer->setEnvironment($this->environment);

        // Checks that TWIG renderer engine is switched to initial after switch to the original env.
        self::assertSame($engine1, $renderer->getEngine());
        self::assertEquals($this->twigRendererEngine, $engine1);
        self::assertNotSame($engine2, $renderer->getEngine());

        // Checks that local cache is switched to initial after switch to the original env.
        \Closure::bind(function ($testCase) {
            $testCase::assertEquals(['blockHierarchySampleData1'], $this->blockHierarchy);
            $testCase::assertEquals(['blockNameHierarchyMapSampleData1'], $this->blockNameHierarchyMap);
            $testCase::assertEquals(['hierarchyLevelMapSampleData1'], $this->hierarchyLevelMap);
            $testCase::assertEquals(['variableStackSampleData1'], $this->variableStack);
        }, $renderer, TwigRenderer::class)($this);
    }
}
