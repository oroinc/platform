<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\BlockView;

use Oro\Bundle\DataGridBundle\Layout\Block\Extension\TaggableDatagridExtension;

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
     * @param array $options
     * @param array $expectedOptions
     */
    public function testSetDefaultOptions(array $options, array $expectedOptions)
    {
        $resolver = new OptionsResolver();
        $this->extension->setDefaultOptions($resolver);
        $actual = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $actual);
    }

    /**
     * @return array
     */
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
     * @param array $options
     * @param array $expectedVars
     */
    public function testBuildView(array $options, array $expectedVars)
    {
        $view = new BlockView();
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->extension->buildView($view, $block, $options);
        unset($view->vars['attr']);
        $this->assertEquals($expectedVars, $view->vars);
    }

    /**
     * @return array
     */
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
