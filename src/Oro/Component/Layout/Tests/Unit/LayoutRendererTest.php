<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutRenderer;

class LayoutRendererTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $innerRenderer;

    /** @var LayoutRenderer */
    protected $renderer;

    protected function setUp()
    {
        $this->innerRenderer = $this->getMock('Symfony\Component\Form\FormRendererInterface');
        $this->renderer      = new LayoutRenderer($this->innerRenderer);
    }

    public function testRenderBlock()
    {
        $expected = 'some rendered string';

        $view = new BlockView();

        $this->innerRenderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'widget')
            ->will($this->returnValue($expected));

        $result = $this->renderer->renderBlock($view);
        $this->assertEquals($expected, $result);
    }

    public function testSetBlockTheme()
    {
        $theme = 'MyBungle::blocks.html.twig';

        $view = new BlockView();

        $this->innerRenderer->expects($this->once())
            ->method('setTheme')
            ->with($this->identicalTo($view), $theme);

        $this->renderer->setBlockTheme($view, $theme);
    }
}
