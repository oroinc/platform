<?php

namespace Oro\Component\Layout\Tests\Unit\Templating\Helper;

use Oro\Component\Layout\Form\FormRendererInterface;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Oro\Component\Layout\Templating\Helper\LayoutHelper;
use Oro\Component\Layout\Templating\TextHelper;

class LayoutHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormRendererInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $renderer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TextHelper */
    protected $textHelper;

    /** @var LayoutHelper */
    protected $helper;

    /** @var FormRendererEngineInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formRenderer;

    protected function setUp()
    {
        $this->renderer   = $this->getMock('Oro\Component\Layout\Form\FormRendererInterface');
        $this->textHelper = $this->getMockBuilder('Oro\Component\Layout\Templating\TextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formRenderer = $this->getMock('Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface');

        $this->helper = new LayoutHelper($this->renderer, $this->textHelper, $this->formRenderer);
    }

    public function testGetName()
    {
        $this->assertEquals('layout', $this->helper->getName());
    }

    public function testSetBlockTheme()
    {
        $view  = new BlockView();
        $theme = 'MyBundle:Layout\php';

        $this->renderer->expects($this->once())
            ->method('setTheme')
            ->with($this->identicalTo($view), $theme);

        $this->helper->setBlockTheme($view, $theme);
    }

    public function testSetFormTheme()
    {
        $theme = 'MyBundle:Layout\php';

        $this->formRenderer->expects($this->once())
            ->method('addDefaultThemes')
            ->with($theme);

        $this->helper->setFormTheme($theme);
    }

    public function testWidgetRendering()
    {
        $view      = new BlockView();
        $variables = ['foo' => 'bar'];

        $this->renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'widget', $variables);

        $this->helper->widget($view, $variables);
    }

    public function testParentBlockWidgetRendering()
    {
        $view      = new BlockView();
        $variables = ['foo' => 'bar'];

        $this->renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'widget', $variables, true);

        $this->helper->parentBlockWidget($view, $variables);
    }

    public function testRowRendering()
    {
        $view      = new BlockView();
        $variables = ['foo' => 'bar'];

        $this->renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'row', $variables);

        $this->helper->row($view, $variables);
    }

    public function testLabelRendering()
    {
        $view      = new BlockView();
        $label     = 'test_label';
        $variables = ['foo' => 'bar'];

        $this->renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'label', ['foo' => 'bar', 'label' => $label]);

        $this->helper->label($view, $label, $variables);
    }

    public function testLabelRenderingWithLabelInVariables()
    {
        $view      = new BlockView();
        $label     = 'test_label';
        $variables = ['foo' => 'bar', 'label' => 'original_label'];

        $this->renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'label', $variables);

        $this->helper->label($view, $label, $variables);
    }

    public function testLabelRenderingWithNoLabelSpecified()
    {
        $view      = new BlockView();
        $variables = ['foo' => 'bar'];

        $this->renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'label', $variables);

        $this->helper->label($view, null, $variables);
    }

    public function testLabelRenderingWithEmptyLabel()
    {
        $view      = new BlockView();
        $variables = ['foo' => 'bar'];

        $this->renderer->expects($this->once())
            ->method('searchAndRenderBlock')
            ->with($this->identicalTo($view), 'label', ['foo' => 'bar', 'label' => '']);

        $this->helper->label($view, '', $variables);
    }

    public function testBlockRendering()
    {
        $view      = new BlockView();
        $blockName = 'test';
        $variables = ['foo' => 'bar'];

        $this->renderer->expects($this->once())
            ->method('renderBlock')
            ->with($this->identicalTo($view), $blockName, $variables);

        $this->helper->block($view, $blockName, $variables);
    }

    public function testText()
    {
        $this->textHelper->expects($this->once())
            ->method('processText')
            ->with('test', 'domain')
            ->will($this->returnValue('processed'));

        $this->assertEquals('processed', $this->helper->text('test', 'domain'));
    }
}
