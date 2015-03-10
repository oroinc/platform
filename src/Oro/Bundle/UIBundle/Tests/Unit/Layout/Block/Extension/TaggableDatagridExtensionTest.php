<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\BlockView;

use Oro\Bundle\UIBundle\Layout\Block\Extension\TaggableDatagridExtension;

class TaggableDatagridExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaggableDatagridExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new TaggableDatagridExtension();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('datagrid', $this->extension->getExtendedType());
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testSetDefaultOptions($options, $expectedOptions)
    {
        $resolver = new OptionsResolver();
        $this->extension->setDefaultOptions($resolver);
        $actual = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $actual);
    }

    public function optionsDataProvider()
    {
        return [
            [
                [],
                ['enable_tagging' => false]
            ],
            [
                ['enable_tagging' => true],
                ['enable_tagging' => true]
            ]
        ];
    }

    /**
     * @dataProvider buildViewOptionsDataProvider
     */
    public function testBuildView($options, $expectedVars)
    {
        $view  = new BlockView();
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->extension->buildView($view, $block, $options);
        unset($view->vars['attr']);
        $this->assertEquals($expectedVars, $view->vars);
    }

    public function buildViewOptionsDataProvider()
    {
        return [
            [
                ['enable_tagging' => true],
                ['enable_tagging' => true]
            ]
        ];
    }
}
