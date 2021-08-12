<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\ColumnOptionsGuesserMock;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\SegmentBundle\Grid\SegmentDatagridConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class SegmentDatagridConfigurationBuilderTest extends SegmentDefinitionTestCase
{
    private const TEST_GRID_NAME = 'test';

    public function testConfiguration()
    {
        $segment = $this->getSegment();
        $doctrineHelper = $this->getDoctrineHelper(
            [self::TEST_ENTITY => ['userName' => 'string']],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );
        $configManager = $this->createMock(ConfigManager::class);

        $entityMetadata = new EntityMetadata(\stdClass::class);
        $entityMetadata->routeView = 'route';

        $configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($segment->getEntity())
            ->willReturn($entityMetadata);

        $builder = new SegmentDatagridConfigurationBuilder(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->getVirtualRelationProvider(),
            $doctrineHelper,
            new DatagridGuesser([new ColumnOptionsGuesserMock()]),
            $this->getEntityNameResolver()
        );

        $builder->setGridName(self::TEST_GRID_NAME);
        $builder->setSource($segment);
        $builder->setConfigManager($configManager);

        $result = $builder->getConfiguration()->toArray();
        $expected = $this->getExpectedDefinition('route');

        $this->assertEquals($expected, $result);
    }

    /**
     * Test grid definition when no route exists for entity in config
     * no grid actions should be added
     */
    public function testNoRouteConfiguration()
    {
        $segment = $this->getSegment();
        $doctrineHelper = $this->getDoctrineHelper(
            [self::TEST_ENTITY => ['userName' => 'string']],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );
        $configManager = $this->createMock(ConfigManager::class);

        $builder = new SegmentDatagridConfigurationBuilder(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->getVirtualRelationProvider(),
            $doctrineHelper,
            new DatagridGuesser([new ColumnOptionsGuesserMock()]),
            $this->getEntityNameResolver()
        );

        $builder->setConfigManager($configManager);
        $builder->setGridName(self::TEST_GRID_NAME);
        $builder->setSource($segment);

        $result = $builder->getConfiguration()->toArray();
        $expected = $this->getExpectedDefinition();

        $this->assertEquals($expected, $result);
    }

    private function getExpectedDefinition(string $route = null): array
    {
        $definition = [
            'name'    => self::TEST_GRID_NAME,
            'columns' => ['c1' => ['label' => 'User name', 'translatable' => false, 'frontend_type' => 'string']],
            'sorters' => ['columns' => ['c1' => ['data_name' => 'c1']]],
            'filters' => ['columns' => ['c1' => ['type' => 'string', 'data_name' => 'c1', 'translatable' => false]]],
            'source'  => [
                'query'        => [
                    'select' => ['t1.userName as c1'],
                    'from'   => [['table' => self::TEST_ENTITY, 'alias' => 't1']]
                ],
                'query_config' => [
                    'filters'        => [
                        [
                            'column'     => sprintf('t1.%s', self::TEST_IDENTIFIER_NAME),
                            'filter'     => 'segment',
                            'filterData' => ['value' => self::TEST_IDENTIFIER]
                        ]
                    ],
                    'table_aliases'  => ['' => 't1'],
                    'column_aliases' => ['userName' => 'c1',]
                ],
                'type'         => 'orm',
                'acl_resource' => 'oro_segment_view',
            ],
            'options' => ['export' => true],
            'fields_acl' => ['columns' => ['c1' => ['data_name' => 't1.userName']]]
        ];

        if (!empty($route)) {
            $definition = array_merge(
                $definition,
                [
                    'properties' => [
                        'id'        => null,
                        'view_link' => [
                            'type'   => 'url',
                            'route'  => 'route',
                            'params' => ['id']
                        ]
                    ],
                    'actions'    => [
                        'view' => [
                            'type'         => 'navigate',
                            'acl_resource' => 'VIEW;entity:AcmeBundle:UserEntity',
                            'label'        => 'oro.report.datagrid.row.action.view',
                            'icon'         => 'eye',
                            'link'         => 'view_link',
                            'rowAction'    => true,
                        ],
                    ],
                ]
            );
            $definition['source']['query']['select'] = ['t1.userName as c1', 't1.' . self::TEST_IDENTIFIER_NAME];
            $definition['source']['hints'][] = 'HINT_TRANSLATABLE';
        }

        return $definition;
    }
}
