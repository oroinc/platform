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
use Oro\Component\Layout\Exception\InvalidArgumentException;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DatagridTypeTest extends BlockTypeTestCase
{
    /** @var NameStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $nameStrategy;

    /** @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    protected function setUp(): void
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
            ->willReturn('test-grid-test-scope');

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

    public function testBuildViewThrowsExceptionIfGridNameIsNotSpecified()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot resolve options for the block "datagrid_id". Reason: The required option "grid_name" is missing.'
        );

        $this->getBlockView(new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildBlock()
    {
        $gridConfig = $this->createMock(DatagridConfiguration::class);
        $gridConfig->expects($this->once())
            ->method('getAclResource')
            ->willReturn('acl_resource');
        $gridConfig->expects($this->once())
            ->method('offsetGet')
            ->with('columns')
            ->willReturn(['column_1' => true]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('acl_resource')
            ->willReturn(true);

        $this->manager->expects($this->any())
            ->method('getConfigurationForGrid')
            ->with('test-grid')
            ->willReturn($gridConfig);

        $builder = $this->createMock(BlockBuilderInterface::class);
        $builder->expects($this->any())
            ->method('getId')
            ->willReturn('test_grid');

        $layoutManipulator = $this->createMock(LayoutManipulatorInterface::class);
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

        $view = $this->createMock(BlockView::class);
        $view->vars = [
            'block_type' => 'datagrid',
            'unique_block_prefix' => '_product_datagrid',
            'block_prefixes' => ['block', 'container', 'datagrid', '_product_datagrid'],
        ];
        $view->children = [$childView];

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
     */
    public function testConfigureOptions(array $options, array $expectedOptions)
    {
        $datagridType = new DatagridType($this->nameStrategy, $this->manager, $this->authorizationChecker);
        $resolver = new OptionsResolver();
        $datagridType->configureOptions($resolver);

        $actual = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $actual);
    }

    public function optionsDataProvider(): array
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
