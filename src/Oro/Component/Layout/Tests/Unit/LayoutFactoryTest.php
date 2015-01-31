<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutFactory;
use Oro\Component\Layout\LayoutRendererRegistry;

class LayoutFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $renderer;

    /** @var LayoutFactory */
    protected $layoutFactory;

    protected function setUp()
    {
        $this->renderer   = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        $rendererRegistry = new LayoutRendererRegistry();
        $rendererRegistry->addRenderer('test', $this->renderer);
        $rendererRegistry->setDefaultRenderer('test');

        $this->layoutFactory = new LayoutFactory($rendererRegistry);
    }

    public function testCreateLayout()
    {
        $view = new BlockView(['test']);

        $layout = $this->layoutFactory->createLayout($view);

        $this->assertSame($view, $layout->getView());

        // check that the renderer is passed to the Layout object
        $this->renderer->expects($this->once())
            ->method('renderBlock');
        $layout->render();
    }
}
