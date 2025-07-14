<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache;

use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutContextStack;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PlaceholderRendererTest extends TestCase
{
    private LayoutContext $context;
    private LayoutManager&MockObject $layoutManager;
    private LayoutContextStack&MockObject $layoutContextStack;
    private PlaceholderRenderer $placeholderRenderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new LayoutContext();
        $this->layoutManager = $this->createMock(LayoutManager::class);
        $this->layoutContextStack = $this->createMock(LayoutContextStack::class);

        $this->placeholderRenderer = new PlaceholderRenderer(
            $this->layoutManager,
            $this->layoutContextStack,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testRenderPlaceholders(): void
    {
        $this->layoutContextStack->expects(self::atLeastOnce())
            ->method('getCurrentContext')
            ->willReturn($this->context);

        $html = <<<'HTML'
<html>
  <head>
    <title><!-- PLACEHOLDER title --></title>
  </head>
  <body>
     <h1><!-- PLACEHOLDER header --></h1>
     <article><!-- PLACEHOLDER content --></article>
  </body
<html>
HTML;
        $titleBlockLayout = $this->createMock(Layout::class);
        $titleBlockLayout->expects(self::once())
            ->method('render')
            ->willReturn('Page title');

        $headerBlockLayout = $this->createMock(Layout::class);
        $headerBlockLayout->expects(self::once())
            ->method('render')
            ->willReturn('Page header');

        $contentBlockLayout = $this->createMock(Layout::class);
        $contentBlockLayout->expects(self::once())
            ->method('render')
            ->willReturn('Page content.');

        $this->layoutManager->expects(self::any())
            ->method('getLayout')
            ->withConsecutive(
                [$this->context, 'title'],
                [$this->context, 'header'],
                [$this->context, 'content'],
            )
            ->willReturnOnConsecutiveCalls(
                $titleBlockLayout,
                $headerBlockLayout,
                $contentBlockLayout,
            );

        self::assertEquals(
            <<<'HTML'
<html>
  <head>
    <title>Page title</title>
  </head>
  <body>
     <h1>Page header</h1>
     <article>Page content.</article>
  </body
<html>
HTML,
            $this->placeholderRenderer->renderPlaceholders($html)
        );
    }

    public function testCreatePlaceholder(): void
    {
        $blockId = 'block_id';
        $html = 'block html';
        self::assertEquals(
            '<!-- PLACEHOLDER block_id -->',
            $this->placeholderRenderer->createPlaceholder($blockId, $html)
        );
    }

    public function testCreatePlaceholderWithCache(): void
    {
        $html = <<<'HTML'
<html>
  <head>
    <title><!-- PLACEHOLDER title --></title>
  </head>
  <body>
     <h1><!-- PLACEHOLDER header --></h1>
     <article><!-- PLACEHOLDER content --></article>
  </body
<html>
HTML;
        $this->placeholderRenderer->createPlaceholder('title', 'Page title');
        $this->placeholderRenderer->createPlaceholder('header', 'Page header');
        $this->placeholderRenderer->createPlaceholder('content', 'Page content.');

        self::assertEquals(
            <<<'HTML'
<html>
  <head>
    <title>Page title</title>
  </head>
  <body>
     <h1>Page header</h1>
     <article>Page content.</article>
  </body
<html>
HTML,
            $this->placeholderRenderer->renderPlaceholders($html)
        );

        $this->placeholderRenderer->createPlaceholder('content', 'Updated page content.');
        self::assertEquals(
            <<<'HTML'
<html>
  <head>
    <title>Page title</title>
  </head>
  <body>
     <h1>Page header</h1>
     <article>Updated page content.</article>
  </body
<html>
HTML,
            $this->placeholderRenderer->renderPlaceholders($html)
        );
    }

    public function testRenderPlaceholderWhenNoContext(): void
    {
        $this->layoutContextStack->expects(self::exactly(3))
            ->method('getCurrentContext')
            ->willReturn(null);

        $html = <<<'HTML'
<html>
  <head>
    <title><!-- PLACEHOLDER title --></title>
  </head>
  <body>
     <h1><!-- PLACEHOLDER header --></h1>
     <article><!-- PLACEHOLDER content --></article>
  </body
<html>
HTML;

        $this->layoutManager->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            <<<'HTML'
<html>
  <head>
    <title></title>
  </head>
  <body>
     <h1></h1>
     <article></article>
  </body
<html>
HTML,
            $this->placeholderRenderer->renderPlaceholders($html)
        );
    }
}
