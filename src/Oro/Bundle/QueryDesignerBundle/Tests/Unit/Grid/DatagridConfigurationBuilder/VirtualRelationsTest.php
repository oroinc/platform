<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder\DatagridConfigurationBuilderTestCase;

class VirtualRelationsTest extends DatagridConfigurationBuilderTestCase
{
    /**
     * @param array $columns
     * @param array $virtualRelationQuery
     * @param array $virtualFieldProviderConfig
     * @param array $expected
     *
     * @dataProvider virtualRelationsDataProvider
     */
    public function testVirtualColumns(
        array $columns,
        array $virtualRelationQuery,
        array $virtualFieldProviderConfig,
        array $expected
    ) {
        $entity = 'Acme\Entity\TestEntity';
        $doctrine = $this->getDoctrine(
            [
                $entity => [],
                'Acme\Entity\TestEntity2' => [],
                'Acme\Entity\TestEntity3' => [],
                'Acme\Entity\TestEntity4' => [],
                'Acme\Entity\TestEntity5' => [],
            ]
        );
        $virtualColumnProvider = $this->getVirtualFieldProvider($virtualFieldProviderConfig);
        $model = new QueryDesignerModel();
        $model->setEntity($entity);
        $model->setDefinition(json_encode(['columns' => $columns]));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine, null, $virtualColumnProvider);

        /** @var \PHPUnit\Framework\MockObject\MockObject|VirtualRelationProviderInterface $virtualRelationProvider */
        $virtualRelationProvider = $this
            ->createMock('Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface');

