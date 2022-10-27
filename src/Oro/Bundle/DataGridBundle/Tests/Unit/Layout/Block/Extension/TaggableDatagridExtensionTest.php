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
    private $extension;

    protected function setUp(): void
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
    public function testConfigureOptions(array $options, array $expectedOptions)
    {
        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);
        $actual = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $actual);
    }

    public function optionsDataProvider(): array
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
        $block = $this->createMock(BlockInterface::class);
        $this->extension->buildView($view, $block, new Options(['enable_tagging' => true]));
        $this->assertTrue($view->vars['enable_tagging']);
    }

    public function testFinishView()
    {
        $view = new BlockView();
        $view->vars['block_prefixes'] = [];
        $block = $this->createMock(BlockInterface::class);
        $this->extension->finishView($view, $block);
        $this->assertEquals('taggable_datagrid', $view->vars['block_prefixes'][0]);
    }
}
