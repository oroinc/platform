<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\DataGridBundle\Layout\Block\Type\DatagridType;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

class DatagridTypeTest extends BlockTypeTestCase
{
    /** @var NameStrategyInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $nameStrategy;

    protected function setUp()
    {
        parent::setUp();

        $this->nameStrategy = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface');
    }

    public function testBuildView()
    {
        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->with('test-grid', 'test-scope')
            ->will($this->returnValue('test-grid-test-scope'));

        $view = $this->getBlockView(
            new DatagridType($this->nameStrategy),
            [
                'grid_name'       => 'test-grid',
                'grid_scope'      => 'test-scope',
                'grid_parameters' => ['foo' => 'bar'],
                'grid_render_parameters' => ['foo1' => 'bar1']
            ]
        );

        $this->assertEquals('test-grid', $view->vars['grid_name']);
        $this->assertEquals('test-grid-test-scope', $view->vars['grid_full_name']);
        $this->assertEquals('test-scope', $view->vars['grid_scope']);
        $this->assertEquals(['foo' => 'bar'], $view->vars['grid_parameters']);
        $this->assertEquals(['foo1' => 'bar1'], $view->vars['grid_render_parameters']);
    }

    public function testBuildViewWithoutScope()
    {
        $this->nameStrategy->expects($this->never())
            ->method('buildGridFullName');

        $view = $this->getBlockView(
            new DatagridType($this->nameStrategy),
            [
                'grid_name'       => 'test-grid',
                'grid_parameters' => ['foo' => 'bar'],
                'grid_render_parameters' => ['foo1' => 'bar1'],
            ]
        );

        $this->assertEquals('test-grid', $view->vars['grid_name']);
        $this->assertEquals('test-grid', $view->vars['grid_full_name']);
        $this->assertFalse(isset($view->vars['grid_scope']));
        $this->assertEquals(['foo' => 'bar'], $view->vars['grid_parameters']);
        $this->assertEquals(['foo1' => 'bar1'], $view->vars['grid_render_parameters']);
    }

    public function testBuildViewWithParamsOverwrite()
    {
        $view = $this->getBlockView(
            new DatagridType($this->nameStrategy),
            [
                'grid_name'       => 'test-grid',
                'grid_parameters' => ['enableFullScreenLayout' => false]
            ]
        );
        $this->assertEquals(['enableFullScreenLayout' => false], $view->vars['grid_parameters']);
        $this->assertEquals([], $view->vars['grid_render_parameters']);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "grid_name" is missing.
     */
    public function testBuildViewThrowsExceptionIfGridNameIsNotSpecified()
    {
        $this->getBlockView(new DatagridType($this->nameStrategy));
    }

    public function testGetName()
    {
        $type = new DatagridType($this->nameStrategy);

        $this->assertSame(DatagridType::NAME, $type->getName());
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expectedOptions
     */
    public function testSetDefaultOptions(array $options, array $expectedOptions)
    {
        $datagridType = new DatagridType($this->nameStrategy);
        $resolver = new OptionsResolver();
        $datagridType->configureOptions($resolver);

        $actual = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $actual);
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'default' => [
                [
                    'grid_name' => 'test_grid',
                ],
                [
                    'grid_name' => 'test_grid',
                    'grid_parameters' => [],
                    'grid_render_parameters' => [],
                ]
            ],
            'custom' => [
                [
                    'grid_name' => 'test_grid',
                    'grid_scope' => 'test_scope',
                    'grid_parameters' => [
                        'enableFullScreenLayout' => false,
                    ],
                    'grid_render_parameters' => ['foo' => 'bar'],
                ],
                [
                    'grid_name' => 'test_grid',
                    'grid_scope' => 'test_scope',
                    'grid_parameters' => [
                        'enableFullScreenLayout' => false,
                    ],
                    'grid_render_parameters' => ['foo' => 'bar'],
                ]
            ],
        ];
    }
}