        $virtualRelationProvider->expects($this->any())
            ->method('isVirtualRelation')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use ($virtualRelationQuery) {
                        return !empty($virtualRelationQuery[$className][$fieldName]);
                    }
                )
            );
        $virtualRelationProvider->expects($this->any())
            ->method('getVirtualRelationQuery')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use ($virtualRelationQuery) {
                        if (empty($virtualRelationQuery[$className][$fieldName])) {
                            return [];
                        }

                        return $virtualRelationQuery[$className][$fieldName];
                    }
                )
            );
        $virtualRelationProvider->expects($this->any())
            ->method('getTargetJoinAlias')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use ($virtualRelationQuery) {
                        if (!empty($virtualRelationQuery[$className][$fieldName]['target_join_alias'])) {
                            return $virtualRelationQuery[$className][$fieldName]['target_join_alias'];
                        }

                        $joins = [];
                        foreach ($virtualRelationQuery[$className][$fieldName]['join'] as $typeJoins) {
                            $joins = array_merge($joins, $typeJoins);
                        }

                        if (1 === count($joins)) {
                            $join = reset($joins);

                            return $join['alias'];
                        }

                        return null;
                    }
                )
            );

        $builder->setVirtualRelationProvider($virtualRelationProvider);

        $this->assertEquals($expected, $builder->getConfiguration()->toArray()['source']['query']);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function virtualRelationsDataProvider()
    {
        return [
            'on root entity' => [
                'columns' => [
                    'code' => [
                        'name' => 'alias+Acme\Entity\TestEntity::field',
                        'label' => 'code',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity' => [
                        'alias' => [
                            'root_alias' => 'root_event',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity',
                                        'alias' => 'alias',
                                        'conditionType' => 'WITH',
                                        'condition' => 'alias.field = root_event.field',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t2.field as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 'Acme\Entity\TestEntity',
                                'alias' => 't2',
                                'conditionType' => 'WITH',
                                'condition' => 't2.field = t1.field',
                            ],
                        ],
                    ],
                ],
            ],
            'last in join path' => [
                'columns' => [
                    'website' => [
                        'name' => sprintf(
                            'campaign+%s+%s',
                            'Acme\Entity\TestEntity3::event',
                            'Acme\Entity\TestEntity::website'
                        ),
                        'label' => 'website',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity3' => [
                        'event' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity',
                                        'alias' => 'event',
                                        'conditionType' => 'WITH',
                                        'condition' => 'event.field = entity.field',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t3.website as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 't1.campaign',
                                'alias' => 't2',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.field = t2.field',
                            ],
                        ],
                    ],
                ],
            ],
            'relation in the middle' => [
                'columns' => [
                    'identifier' => [
                        'name' => sprintf(
                            'campaign+%s+%s+%s',
                            'Acme\Entity\TestEntity3::event',
                            'Acme\Entity\TestEntity::website',
                            'Acme\Entity\TestEntity2::identifier'
                        ),
                        'label' => 'identifier',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity3' => [
                        'event' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity',
                                        'alias' => 'event',
                                        'conditionType' => 'WITH',
                                        'condition' => 'event.field = entity.field',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t4.identifier as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 't1.campaign',
                                'alias' => 't2',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.field = t2.field',
                            ],
                            [
                                'join' => 't3.website',
                                'alias' => 't4',
                            ],
                        ],
                    ],
                ],
            ],
            'multiple joins' => [
                'columns' => [
                    'identifier' => [
                        'name' => sprintf(
                            'campaign+%s+%s',
                            'Acme\Entity\TestEntity3::item',
                            'Acme\Entity\TestEntity5::name'
                        ),
                        'label' => 'name',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity3' => [
                        'item' => [
                            'target_join_alias' => 'item_virtual',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity4',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                    [
                                        'join' => 'Acme\Entity\TestEntity5',
                                        'alias' => 'item_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'item_virtual.List = List'
                                            . ' AND entity.id = item_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t4.name as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 't1.campaign',
                                'alias' => 't2',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity4',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.entity = \'Acme\Entity\TestEntity\'',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity5',
                                'alias' => 't4',
                                'conditionType' => 'WITH',
                                'condition' => 't4.List = t3 AND t2.id = t4.entityId',
                            ],
                        ],
                    ],
                ],
            ],
            'selects from first join' => [
                'columns' => [
                    'identifier' => [
                        'name' => sprintf(
                            'list+%s',
                            'Acme\Entity\TestEntity4::name'
                        ),
                        'label' => 'list',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity' => [
                        'list' => [
                            'target_join_alias' => 'List',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity4',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                    [
                                        'join' => 'Acme\Entity\TestEntity5',
                                        'alias' => 'item_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'item_virtual.List = List'
                                            . ' AND entity.id = item_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t2.name as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 'Acme\Entity\TestEntity4',
                                'alias' => 't2',
                                'conditionType' => 'WITH',
                                'condition' => 't2.entity = \'Acme\Entity\TestEntity\'',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity5',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.List = t2 AND t1.id = t3.entityId',
                            ],
                        ],
                    ],
                ],
            ],
            'multiple joins in the middle' => [
                'columns' => [
                    'identifier' => [
                        'name' => sprintf(
                            'campaign+%s+%s+%s',
                            'Acme\Entity\TestEntity3::item',
                            'Acme\Entity\TestEntity5::website',
                            'Acme\Entity\TestEntity2::identifier'
                        ),
                        'label' => 'identifier',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity3' => [
                        'item' => [
                            'target_join_alias' => 'List',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity4',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                ],
                                'inner' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity5',
                                        'alias' => 'item_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'item_virtual.List = List'
                                            . ' AND entity.id = item_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t5.identifier as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'inner' => [
                            [
                                'join' => 'Acme\Entity\TestEntity5',
                                'alias' => 't4',
                                'conditionType' => 'WITH',
                                'condition' => 't4.List = t3 AND t2.id = t4.entityId',
                            ],
                        ],
                        'left' => [
                            [
                                'join' => 't1.campaign',
                                'alias' => 't2',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity4',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.entity = \'Acme\Entity\TestEntity\'',
                            ],
                            [
                                'join' => 't3.website',
                                'alias' => 't5',
                            ],
                        ],
                    ],
                ],
            ],
            'multiple relations in join' => [
                'columns' => [
                    'website' => [
                        'name' => sprintf(
                            'campaign+%s+%s+%s+%s',
                            'Acme\Entity\TestEntity3::event',
                            'Acme\Entity\TestEntity::website',
                            'Acme\Entity\TestEntity2::list',
                            'Acme\Entity\TestEntity4::field'
                        ),
                        'label' => 'code',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity3' => [
                        'event' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity',
                                        'alias' => 'event',
                                        'conditionType' => 'WITH',
                                        'condition' => 'event.field = entity.field',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'Acme\Entity\TestEntity2' => [
                        'list' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity4',
                                        'alias' => 'list',
                                        'conditionType' => 'WITH',
                                        'condition' => 'list.field = entity.field',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t5.field as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 't1.campaign',
                                'alias' => 't2',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.field = t2.field',
                            ],
                            [
                                'join' => 't3.website',
                                'alias' => 't4',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity4',
                                'alias' => 't5',
                                'conditionType' => 'WITH',
                                'condition' => 't5.field = t4.field',
                            ]
                        ],
                    ],
                ],
            ],
            'unidirectional join' => [
                'columns' => [
                    'identifier' => [
                        'name' => sprintf(
                            'list_virtual+%s+%s',
                            sprintf(
                                '%s::%s',
                                'Acme\Entity\TestEntity4',
                                'Acme\Entity\TestEntity2::list'
                            ),
                            'Acme\Entity\TestEntity2::identifier'
                        ),
                        'label' => 'identifier',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity' => [
                        'list_virtual' => [
                            'target_join_alias' => 'List',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity4',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                    [
                                        'join' => 'Acme\Entity\TestEntity5',
                                        'alias' => 'list_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'list_virtual.List = List'
                                            . ' AND entity.id = list_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [],
                'expected' => [
                    'select' => ['t4.identifier as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 'Acme\Entity\TestEntity4',
                                'alias' => 't2',
                                'conditionType' => 'WITH',
                                'condition' => 't2.entity = \'Acme\Entity\TestEntity\'',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity5',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.List = t2 AND t1.id = t3.entityId',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity2',
                                'alias' => 't4',
                                'conditionType' => 'WITH',
                                'condition' => 't4.list = t2',
                            ],
                        ],
                    ],
                ],
            ],
            'with virtual field' => [
                'columns' => [
                    'field' => [
                        'name' => 'event+Acme\Entity\TestEntity::field',
                        'label' => 'field',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity' => [
                        'event' => [
                            'root_alias' => 'root_event',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity',
                                        'alias' => 'event',
                                        'conditionType' => 'WITH',
                                        'condition' => 'event.field = root_event.field',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'virtualFieldProviderConfig' => [
                    [
                        'Acme\Entity\TestEntity',
                        'field',
                        [
                            'select' => [
                                'expr'        => 't3.field',
                                'return_type' => 'string'
                            ],
                            'join'   => [
                                'left' => [
                                    [
                                        'join' => 'Acme\Entity\TestEntity',
                                        'alias' => 't2',
                                        'conditionType' => 'WITH',
                                        'condition' => 't3.field = t1.field',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'select' => ['t3.field as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 'Acme\Entity\TestEntity',
                                'alias' => 't2',
                                'conditionType' => 'WITH',
                                'condition' => 't2.field = t1.field',
                            ],
                            [
                                'join' => 'Acme\Entity\TestEntity',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.field = t1.field',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
