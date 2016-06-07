<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\VisibleExtension;

class VisibleExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var VisibleExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new VisibleExtension();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(BaseType::NAME, $this->extension->getExtendedType());
    }

    public function testSetDefaultOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMock('Oro\Component\Layout\Block\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefined')
            ->with(['visible']);

        $this->extension->configureOptions($resolver);
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param array $options
     * @param bool $expectedVisibleValue
     */
    public function testBuildView(array $options, $expectedVisibleValue)
    {
        $view  = new BlockView();
        /** @var BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $this->extension->buildView($view, $block, $options);
        $this->assertSame($expectedVisibleValue, $view->vars['visible']);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            [[], true],
            [['visible' => true], true],
            [['visible' => false], false]
        ];
    }
}
