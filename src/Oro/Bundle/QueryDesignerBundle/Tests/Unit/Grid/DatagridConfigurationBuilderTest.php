<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid;

use Oro\Bundle\QueryDesignerBundle\Tests\Unit\OrmQueryConverterTest;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidFiltersException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DatagridConfigurationBuilderTest extends OrmQueryConverterTest
{
    /**
     * @expectedException \Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The "columns" definition does not exist.
     */
    public function testEmpty()
    {
        $model = new QueryDesignerModel();
        $model->setDefinition(json_encode([]));
        $this->createDatagridConfigurationBuilder($model);
    }

    /**
     * @expectedException \Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The "columns" definition must not be empty.
     */
    public function testEmptyColumns()
    {
        $model = new QueryDesignerModel();
        $model->setDefinition(json_encode(['columns' => []]));
        $this->createDatagridConfigurationBuilder($model);
    }

    public function testNoFilters()
    {
        $en         = 'Acme\Entity\TestEntity';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => '']
            ]
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en => ['column1' => 'string']
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => ['t1.column1 as c1'],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        '' => 't1'
                    ],
                    'column_aliases' => [
                        'column1' => 'c1',
                    ],
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1']
                ]
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testNoJoins()
    {
        $en         = 'Acme\Entity\TestEntity';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => '']
            ],
            'filters' => []
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en => ['column1' => 'string']
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => ['t1.column1 as c1'],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        '' => 't1'
                    ],
                    'column_aliases' => [
                        'column1' => 'c1',
                    ],
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1']
                ]
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testJoinFromColumns()
    {
        $en         = 'Acme\Entity\TestEntity';
        $en1        = 'Acme\Entity\TestEntity1';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
                ['name' => 'rc1+' . $en1 . '::column2', 'label' => 'lbl2', 'sorting' => ''],
            ],
            'filters' => []
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en  => [
                    'column1' => 'string',
                    'rc1'     => ['nullable' => true]
                ],
                $en1 => ['column2' => 'string'],
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => [
                        't1.column1 as c1',
                        't2.column2 as c2',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 't1.rc1', 'alias' => 't2'],
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''            => 't1',
                        $en . '::rc1' => 't2'
                    ],
                    'column_aliases' => [
                        'column1'                   => 'c1',
                        'rc1+' . $en1 . '::column2' => 'c2',
                    ],
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'string', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2']
                ]
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => ['data_name' => 'c2', 'type' => 'string', 'translatable' => false]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUnidirectionalJoinFromColumns()
    {
        $en         = 'Acme\Entity\TestEntity';
        $en1        = 'Acme\Entity\TestEntity1';
        $en2        = 'Acme\Entity\TestEntity2';
        $enR1       = 'Acme\Entity\TestEntityReverse1';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
                ['name' => $enR1 . '::rc1+' . $en1 . '::column2', 'label' => 'lbl2', 'sorting' => ''],
                ['name' => $enR1 . '::rc2+' . $en1 . '::column2', 'label' => 'lbl3', 'sorting' => ''],
                [
                    'name'    => 'rc1+' . $en1 . '::' . $enR1 . '::rc1+' . $en2 . '::column1',
                    'label'   => 'lbl4',
                    'sorting' => ''
                ],
            ],
            'filters' => []
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en   => [
                    'column1' => 'string',
                    'rc1'     => ['nullable' => true],
                ],
                $en1  => [
                    'column2' => 'string',
                ],
                $en2  => [
                    'column1' => 'string',
                ],
                $enR1 => [
                    'rc1' => ['nullable' => true],
                    'rc2' => ['nullable' => false],
                ],
            ],
            [
                $en  => ['id'],
                $en1 => ['id'],
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => [
                        't1.column1 as c1',
                        't2.column2 as c2',
                        't3.column2 as c3',
                        't5.column1 as c4',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left'  => [
                            [
                                'join'          => $enR1,
                                'alias'         => 't2',
                                'conditionType' => 'WITH',
                                'condition'     => 't2.rc1 = t1'
                            ],
                            [
                                'join'  => 't1.rc1',
                                'alias' => 't4',
                            ],
                            [
                                'join'          => $enR1,
                                'alias'         => 't5',
                                'conditionType' => 'WITH',
                                'condition'     => 't5.rc1 = t4'
                            ],
                        ],
                        'inner' => [
                            [
                                'join'          => $enR1,
                                'alias'         => 't3',
                                'conditionType' => 'WITH',
                                'condition'     => 't3.rc2 = t1'
                            ],
                        ],
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''                                             => 't1',
                        $en . '::' . $enR1 . '::rc1'                   => 't2',
                        $en . '::' . $enR1 . '::rc2'                   => 't3',
                        $en . '::rc1'                                  => 't4',
                        $en . '::rc1+' . $en1 . '::' . $enR1 . '::rc1' => 't5',
                    ],
                    'column_aliases' => [
                        'column1'                                                    => 'c1',
                        $enR1 . '::rc1+' . $en1 . '::column2'                        => 'c2',
                        $enR1 . '::rc2+' . $en1 . '::column2'                        => 'c3',
                        'rc1+' . $en1 . '::' . $enR1 . '::rc1+' . $en2 . '::column1' => 'c4',
                    ],
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'string', 'translatable' => false],
                'c3' => ['label' => 'lbl3', 'frontend_type' => 'string', 'translatable' => false],
                'c4' => ['label' => 'lbl4', 'frontend_type' => 'string', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                    'c3' => ['data_name' => 'c3'],
                    'c4' => ['data_name' => 'c4'],
                ]
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => ['data_name' => 'c2', 'type' => 'string', 'translatable' => false],
                    'c3' => ['data_name' => 'c3', 'type' => 'string', 'translatable' => false],
                    'c4' => ['data_name' => 'c4', 'type' => 'string', 'translatable' => false],
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testVirtualColumns()
    {
        $en                    = 'Acme\Entity\TestEntity';
        $en1                   = 'Acme\Entity\TestEntity1';
        $definition            = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
                ['name' => 'vc1', 'label' => 'lbl2', 'sorting' => 'DESC'],
                ['name' => 'rc1+' . $en1 . '::column2', 'label' => 'lbl3', 'sorting' => ''],
                ['name' => 'rc1+' . $en1 . '::vc2', 'label' => 'lbl4', 'sorting' => ''],
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
                ],
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
                        't4.email as c2',
                        't2.column2 as c3',
                        't3.phone as c4',
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
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''                                                                  => 't1',
                        'Acme\Entity\TestEntity::rc1'                                       => 't2',
                        'Acme\Entity\TestEntity::rc1+t2.phones|left|WITH|t3.primary = true' => 't3',
                        't1.emails|left|WITH|t4.primary = true'                             => 't4',
                    ],
                    'column_aliases' => [
                        'column1'                              => 'c1',
                        'vc1'                                  => 'c2',
                        'rc1+Acme\Entity\TestEntity1::column2' => 'c3',
                        'rc1+Acme\Entity\TestEntity1::vc2'     => 'c4'
                    ],
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
                    ]
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'string', 'translatable' => false],
                'c3' => ['label' => 'lbl3', 'frontend_type' => 'integer', 'translatable' => false],
                'c4' => ['label' => 'lbl4', 'frontend_type' => 'string', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                    'c3' => ['data_name' => 'c3'],
                    'c4' => ['data_name' => 'c4'],
                ],
                'default' => ['c2' => 'DESC']
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => ['data_name' => 'c2', 'type' => 'string', 'translatable' => false],
                    'c3' => ['data_name' => 'c3', 'type' => 'number', 'translatable' => false],
                    'c4' => ['data_name' => 'c4', 'type' => 'string', 'translatable' => false],
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testJoinFromFilters()
    {
        $en         = 'Acme\Entity\TestEntity';
        $en1        = 'Acme\Entity\TestEntity1';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
            ],
            'filters' => [
                [
                    'columnName' => 'rc1+' . $en1 . '::column2',
                    'criterion'  => [
                        'filter' => 'string',
                        'data'   => [
                            'type'  => '1',
                            'value' => 'test'
                        ]
                    ]
                ],
            ],
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en  => [
                    'column1' => 'string',
                    'rc1'     => ['nullable' => true]
                ],
                $en1 => ['column2' => 'string'],
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => [
                        't1.column1 as c1',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 't1.rc1', 'alias' => 't2'],
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''            => 't1',
                        $en . '::rc1' => 't2'
                    ],
                    'column_aliases' => [
                        'column1' => 'c1',
                    ],
                    'filters'        => [
                        [
                            'column'     => 't2.column2',
                            'filter'     => 'string',
                            'filterData' => [
                                'type'  => '1',
                                'value' => 'test'
                            ]
                        ],
                    ]
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1']
                ]
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGrouping()
    {
        $en               = 'Acme\Entity\TestEntity';
        $definition       = [
            'columns'          => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => 'DESC'],
                [
                    'name'    => 'column2',
                    'label'   => 'lbl2',
                    'sorting' => '',
                    'func'    => [
                        'name'       => 'Count',
                        'group_name' => 'string',
                        'group_type' => 'aggregates'
                    ]
                ]
            ],
            'filters'          => [],
            'grouping_columns' => [
                ['name' => 'column1']
            ],
        ];
        $doctrine         = $this->getDoctrine(
            [
                $en => ['column1' => 'string'],
                $en => ['column2' => 'string']
            ]
        );
        $functionProvider = $this->getFunctionProvider(
            [
                [
                    'Count',
                    'string',
                    'aggregates',
                    ['name' => 'Count', 'return_type' => 'integer', 'expr' => 'COUNT($column)']
                ]
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine, $functionProvider);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select'  => [
                        't1.column1 as c1',
                        'COUNT(t1.column2) as c2'
                    ],
                    'from'    => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'groupBy' => 't1.column1'
                ],
                'query_config' => [
                    'table_aliases'  => [
                        '' => 't1'
                    ],
                    'column_aliases' => [
                        'column1'                          => 'c1',
                        'column2(Count,string,aggregates)' => 'c2'
                    ],
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'integer', 'translatable' => false],
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                ],
                'default' => ['c1' => 'DESC']
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => [
                        'data_name'        => 'c2',
                        'type'             => 'number',
                        'translatable'     => false,
                        'filter_by_having' => true
                    ],
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testComplexQuery()
    {
        $en         = 'Acme\Entity\TestEntity';
        $en1        = 'Acme\Entity\TestEntity1';
        $en2        = 'Acme\Entity\TestEntity2';
        $en3        = 'Acme\Entity\TestEntity3';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => 'DESC'],
                ['name' => 'rc1+' . $en1 . '::column2', 'label' => 'lbl2', 'sorting' => ''],
                ['name' => 'rc2+' . $en2 . '::column3', 'label' => 'lbl3', 'sorting' => 'ASC'],
            ],
            'filters' => [
                [
                    'columnName' => 'rc1+' . $en1 . '::column2',
                    'criterion'  => [
                        'filter' => 'string',
                        'data'   => [
                            'type'  => '1',
                            'value' => 'test'
                        ]
                    ]
                ],
                'OR',
                [
                    [
                        'columnName' => 'rc1+' . $en1 . '::rc4+' . $en3 . '::column5',
                        'criterion'  => [
                            'filter' => 'string',
                            'data'   => [
                                'type'  => '1',
                                'value' => 'test'
                            ]
                        ]
                    ],
                    'and',
                    [
                        'columnName' => 'rc1+' . $en1 . '::rc4+' . $en3 . '::column6',
                        'criterion'  => [
                            'filter' => 'string',
                            'data'   => [
                                'type'  => '1',
                                'value' => 'test'
                            ]
                        ]
                    ],
                ],
            ],
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en  => [
                    'column1' => 'string',
                    'rc1'     => ['nullable' => true],
                    'rc2'     => ['nullable' => true]
                ],
                $en1 => [
                    'column2' => 'integer',
                    'rc4'     => ['nullable' => true]
                ],
                $en2 => ['column3' => 'float'],
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));
        $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
        $result  = $builder->getConfiguration()->toArray();

        $expected = [
            'source'  => [
                'type'         => 'orm',
                'query'        => [
                    'select' => [
                        't1.column1 as c1',
                        't2.column2 as c2',
                        't4.column3 as c3',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 't1.rc1', 'alias' => 't2'],
                            ['join' => 't2.rc4', 'alias' => 't3'],
                            ['join' => 't1.rc2', 'alias' => 't4'],
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''                              => 't1',
                        $en . '::rc1'                   => 't2',
                        $en . '::rc1+' . $en1 . '::rc4' => 't3',
                        $en . '::rc2'                   => 't4',
                    ],
                    'column_aliases' => [
                        'column1'                   => 'c1',
                        'rc1+' . $en1 . '::column2' => 'c2',
                        'rc2+' . $en2 . '::column3' => 'c3',
                    ],
                    'filters'        => [
                        [
                            'column'      => 't2.column2',
                            'filter'      => 'string',
                            'filterData'  => [
                                'type'  => '1',
                                'value' => 'test'
                            ],
                            'columnAlias' => 'c2'
                        ],
                        'OR',
                        [
                            [
                                'column'     => 't3.column5',
                                'filter'     => 'string',
                                'filterData' => [
                                    'type'  => '1',
                                    'value' => 'test'
                                ]
                            ],
                            'AND',
                            [
                                'column'     => 't3.column6',
                                'filter'     => 'string',
                                'filterData' => [
                                    'type'  => '1',
                                    'value' => 'test'
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'integer', 'translatable' => false],
                'c3' => ['label' => 'lbl3', 'frontend_type' => 'decimal', 'translatable' => false],
            ],
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                    'c3' => ['data_name' => 'c3']
                ],
                'default' => [
                    'c1' => 'DESC',
                    'c3' => 'ASC',
                ]
            ],
            'name'    => 'test_grid',
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => ['data_name' => 'c2', 'type' => 'number', 'translatable' => false],
                    'c3' => ['data_name' => 'c3', 'type' => 'number', 'translatable' => false]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider invalidFiltersStructureProvider
     */
    public function testInvalidFiltersStructure($expectedExceptionMessage, $filters)
    {
        $en         = 'Acme\Entity\TestEntity';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
            ],
            'filters' => $filters,
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en => [
                    'column1' => 'string',
                ],
            ]
        );

        $model = new QueryDesignerModel();
        $model->setEntity($en);
        $model->setDefinition(json_encode($definition));

        try {
            $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
            $builder->getConfiguration()->toArray();
            $this->fail('Expected "Oro\Bundle\QueryDesignerBundle\Exception\InvalidFiltersException" exception.');
        } catch (InvalidFiltersException $ex) {
            if (false === strpos($ex->getMessage(), $expectedExceptionMessage)) {
                $this->fail(
                    sprintf(
                        'Expected exception message "%s", but given "%s".',
                        $expectedExceptionMessage,
                        $ex->getMessage()
                    )
                );
            }
        }
    }

    public function invalidFiltersStructureProvider()
    {
        return [
            [
                'Invalid filters structure; unexpected "OR" operator.',
                [
                    'OR'
                ]
            ],
            [
                'Invalid filters structure; a filter is unexpected here.',
                [
                    [
                        'columnName' => 'column1',
                        'criterion'  => [
                            'filter' => "string",
                            'data'   => [
                                'value' => '1',
                                'type'  => 1,
                            ]
                        ],
                    ],
                    [
                        'columnName' => 'column1',
                        'criterion'  => [
                            'filter' => "string",
                            'data'   => [
                                'value' => '2',
                                'type'  => 1,
                            ]
                        ],
                    ],
                ]
            ],
            [
                'Invalid filters structure; unexpected "OR" operator.',
                [
                    'OR',
                    [
                        'columnName' => 'column1',
                        'criterion'  => [
                            'filter' => "string",
                            'data'   => [
                                'value' => '1',
                                'type'  => 1,
                            ]
                        ],
                    ],
                ]
            ],
            [
                'Invalid filters structure; unexpected end of group.',
                [
                    [
                        'columnName' => 'column1',
                        'criterion'  => [
                            'filter' => "string",
                            'data'   => [
                                'value' => '1',
                                'type'  => 1,
                            ]
                        ],
                    ],
                    'OR',
                ]
            ],
            [
                'Invalid filters structure; a group must not be empty.',
                [
                    [
                        'columnName' => 'column1',
                        'criterion'  => [
                            'filter' => "string",
                            'data'   => [
                                'value' => '1',
                                'type'  => 1,
                            ]
                        ],
                    ],
                    'OR',
                    []
                ]
            ],
        ];
    }

    /**
     * @param QueryDesignerModel                            $model
     * @param \PHPUnit_Framework_MockObject_MockObject|null $doctrine
     * @param \PHPUnit_Framework_MockObject_MockObject|null $functionProvider
     * @param \PHPUnit_Framework_MockObject_MockObject|null $virtualFieldProvider
     * @return DatagridConfigurationBuilder
     */
    protected function createDatagridConfigurationBuilder(
        QueryDesignerModel $model,
        $doctrine = null,
        $functionProvider = null,
        $virtualFieldProvider = null
    ) {
        return new DatagridConfigurationBuilder(
            'test_grid',
            $model,
            $functionProvider ? : $this->getFunctionProvider(),
            $virtualFieldProvider ? : $this->getVirtualFieldProvider(),
            $doctrine ? : $this->getDoctrine()
        );
    }
}
