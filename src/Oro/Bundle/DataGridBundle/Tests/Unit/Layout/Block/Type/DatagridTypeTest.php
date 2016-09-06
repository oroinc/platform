<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Layout\Block\Type\DatagridType;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockBuilderInterface;

class DatagridTypeTest extends BlockTypeTestCase
{
    /** @var NameStrategyInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $nameStrategy;

    /** @var ManagerInterface|\PHPUnit_Framework_MockObject_MockObject  */
    protected $manager;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        parent::setUp();

        $this->nameStrategy = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface');
        $this->manager = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface');
        $this->securityFacade = $this->getMock('Oro\Bundle\SecurityBundle\SecurityFacade', [], [], '', false);
    }

    public function testBuildView()
    {
        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->with('test-grid', 'test-scope')
            ->will($this->returnValue('test-grid-test-scope'));

        $view = $this->getBlockView(
            new DatagridType($this->nameStrategy, $this->manager, $this->securityFacade),
            [
                'grid_name'       => 'test-grid',
                'grid_scope'      => 'test-scope',
                'grid_parameters' => ['foo' => 'bar'],
                'grid_render_parameters' => ['foo1' => 'bar1'],
                'split_to_cells'  => true
            ]
        );

        $this->assertEquals('test-grid', $view->vars['grid_name']);
        $this->assertEquals('test-grid-test-scope', $view->vars['grid_full_name']);
        $this->assertEquals('test-scope', $view->vars['grid_scope']);
        $this->assertEquals(['foo' => 'bar'], $view->vars['grid_parameters']);
        $this->assertEquals(['foo1' => 'bar1'], $view->vars['grid_render_parameters']);
        $this->assertEquals(true, $view->vars['split_to_cells']);
    }

    public function testBuildViewWithoutScope()
    {
        $this->nameStrategy->expects($this->never())
            ->method('buildGridFullName');

        $view = $this->getBlockView(
            new DatagridType($this->nameStrategy, $this->manager, $this->securityFacade),
            [
                'grid_name'       => 'test-grid',
                'grid_parameters' => ['foo' => 'bar'],
                'grid_render_parameters' => ['foo1' => 'bar1'],
                'split_to_cells'  => true,
            ]
        );

        $this->assertEquals('test-grid', $view->vars['grid_name']);
        $this->assertEquals('test-grid', $view->vars['grid_full_name']);
        $this->assertFalse(isset($view->vars['grid_scope']));
        $this->assertEquals(['foo' => 'bar'], $view->vars['grid_parameters']);
        $this->assertEquals(['foo1' => 'bar1'], $view->vars['grid_render_parameters']);
        $this->assertEquals(true, $view->vars['split_to_cells']);
    }

    public function testBuildViewWithParamsOverwrite()
    {
        $view = $this->getBlockView(
            new DatagridType($this->nameStrategy, $this->manager, $this->securityFacade),
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
        $this->getBlockView(new DatagridType($this->nameStrategy, $this->manager, $this->securityFacade));
    }

    public function testBuildBlock()
    {
        /** @var DatagridConfiguration|\PHPUnit_Framework_MockObject_MockObject $gridConfig */
        $gridConfig = $this
            ->getMock('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration', [], [], '', false);

        $gridConfig->expects($this->once())
            ->method('getAclResource')
            ->will($this->returnValue('acl_resource'));

        $gridConfig->expects($this->once())
            ->method('offsetGet')
            ->with('columns')
            ->will($this->returnValue(['column_1' => true]));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with('acl_resource')
            ->will($this->returnValue(true));

        $this->manager
            ->expects($this->any())
            ->method('getConfigurationForGrid')
            ->with('test-grid')
            ->will($this->returnValue($gridConfig));

        /** @var BlockBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('test_grid'));

        $layoutManipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $builder->expects($this->exactly(5))
            ->method('getLayoutManipulator')
            ->willReturn($layoutManipulator);

        $layoutManipulator->expects($this->exactly(5))
            ->method('add')
            ->withConsecutive(
                [
                    'test_grid_header_row',
                    'test_grid',
                    'datagrid_header_row',
                ],
                [
                    'test_grid_row',
                    'test_grid',
                    'datagrid_row',
                ],
                [
                    'test_grid_header_cell_column_1',
                    'test_grid_header_row',
                    'datagrid_header_cell',
                    [
                        'column_name' => 'column_1',
                    ],
                ],
                [
                    'test_grid_cell_column_1',
                    'test_grid_row',
                    'datagrid_cell',
                    [
                        'column_name' => 'column_1',
                    ],
                ],
                [
                    'test_grid_cell_column_1_value',
                    'test_grid_cell_column_1',
                    'datagrid_cell_value',
                    [
                        'column_name' => 'column_1',
                    ],
                ]
            );

        $type = new DatagridType($this->nameStrategy, $this->manager, $this->securityFacade);
        $options = $this->resolveOptions($type, ['grid_name' => 'test-grid', 'split_to_cells' => true]);
        $type->buildBlock($builder, $options);
    }

    public function testGetName()
    {
        $type = new DatagridType($this->nameStrategy, $this->manager, $this->securityFacade);

        $this->assertSame(DatagridType::NAME, $type->getName());
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expectedOptions
     */
    public function testSetDefaultOptions(array $options, array $expectedOptions)
    {
        $datagridType = new DatagridType($this->nameStrategy, $this->manager, $this->securityFacade);
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
                    'split_to_cells' => false,
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
                    'split_to_cells' => true,
                ],
                [
                    'grid_name' => 'test_grid',
                    'grid_scope' => 'test_scope',
                    'grid_parameters' => [
                        'enableFullScreenLayout' => false,
                    ],
                    'grid_render_parameters' => ['foo' => 'bar'],
                    'split_to_cells' => true,
                ]
            ],
        ];
    }
}
