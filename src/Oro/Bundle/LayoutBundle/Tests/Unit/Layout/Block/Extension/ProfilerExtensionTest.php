<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ProfilerExtension;

class ProfilerExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProfilerExtension
     */
    protected $extension;

    /**
     * @var LayoutHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutHelper;

    protected function setUp()
    {
        $this->layoutHelper = $this->getMockLayoutHelper();
        $this->extension = new ProfilerExtension($this->layoutHelper);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildViewProfilerEnabled()
    {
        /** @var BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $view = new BlockView();
        $view->vars['id'] = 'root';
        $view->vars['attr'] = [];

        $this->layoutHelper
            ->expects($this->once())
            ->method('isProfilerEnabled')
            ->will($this->returnValue(true));

        $this->extension->buildView($view, $block, []);

        $this->assertArrayHasKey('data-layout-block-id', $view->vars['attr']);
        $this->assertSame('root', $view->vars['attr']['data-layout-block-id']);
    }

    public function testBuildViewProfilerDisabled()
    {
        /** @var BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $view = new BlockView();
        $view->vars['id'] = 'root';
        $view->vars['attr'] = [];

        $this->layoutHelper
            ->expects($this->once())
            ->method('isProfilerEnabled')
            ->will($this->returnValue(false));

        $view->vars['attr'] = [];
        $this->extension->buildView($view, $block, []);

        $this->assertArrayNotHasKey('data-layout-block-id', $view->vars['attr']);
    }

    /**
     * @return LayoutHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockLayoutHelper()
    {
        return $this->getMock('Oro\Bundle\LayoutBundle\Request\LayoutHelper', [], [], '', false);
    }
}
