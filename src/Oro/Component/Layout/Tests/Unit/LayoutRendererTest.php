<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Oro\Component\Layout\LayoutRenderer;

class LayoutRendererTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $innerRenderer;

    /** @var LayoutRenderer */
    protected $renderer;

    /** @var FormRendererEngineInterface */
    protected $formRenderer;

    protected function setUp()
    {
        $this->innerRenderer = $this->createMock('Oro\Component\Layout\Form\FormRendererInterface');
        $this->formRenderer = $this->createMock('Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface');
        $this->renderer      = new LayoutRenderer($this->innerRenderer, $this->formRenderer);
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

    public function testSetFormTheme()
    {
        $theme = 'MyBundle::forms.html.twig';

        $this->formRenderer->expects($this->once())
            ->method('addDefaultThemes')
            ->with($theme);

        $this->renderer->setFormTheme($theme);
    }
}
