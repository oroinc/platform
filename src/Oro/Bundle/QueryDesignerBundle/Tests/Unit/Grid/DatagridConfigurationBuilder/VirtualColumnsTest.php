<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;

class VirtualColumnsTest extends DatagridConfigurationBuilderTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testVirtualColumns()
    {
        $en                    = 'Acme\Entity\TestEntity';
        $en1                   = 'Acme\Entity\TestEntity1';
        $en2                   = 'Acme\Entity\TestEntity2';
        $definition            = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
                ['name' => 'vc1', 'label' => 'lbl2', 'sorting' => 'DESC'],
                ['name' => 'rc1+' . $en1 . '::column2', 'label' => 'lbl3', 'sorting' => ''],
                ['name' => 'rc1+' . $en1 . '::vc2', 'label' => 'lbl4', 'sorting' => ''],
                ['name' => 'vc3', 'label' => 'lbl5', 'sorting' => ''],
            ],
            'filters' => [
                [
                    'columnName' => 'rc1+' . $en1 . '::vc2',
                    'criterion'  => [
                        'filter' => 'string',
                        'data'   => [
                            'type'  => '1',
                            'value' => 'test'
                        ]
                    ]
                ]
            ]
        ];
        $doctrine              = $this->getDoctrine(
            [
                $en  => [
                    'column1' => 'string',
                    'rc1'     => ['nullable' => true],
                ],
                $en1 => [
                    'column2' => 'integer',
                ],
                $en2 => [
                    'name' => 'string',
                ],
            ]
        );
        $virtualColumnProvider = $this->getVirtualFieldProvider(
            [
                [
                    $en,
                    'vc1',
                    [
                        'select' => [
                            'expr'        => 'emails.email',
                            'return_type' => 'string'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'          => 'entity.emails',
                                    'alias'         => 'emails',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'emails.primary = true'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    $en1,
                    'vc2',
                    [
                        'select' => [
                            'expr'        => 'phones.phone',
                            'return_type' => 'string'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'          => 'entity.phones',
                                    'alias'         => 'phones',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'phones.primary = true'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    $en,
                    'vc3',
                    [
                        'select' => [
                            'expr'        => 'COALESCE(entity.regionText, region.name)',
                            'return_type' => 'string'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => 'entity.region',
                                    'alias' => 'region',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine, null, $virtualColumnProvider);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'name'    => 'test_grid',
            'columns' => [
                'c1' => ['frontend_type' => 'string', 'label' => 'lbl1', 'translatable' => false],
                'c2' => ['frontend_type' => 'string', 'label' => 'lbl2', 'translatable' => false],
                'c3' => ['frontend_type' => 'integer', 'label' => 'lbl3', 'translatable' => false],
                'c4' => ['frontend_type' => 'string', 'label' => 'lbl4', 'translatable' => false],
                'c5' => ['frontend_type' => 'string', 'label' => 'lbl5', 'translatable' => false],
            ],
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                    'c3' => ['data_name' => 'c3'],
                    'c4' => ['data_name' => 'c4'],
                    'c5' => ['data_name' => 'c5'],
                ],
                'default' => ['c2' => 'DESC']
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['type' => 'string', 'data_name' => 'c1', 'translatable' => false],
                    'c2' => ['type' => 'string', 'data_name' => 'c2', 'translatable' => false],
                    'c3' => ['type' => 'number', 'data_name' => 'c3', 'translatable' => false],
                    'c4' => ['type' => 'string', 'data_name' => 'c4', 'translatable' => false],
                    'c5' => ['type' => 'string', 'data_name' => 'c5', 'translatable' => false],
                ]
            ],
            'source'  => [
                'query'        => [
                    'select' => [
                        't1.column1 as c1',
                        't4.email as c2',
                        't2.column2 as c3',
                        't3.phone as c4',
                        'COALESCE(t1.regionText, t5.name) as c5',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'  => 't1.rc1',
                                'alias' => 't2'
                            ],
                            [
                                'join'          => 't2.phones',
                                'alias'         => 't3',
                                'conditionType' => 'WITH',
                                'condition'     => 't3.primary = true'
                            ],
                            [
                                'join'          => 't1.emails',
                                'alias'         => 't4',
                                'conditionType' => 'WITH',
                                'condition'     => 't4.primary = true'
                            ],
                            [
                                'join'  => 't1.region',
                                'alias' => 't5',
                            ],
                        ]
                    ]
                ],
                'query_config' => [
                    'filters'        => [
                        [
                            'column'      => 't3.phone',
                            'filter'      => 'string',
                            'filterData'  => [
                                'type'  => '1',
                                'value' => 'test'
                            ],
                            'columnAlias' => 'c4'
                        ],
                    ],
                    'table_aliases'  => [
                        ''                                                                  => 't1',
                        'Acme\Entity\TestEntity::rc1'                                       => 't2',
                        'Acme\Entity\TestEntity::rc1+t2.phones|left|WITH|t3.primary = true' => 't3',
                        't1.emails|left|WITH|t4.primary = true'                             => 't4',
                        't1.region|left'                                                    => 't5',
                    ],
                    'column_aliases' => [
                        'column1'                              => 'c1',
                        'vc1'                                  => 'c2',
                        'rc1+Acme\Entity\TestEntity1::column2' => 'c3',
                        'rc1+Acme\Entity\TestEntity1::vc2'     => 'c4',
                        'vc3'                                  => 'c5',
                    ]
                ],
                'type'         => 'orm',
                'hints'        => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker',
                    ]
                ]
            ],
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.column1'],
                    'c2' => ['data_name' => 't4.email'],
                    'c3' => ['data_name' => 't2.column2'],
                    'c4' => ['data_name' => 't3.phone'],
                    'c5' => ['data_name' => 'COALESCE(t1.regionText, t5.name)']
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testVirtualColumnsForEnum()
    {
        $en                    = 'Acme\Entity\TestEntity';
        $definition            = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
                ['name' => 'vc1', 'label' => 'lbl2', 'sorting' => 'DESC'],
            ],
            'filters' => [
                [
                    'columnName' => 'vc1',
                    'criterion'  => [
                        'filter' => 'enum',
                        'data'   => [
                            'params' => [
                                'class'      => 'Test\EnumValue',
                                'null_value' => ':empty:'
                            ],
                            'value'  => ['status1']
                        ]
                    ]
                ],
            ]
        ];
        $doctrine              = $this->getDoctrine(
            [
                $en => [
                    'column1' => 'string',
                ],
            ]
        );
        $virtualColumnProvider = $this->getVirtualFieldProvider(
            [
                [
                    $en,
                    'vc1',
                    [
                        'select' => [
                            'expr'         => 'status.name',
                            'return_type'  => 'enum',
                            'filter_by_id' => true
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => 'entity.status',
                                    'alias' => 'status'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine, null, $virtualColumnProvider);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => [
                        't1.column1 as c1',
                        't2.name as c2',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'  => 't1.status',
                                'alias' => 't2'
                            ],
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''               => 't1',
                        't1.status|left' => 't2',
                    ],
                    'column_aliases' => [
                        'column1' => 'c1',
                        'vc1'     => 'c2',
                    ],
                    'filters'        => [
                        [
                            'column'      => 't1.vc1',
                            'filter'      => 'enum',
                            'filterData'  => [
                                'params' => [
                                    'class'      => 'Test\EnumValue',
                                    'null_value' => ':empty:'
                                ],
                                'value' => ['status1']
                            ],
                            'columnAlias' => 'c2'
                        ],
                    ]
                ],
                'hints'        => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker',
                    ]
                ]
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'enum', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                ],
                'default' => ['c2' => 'DESC']
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => ['data_name' => 't1.vc1', 'type' => 'enum', 'translatable' => false],
                ]
            ],
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.column1'],
                    'c2' => ['data_name' => 't2.name']
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testVirtualColumnsForDictionary()
    {
        $en                    = 'Acme\Entity\TestEntity';
        $definition            = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
                ['name' => 'vc1', 'label' => 'lbl2', 'sorting' => 'DESC'],
            ],
        ];
        $doctrine              = $this->getDoctrine(
            [
                $en => [
                    'column1' => 'string',
                ],
            ]
        );
        $virtualColumnProvider = $this->getVirtualFieldProvider(
            [
                [
                    $en,
                    'vc1',
                    [
                        'select' => [
                            'expr'         => 'owner.displayName',
                            'return_type'  => 'dictionary',
                            'filter_by_id' => true
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => 'entity.owner',
                                    'alias' => 'owner'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));

        $entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityNameResolver->expects($this->once())
            ->method('getNameDQL')
            ->willReturn('testName');

        $builder = $this->createDatagridConfigurationBuilder(
            $model,
            $doctrine,
            null,
            $virtualColumnProvider,
            [],
            $entityNameResolver
        );
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => [
                        't1.column1 as c1',
                        'testName as c2',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'  => 't1.owner',
                                'alias' => 't2'
                            ],
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''               => 't1',
                        't1.owner|left' => 't2',
                    ],
                    'column_aliases' => [
                        'column1' => 'c1',
                        'vc1'     => 'c2',
                    ],
                ],
                'hints'        => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker',
                    ]
                ]
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'dictionary', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                ],
                'default' => ['c2' => 'DESC']
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => ['data_name' => 't1.vc1', 'type' => 'dictionary', 'translatable' => false],
                ]
            ],
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.column1'],
                    'c2' => ['data_name' => 'testName']
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }
}
