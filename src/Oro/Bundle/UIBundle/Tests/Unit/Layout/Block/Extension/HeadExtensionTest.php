<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Extension\HeadBlockTypeExtension;

class HeadBlockTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $titleProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $titleTranslator;

    /** @var HeadBlockTypeExtension */
    protected $extension;

    protected function setUp()
    {
        $this->titleProvider   = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->titleTranslator = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleTranslator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new HeadBlockTypeExtension(
            $this->titleProvider,
            $this->titleTranslator
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('head', $this->extension->getExtendedType());
    }

    public function testBuildView()
    {
        $routeName               = 'test_route';
        $titleTemplate           = 'title';
        $translatedTitleTemplate = 'translated title';

        $context = new LayoutContext();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view    = new BlockView();

        $view->vars['title'] = '';

        $context['route_name'] = $routeName;

        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $this->titleProvider->expects($this->once())
            ->method('getTitleTemplates')
            ->with($routeName)
            ->will($this->returnValue(['title' => $titleTemplate]));
        $this->titleTranslator->expects($this->once())
            ->method('trans')
            ->with($titleTemplate)
            ->will($this->returnValue($translatedTitleTemplate));

        $this->extension->buildView($view, $block, ['title' => '']);

        $this->assertSame($translatedTitleTemplate, $view->vars['title']);
    }

    public function testBuildViewNoTitleTemplate()
    {
        $routeName = 'test_route';

        $context = new LayoutContext();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view    = new BlockView();

        $view->vars['title'] = '';

        $context['route_name'] = $routeName;

        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $this->titleProvider->expects($this->once())
            ->method('getTitleTemplates')
            ->with($routeName)
            ->will($this->returnValue([]));
        $this->titleTranslator->expects($this->never())
            ->method('trans');

        $this->extension->buildView($view, $block, ['title' => '']);

        $this->assertSame('', $view->vars['title']);
    }

    public function testBuildViewNoRoute()
    {
        $context = new LayoutContext();
        $block   = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view    = new BlockView();

        $view->vars['title'] = '';

        $block->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($context));
        $this->titleProvider->expects($this->never())
            ->method('getTitleTemplates');
        $this->titleTranslator->expects($this->never())
            ->method('trans');

        $this->extension->buildView($view, $block, ['title' => '']);

        $this->assertSame('', $view->vars['title']);
    }

    public function testBuildViewWithAlreadySetTitle()
    {
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $view  = new BlockView();

        $view->vars['title'] = 'foo';

        $block->expects($this->never())
            ->method('getContext');
        $this->titleProvider->expects($this->never())
            ->method('getTitleTemplates');
        $this->titleTranslator->expects($this->never())
            ->method('trans');

        $this->extension->buildView($view, $block, ['title' => 'foo']);

        $this->assertSame('foo', $view->vars['title']);
    }
}
