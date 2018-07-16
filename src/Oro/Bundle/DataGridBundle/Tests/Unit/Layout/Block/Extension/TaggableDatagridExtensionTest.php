<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\DataGridBundle\Layout\Block\Extension\TaggableDatagridExtension;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class TaggableDatagridExtensionTest extends \PHPUnit\Framework\TestCase
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
    public function testConfigureOptions(array $options, array $expectedOptions)
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
        $block = $this->createMock('Oro\Component\Layout\BlockInterface');
        $this->extension->buildView($view, $block, new Options(['enable_tagging' => true]));
        $this->assertTrue($view->vars['enable_tagging']);
    }

    public function testFinishView()
    {
        $view = new BlockView();
        $view->vars['block_prefixes'] = [];
        /** @var BlockInterface $block */
        $block = $this->createMock('Oro\Component\Layout\BlockInterface');
        $this->extension->finishView($view, $block, new Options(['enable_tagging' => true]));
        $this->assertEquals('taggable_datagrid', $view->vars['block_prefixes'][0]);
    }
}
