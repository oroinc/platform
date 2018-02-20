<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;

class JoinCasesTest extends DatagridConfigurationBuilderTestCase
{
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
                'hints' => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker',
                    ]
                ]
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
            ],
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.column1'],
                    'c2' => ['data_name' => 't2.column2']
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
                ['name' => $enR1 . '::rc3+' . $en1 . '::column2', 'label' => 'lbl4', 'sorting' => ''],
                [
                    'name'    => 'rc1+' . $en1 . '::' . $enR1 . '::rc1+' . $en2 . '::column1',
                    'label'   => 'lbl5',
                    'sorting' => ''
                ],
            ],
            'filters' => []
        ];
        $doctrine   = $this->getDoctrine(
            [
                $en   => [
                    'column1' => 'string',
                    'rc1'     => [
                        'nullable' => true,
                        'type' => ClassMetadataInfo::MANY_TO_ONE
                    ],
                ],
                $en1  => [
                    'column2' => 'string',
                ],
                $en2  => [
                    'column1' => 'string',
                ],
                $enR1 => [
                    'rc1' => [
                        'nullable' => true,
                        'type' => ClassMetadataInfo::MANY_TO_ONE
                    ],
                    'rc2' => [
                        'nullable' => false,
                        'type' => ClassMetadataInfo::ONE_TO_MANY
                    ],
                    'rc3' => [
                        'nullable' => false,
                        'type' => ClassMetadataInfo::MANY_TO_MANY
                    ]
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
                        't4.column2 as c4',
                        't6.column1 as c5',
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
                                'alias' => 't5',
                            ],
                            [
                                'join'          => $enR1,
                                'alias'         => 't6',
                                'conditionType' => 'WITH',
                                'condition'     => 't6.rc1 = t5'
                            ],
                        ],
                        'inner' => [
                            [
                                'join'          => $enR1,
                                'alias'         => 't3',
                                'conditionType' => 'WITH',
                                'condition'     => 't1 MEMBER OF t3.rc2'
                            ],
                            [
                                'join'          => $enR1,
                                'alias'         => 't4',
                                'conditionType' => 'WITH',
                                'condition'     => 't1 MEMBER OF t4.rc3'
                            ]
                        ],
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''                                             => 't1',
                        $en . '::' . $enR1 . '::rc1'                   => 't2',
                        $en . '::' . $enR1 . '::rc2'                   => 't3',
                        $en . '::' . $enR1 . '::rc3'                   => 't4',
                        $en . '::rc1'                                  => 't5',
                        $en . '::rc1+' . $en1 . '::' . $enR1 . '::rc1' => 't6',
                    ],
                    'column_aliases' => [
                        'column1'                                                    => 'c1',
                        $enR1 . '::rc1+' . $en1 . '::column2'                        => 'c2',
                        $enR1 . '::rc2+' . $en1 . '::column2'                        => 'c3',
                        $enR1 . '::rc3+'  .$en1 . '::column2'                        => 'c4',
                        'rc1+' . $en1 . '::' . $enR1 . '::rc1+' . $en2 . '::column1' => 'c5',
                    ],
                ],
                'hints' => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker',
                    ]
                ]
            ],
            'columns' => [
                'c1' => ['label' => 'lbl1', 'frontend_type' => 'string', 'translatable' => false],
                'c2' => ['label' => 'lbl2', 'frontend_type' => 'string', 'translatable' => false],
                'c3' => ['label' => 'lbl3', 'frontend_type' => 'string', 'translatable' => false],
                'c4' => ['label' => 'lbl4', 'frontend_type' => 'string', 'translatable' => false],
                'c5' => ['label' => 'lbl5', 'frontend_type' => 'string', 'translatable' => false]
            ],
            'name'    => 'test_grid',
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                    'c3' => ['data_name' => 'c3'],
                    'c4' => ['data_name' => 'c4'],
                    'c5' => ['data_name' => 'c5'],
                ]
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1', 'type' => 'string', 'translatable' => false],
                    'c2' => ['data_name' => 'c2', 'type' => 'string', 'translatable' => false],
                    'c3' => ['data_name' => 'c3', 'type' => 'string', 'translatable' => false],
                    'c4' => ['data_name' => 'c4', 'type' => 'string', 'translatable' => false],
                    'c5' => ['data_name' => 'c5', 'type' => 'string', 'translatable' => false],
                ]
            ],
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.column1'],
                    'c2' => ['data_name' => 't2.column2'],
                    'c3' => ['data_name' => 't3.column2'],
                    'c4' => ['data_name' => 't4.column2'],
                    'c5' => ['data_name' => 't6.column1'],
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
                'hints' => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Oro\Bundle\QueryDesignerBundle\QueryDesigner\SqlWalker',
                    ]
                ]
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
            ],
            'fields_acl' => ['columns' => ['c1' => ['data_name' => 't1.column1']]]
        ];

        $this->assertEquals($expected, $result);
    }
}
