<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache;

use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PlaceholderRendererTest extends TestCase
{
    /**
     * @var LayoutContext|MockObject
     */
    private $context;

    /**
     * @var LayoutManager|MockObject
     */
    private $layoutManager;

    /**
     * @var PlaceholderRenderer
     */
    private $placeholderRenderer;

    protected function setUp(): void
    {
        $this->layoutManager = $this->createMock(LayoutManager::class);
        $this->context = new LayoutContext();
        $contextHolder = $this->createMock(LayoutContextHolder::class);
        $contextHolder->expects($this->any())
            ->method('getContext')
            ->willReturn($this->context);
        $logger = $this->createMock(LoggerInterface::class);

        $this->placeholderRenderer = new PlaceholderRenderer(
            $this->layoutManager,
            $contextHolder,
            $logger
        );
    }

    public function testRenderPlaceholders()
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
        $titleBlockLayout = $this->createMock(Layout::class);
        $titleBlockLayout->expects($this->once())
            ->method('render')
            ->willReturn('Page title');

        $headerBlockLayout = $this->createMock(Layout::class);
        $headerBlockLayout->expects($this->once())
            ->method('render')
            ->willReturn('Page header');

        $contentBlockLayout = $this->createMock(Layout::class);
        $contentBlockLayout->expects($this->once())
            ->method('render')
            ->willReturn('Page content.');

        $this->layoutManager->expects($this->any())
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

        $this->assertEquals(
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

    public function testCreatePlaceholder()
    {
        $blockId = 'block_id';
        $html = 'block html';
        $this->assertEquals(
            '<!-- PLACEHOLDER block_id -->',
            $this->placeholderRenderer->createPlaceholder($blockId, $html)
        );
    }

    public function testCreatePlaceholderWithCache()
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

        $this->assertEquals(
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
        $this->assertEquals(
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
}
