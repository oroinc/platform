<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockRendererRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Layout;

class LayoutTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $renderer;

    /** @var BlockRendererRegistry */
    protected $rendererRegistry;

    protected function setUp()
    {
        $this->renderer         = $this->getMock('Oro\Component\Layout\BlockRendererInterface');
        $this->rendererRegistry = new BlockRendererRegistry();
        $this->rendererRegistry->addRenderer('test', $this->renderer);
        $this->rendererRegistry->setDefaultRenderer('test');
    }

    public function testGetView()
    {
        $view = new BlockView(['test']);

        $layout = new Layout($view, $this->rendererRegistry);

        $this->assertSame($view, $layout->getView());
    }

    public function testRender()
    {
        $expected = 'some rendered string';

        $view = new BlockView(['test']);

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
     * @expectedExceptionMessage The block renderer named "unknown" was not found.
     */
    public function testRenderByUnknownRenderer()
    {
        $view   = new BlockView(['test']);
        $layout = new Layout($view, $this->rendererRegistry);
        $layout->setRenderer('unknown')->render();
    }

    public function testRenderByOtherRenderer()
    {
        $expected = 'some rendered string';

        $view = new BlockView(['test']);

        $otherRenderer = $this->getMock('Oro\Component\Layout\BlockRendererInterface');
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

        $view = new BlockView(['test']);

        $this->renderer->expects($this->once())
            ->method('setTheme')
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

        $view = new BlockView(['test']);

        $childView                  = new BlockView(['test'], $view);
        $view->children['child_id'] = $childView;

        $this->renderer->expects($this->once())
            ->method('setTheme')
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
}
