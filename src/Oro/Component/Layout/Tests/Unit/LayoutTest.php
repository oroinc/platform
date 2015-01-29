<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Layout;

class LayoutTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $renderer;

    protected function setUp()
    {
        $this->renderer = $this->getMock('Oro\Component\Layout\BlockRendererInterface');
    }

    public function testGetView()
    {
        $view = new BlockView(['test']);

        $layout = new Layout($view, $this->renderer);

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

        $layout = new Layout($view, $this->renderer);
        $result = $layout->render();
        $this->assertEquals($expected, $result);
    }

    public function testSetBlockTheme()
    {
        $theme = 'MyBungle::blocks.html.twig';

        $view = new BlockView(['test']);

        $this->renderer->expects($this->once())
            ->method('setTheme')
            ->with($this->identicalTo($view), $theme);

        $layout = new Layout($view, $this->renderer);
        $layout->setBlockTheme($theme);
    }

    public function testSetBlockThemeForChild()
    {
        $theme = 'MyBungle::blocks.html.twig';

        $view = new BlockView(['test']);

        $childView                  = new BlockView(['test'], $view);
        $view->children['child_id'] = $childView;

        $this->renderer->expects($this->once())
            ->method('setTheme')
            ->with($this->identicalTo($childView), $theme);

        $layout = new Layout($view, $this->renderer);
        $layout->setBlockTheme($theme, 'child_id');
    }
}
