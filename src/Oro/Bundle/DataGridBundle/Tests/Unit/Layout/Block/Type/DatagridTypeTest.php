<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Layout\Block\Type\DatagridType;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DatagridTypeTest extends BlockTypeTestCase
{
    /** @var NameStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $nameStrategy;

    /** @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject  */
    protected $manager;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    protected function setUp()
    {
        parent::setUp();

        $this->nameStrategy = $this->createMock(NameStrategyInterface::class);
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
    }

    public function testBuildView()
    {
        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->with('test-grid', 'test-scope')
            ->will($this->returnValue('test-grid-test-scope'));

        $view = $this->getBlockView(
            new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker),
            [
                'grid_name'              => 'test-grid',
                'grid_scope'             => 'test-scope',
                'grid_parameters'        => ['foo' => 'bar'],
                'grid_render_parameters' => ['foo1' => 'bar1'],
                'split_to_cells'         => true,
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
            new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker),
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
            new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker),
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
        $this->getBlockView(new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildBlock()
    {
        /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $gridConfig */
        $gridConfig = $this
            ->createMock('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration');

        $gridConfig->expects($this->once())
            ->method('getAclResource')
            ->will($this->returnValue('acl_resource'));

        $gridConfig->expects($this->once())
            ->method('offsetGet')
            ->with('columns')
            ->will($this->returnValue(['column_1' => true]));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('acl_resource')
            ->will($this->returnValue(true));

        $this->manager
            ->expects($this->any())
            ->method('getConfigurationForGrid')
            ->with('test-grid')
            ->will($this->returnValue($gridConfig));

        /** @var BlockBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock('Oro\Component\Layout\BlockBuilderInterface');
        $builder->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('test_grid'));

        $layoutManipulator = $this->createMock('Oro\Component\Layout\LayoutManipulatorInterface');
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
                    [
                        'additional_block_prefixes' => [
                            'additional_prefix',
                            '__test__datagrid_header_row',
                            '__test__import__datagrid_header_row'
                        ]
                    ]
                ],
                [
                    'test_grid_row',
                    'test_grid',
                    'datagrid_row',
                    [
                        'additional_block_prefixes' => [
                            'additional_prefix',
                            '__test__datagrid_row',
                            '__test__import__datagrid_row'
                        ]
                    ]
                ],
                [
                    'test_grid_header_cell_column_1',
                    'test_grid_header_row',
                    'datagrid_header_cell',
                    [
                        'column_name' => 'column_1',
                        'additional_block_prefixes' => [
                            'additional_prefix',
                            '__test__datagrid_header_cell',
                            '__test__import__datagrid_header_cell'
                        ]
                    ],
                ],
                [
                    'test_grid_cell_column_1',
                    'test_grid_row',
                    'datagrid_cell',
                    [
                        'column_name' => 'column_1',
                        'additional_block_prefixes' => [
                            'additional_prefix',
                            '__test__datagrid_cell',
                            '__test__import__datagrid_cell'
                        ]
                    ],
                ],
                [
                    'test_grid_cell_column_1_value',
                    'test_grid_cell_column_1',
                    'datagrid_cell_value',
                    [
                        'column_name' => 'column_1',
                        'additional_block_prefixes' => [
                            'additional_prefix',
                            '__test__datagrid_cell_value',
                            '__test__import__datagrid_cell_value'
                        ]
                    ],
                ]
            );

        $type = new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker);
        $options = $this->resolveOptions($type, [
            'grid_name' => 'test-grid',
            'split_to_cells' => true,
            'additional_block_prefixes' => [
                'additional_prefix',
                '__test__datagrid',
                '__test__import__datagrid'
            ]
        ]);
        $type->buildBlock($builder, new Options($options));
    }

    public function testFinishView()
    {
        $type = new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker);

        $childView = $this->createMock(BlockView::class);
        $childView->vars = [
            'block_type' => 'datagrid_toolbar',
            'unique_block_prefix' => '_product_datagrid_toolbar',
            'block_prefixes' => ['block', 'datagrid_toolbar', '_product_datagrid_toolbar'],
        ];

        /** @var BlockView|\PHPUnit\Framework\MockObject\MockObject $view */
        $view = $this->createMock(BlockView::class);
        $view->vars = [
            'block_type' => 'datagrid',
            'unique_block_prefix' => '_product_datagrid',
            'block_prefixes' => ['block', 'container', 'datagrid', '_product_datagrid'],
        ];
        $view->children = [$childView];

        /** @var BlockInterface|\PHPUnit\Framework\MockObject\MockObject $block */
        $block = $this->createMock(BlockInterface::class);
        $block->expects($this->any())
            ->method('getId')
            ->willReturn('product_datagrid');

        $type->finishView($view, $block);

        $this->assertEquals(
            ['block', 'container', 'datagrid', '_product_datagrid'],
            $view->vars['block_prefixes']
        );
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expectedOptions
     */
    public function testConfigureOptions(array $options, array $expectedOptions)
    {
        $datagridType = new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker);
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
