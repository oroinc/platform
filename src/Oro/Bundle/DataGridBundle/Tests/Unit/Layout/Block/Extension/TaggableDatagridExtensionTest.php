<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

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
        $this->extension->configureOptions($resolver);
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

    public function testBuildView()
    {
        $view = new BlockView();
        /** @var BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->extension->buildView($view, $block, ['enable_tagging' => true]);
        $this->assertTrue($view->vars['enable_tagging']);
    }

    public function testFinishView()
    {
        $view = new BlockView();
        $view->vars['block_prefixes'] = [];
        /** @var BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->extension->finishView($view, $block, ['enable_tagging' => true]);
        $this->assertEquals('taggable_datagrid', $view->vars['block_prefixes'][0]);
    }
}
