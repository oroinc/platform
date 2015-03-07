<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\Block\Type\BaseType;

use Oro\Bundle\LayoutBundle\Layout\Extension\VisibleExtension;
use Oro\Component\Layout\BlockView;

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
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setOptional')
            ->with(['visible']);

        $this->extension->setDefaultOptions($resolver);
    }

    /**
     * @dataProvider buildViewDataProvider
     */
    public function testBuildView($options, $expectedVisibleValue)
    {
        $view  = new BlockView();
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $this->extension->buildView($view, $block, $options);
        $this->assertSame($expectedVisibleValue, $view->vars['visible']);
    }

    public function buildViewDataProvider()
    {
        return [
            [[], true],
            [['visible' => true], true],
            [['visible' => false], false]
        ];
    }
}
