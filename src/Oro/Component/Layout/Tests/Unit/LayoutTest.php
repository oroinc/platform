<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutRendererRegistry;

class LayoutTest extends LayoutTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $renderer;

    /** @var LayoutRendererRegistry */
    protected $rendererRegistry;

    protected function setUp()
    {
        $this->renderer         = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
        $this->rendererRegistry = new LayoutRendererRegistry();
        $this->rendererRegistry->addRenderer('test', $this->renderer);
        $this->rendererRegistry->setDefaultRenderer('test');
    }

    public function testGetView()
    {
        $view = new BlockView();

        $layout = new Layout($view, $this->rendererRegistry);

        $this->assertSame($view, $layout->getView());
    }

    public function testRender()
    {
        $expected = 'some rendered string';

        $view = new BlockView();

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $result = $layout->render();
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The layout renderer named "unknown" was not found.
     */
    public function testRenderByUnknownRenderer()
    {
        $view   = new BlockView();
        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setRenderer('unknown')->render();
    }

    public function testRenderByOtherRenderer()
    {
        $expected = 'some rendered string';

        $view = new BlockView();

        $otherRenderer = $this->createMock('Oro\Component\Layout\LayoutRendererInterface');
        $this->rendererRegistry->addRenderer('other', $otherRenderer);

        $otherRenderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $result = $layout->setRenderer('other')->render();
        $this->assertEquals($expected, $result);
    }

    public function testRenderWithBlockTheme()
    {
        $expected = 'some rendered string';
        $theme    = 'MyBungle::blocks.html.twig';

        $view = new BlockView();

        $this->renderer->expects($this->once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($view), $theme);

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($theme);
        $result = $layout->render();
        $this->assertEquals($expected, $result);
    }

    public function testRenderWithBlockThemeForChild()
    {
        $expected = 'some rendered string';
        $theme    = 'MyBungle::blocks.html.twig';

        $view = new BlockView();

        $childView                  = new BlockView($view);
        $view->children['child_id'] = $childView;
        $this->setLayoutBlocks(['root' => $view]);

        $this->renderer->expects($this->once())
            ->method('setBlockTheme')
            ->with($this->identicalTo($childView), $theme);

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view))
            ->will($this->returnValue($expected));

        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setBlockTheme($theme, 'child_id');
        $result = $layout->render();
        $this->assertEquals($expected, $result);
    }

    public function testSetFormTheme()
    {
        $theme = 'MyBundle::forms.html.twig';
        $view = new BlockView();
        $this->renderer->expects($this->once())
            ->method('setFormTheme')
            ->with([$theme]);
        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setFormTheme($theme);
        $layout->render();
    }
}
