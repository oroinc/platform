<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\HeadType;
use Oro\Bundle\UIBundle\Layout\Block\Extension\HeadExtension;

class HeadExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $titleProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $titleTranslator;

    /** @var HeadExtension */
    protected $extension;

    protected function setUp()
    {
        $this->titleProvider   = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->titleTranslator = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleTranslator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new HeadExtension(
            $this->titleProvider,
            $this->titleTranslator
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(HeadType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @dataProvider defaultOptionsDataProvider
     */
    public function testSetDefaultOptions($options, $expectedOptions)
    {
        $resolver = new OptionsResolver();
        $this->extension->setDefaultOptions($resolver);
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $resolvedOptions);
    }

    public function defaultOptionsDataProvider()
    {
        return [
            [
                [],
                ['cache' => null]
            ],
            [
                ['cache' => false],
                ['cache' => false]
            ]
        ];
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

        $this->extension->buildView($view, $block, ['title' => '', 'cache' => true]);

        $this->assertSame($translatedTitleTemplate, $view->vars['title']);
        $this->assertTrue($view->vars['cache']);
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

        $this->extension->buildView($view, $block, ['title' => '', 'cache' => true]);

        $this->assertSame('', $view->vars['title']);
        $this->assertTrue($view->vars['cache']);
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

        $this->extension->buildView($view, $block, ['title' => '', 'cache' => true]);

        $this->assertSame('', $view->vars['title']);
        $this->assertTrue($view->vars['cache']);
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

        $this->extension->buildView($view, $block, ['title' => 'foo', 'cache' => true]);

        $this->assertSame('foo', $view->vars['title']);
        $this->assertTrue($view->vars['cache']);
    }
}
