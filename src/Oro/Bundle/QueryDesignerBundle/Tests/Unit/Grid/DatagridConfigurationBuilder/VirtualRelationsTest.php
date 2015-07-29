<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Doctrine\ORM\Query;

use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;

class VirtualRelationsTest extends DatagridConfigurationBuilderTestCase
{
    /**
     * @param array $columns
     * @param array $virtualRelationQuery
     * @param array $expected
     *
     * @dataProvider virtualRelationsDataProvider
     */
    public function testVirtualColumns(array $columns, array $virtualRelationQuery, array $expected)
    {
        $entity = 'Acme\Entity\TestEntity';
        $doctrine = $this->getDoctrine(
            [
                $entity => [],
                'Oro\Bundle\TrackingBundle\Entity\TrackingEvent' => [],
                'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite' => [],
                'Oro\Bundle\TrackingBundle\Entity\Campaign' => [],
                'Oro\Bundle\TrackingBundle\Entity\List' => [],
                'Oro\Bundle\TrackingBundle\Entity\ListItem' => [],
            ]
        );
        $virtualColumnProvider = $this->getVirtualFieldProvider();
        $model = new QueryDesignerModel();
        $model->setEntity($entity);
        $model->setDefinition(json_encode(['columns' => $columns]));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine, null, $virtualColumnProvider);

        /** @var \PHPUnit_Framework_MockObject_MockObject|VirtualRelationProviderInterface $virtualRelationProvider */
        $virtualRelationProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface');

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
                        'name' => 'trackingEvent+Oro\Bundle\TrackingBundle\Entity\TrackingEvent::code',
                        'label' => 'code',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Acme\Entity\TestEntity' => [
                        'trackingEvent' => [
                            'root_alias' => 'root_event',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                        'alias' => 'trackingEvent',
                                        'conditionType' => 'WITH',
                                        'condition' => 'trackingEvent.code = root_event.code',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'select' => ['t2.code as c1'],
                    'from' => [
                        [
                            'table' => 'Acme\Entity\TestEntity',
                            'alias' => 't1',
                        ],
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                'alias' => 't2',
                                'conditionType' => 'WITH',
                                'condition' => 't2.code = t1.code',
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
                            'Oro\Bundle\TrackingBundle\Entity\Campaign::trackingEvent',
                            'Oro\Bundle\TrackingBundle\Entity\TrackingEvent::website'
                        ),
                        'label' => 'website',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Oro\Bundle\TrackingBundle\Entity\Campaign' => [
                        'trackingEvent' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                        'alias' => 'trackingEvent',
                                        'conditionType' => 'WITH',
                                        'condition' => 'trackingEvent.code = entity.code',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.code = t2.code',
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
                            'Oro\Bundle\TrackingBundle\Entity\Campaign::trackingEvent',
                            'Oro\Bundle\TrackingBundle\Entity\TrackingEvent::website',
                            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite::identifier'
                        ),
                        'label' => 'identifier',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Oro\Bundle\TrackingBundle\Entity\Campaign' => [
                        'trackingEvent' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                        'alias' => 'trackingEvent',
                                        'conditionType' => 'WITH',
                                        'condition' => 'trackingEvent.code = entity.code',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.code = t2.code',
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
                            'Oro\Bundle\TrackingBundle\Entity\Campaign::listItem',
                            'Oro\Bundle\TrackingBundle\Entity\ListItem::name'
                        ),
                        'label' => 'name',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Oro\Bundle\TrackingBundle\Entity\Campaign' => [
                        'listItem' => [
                            'target_join_alias' => 'ListItem_virtual',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
                                        'alias' => 'ListItem_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'ListItem_virtual.List = List'
                                            . ' AND entity.id = ListItem_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.entity = \'Acme\Entity\TestEntity\'',
                            ],
                            [
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
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
                            'Oro\Bundle\TrackingBundle\Entity\List::name'
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
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
                                        'alias' => 'ListItem_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'ListItem_virtual.List = List'
                                            . ' AND entity.id = ListItem_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                'alias' => 't2',
                                'conditionType' => 'WITH',
                                'condition' => 't2.entity = \'Acme\Entity\TestEntity\'',
                            ],
                            [
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
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
                            'Oro\Bundle\TrackingBundle\Entity\Campaign::listItem',
                            'Oro\Bundle\TrackingBundle\Entity\ListItem::website',
                            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite::identifier'
                        ),
                        'label' => 'identifier',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Oro\Bundle\TrackingBundle\Entity\Campaign' => [
                        'listItem' => [
                            'target_join_alias' => 'List',
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                ],
                                'inner' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
                                        'alias' => 'ListItem_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'ListItem_virtual.List = List'
                                            . ' AND entity.id = ListItem_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
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
                            'Oro\Bundle\TrackingBundle\Entity\Campaign::trackingEvent',
                            'Oro\Bundle\TrackingBundle\Entity\TrackingEvent::website',
                            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite::list',
                            'Oro\Bundle\TrackingBundle\Entity\List::code'
                        ),
                        'label' => 'code',
                    ],
                ],
                'virtualRelationQuery' => [
                    'Oro\Bundle\TrackingBundle\Entity\Campaign' => [
                        'trackingEvent' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                        'alias' => 'trackingEvent',
                                        'conditionType' => 'WITH',
                                        'condition' => 'trackingEvent.code = entity.code',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite' => [
                        'list' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                        'alias' => 'list',
                                        'conditionType' => 'WITH',
                                        'condition' => 'list.code = entity.code',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'select' => ['t5.code as c1'],
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingEvent',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.code = t2.code',
                            ],
                            [
                                'join' => 't3.website',
                                'alias' => 't4',
                            ],
                            [
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                'alias' => 't5',
                                'conditionType' => 'WITH',
                                'condition' => 't5.code = t4.code',
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
                                'Oro\Bundle\TrackingBundle\Entity\List',
                                'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite::list'
                            ),
                            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite::identifier'
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
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                        'alias' => 'List',
                                        'conditionType' => 'WITH',
                                        'condition' => 'List.entity = \'Acme\Entity\TestEntity\'',
                                    ],
                                    [
                                        'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
                                        'alias' => 'ListItem_virtual',
                                        'conditionType' => 'WITH',
                                        'condition' => 'ListItem_virtual.List = List'
                                            . ' AND entity.id = ListItem_virtual.entityId',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\List',
                                'alias' => 't2',
                                'conditionType' => 'WITH',
                                'condition' => 't2.entity = \'Acme\Entity\TestEntity\'',
                            ],
                            [
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\ListItem',
                                'alias' => 't3',
                                'conditionType' => 'WITH',
                                'condition' => 't3.List = t2 AND t1.id = t3.entityId',
                            ],
                            [
                                'join' => 'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite',
                                'alias' => 't4',
                                'conditionType' => 'WITH',
                                'condition' => 't4.list = t2',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
