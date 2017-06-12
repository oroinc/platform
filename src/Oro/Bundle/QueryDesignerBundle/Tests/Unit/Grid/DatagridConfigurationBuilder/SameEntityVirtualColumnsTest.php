<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Doctrine\ORM\Query;

use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;

class SameEntityVirtualColumnsTest extends DatagridConfigurationBuilderTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testVirtualColumns()
    {
        $en                    = 'Acme\Entity\TestEntity';
        $definition            = [
            'columns' => [
                ['name' => 'vc1', 'label' => 'lbl1'],
                ['name' => 'vc2', 'label' => 'lbl2'],
            ],
            'filters' => []
        ];
        $doctrine              = $this->getDoctrine();
        $virtualColumnProvider = $this->getVirtualFieldProvider(
            [
                [
                    $en,
                    'vc1',
                    [
                        'select' => [
                            'expr'        => 'country.name',
                            'return_type' => 'string'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => 'entity.country',
                                    'alias' => 'country'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    $en,
                    'vc2',
                    [
                        'select' => [
                            'expr'        => 'country.iso2Code',
                            'return_type' => 'string'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => 'entity.country',
                                    'alias' => 'country'
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
            'name'    => 'test_grid',
            'columns' => [
                'c1' => ['frontend_type' => 'string', 'label' => 'lbl1', 'translatable' => false],
                'c2' => ['frontend_type' => 'string', 'label' => 'lbl2', 'translatable' => false],
            ],
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                ],
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['type' => 'string', 'data_name' => 'c1', 'translatable' => false],
                    'c2' => ['type' => 'string', 'data_name' => 'c2', 'translatable' => false],
                ]
            ],
            'source'  => [
                'query'        => [
                    'select' => [
                        't2.name as c1',
                        't2.iso2Code as c2',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'  => 't1.country',
                                'alias' => 't2'
                            ]
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''                => 't1',
                        't1.country|left' => 't2',
                    ],
                    'column_aliases' => [
                        'vc1' => 'c1',
                        'vc2' => 'c2',
                    ],
                ],
                'type'         => 'orm',
                'hints'        => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Gedmo\Translatable\Query\TreeWalker\TranslationWalker',
                    ]
                ]
            ]

        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testVirtualColumnsWithJoinsToDifferentTablesButTheSameTableAlias()
    {
        $en                    = 'Acme\Entity\TestEntity';
        $definition            = [
            'columns' => [
                ['name' => 'vc1', 'label' => 'lbl1'],
                ['name' => 'vc2', 'label' => 'lbl2'],
            ],
            'filters' => []
        ];
        $doctrine              = $this->getDoctrine();
        $virtualColumnProvider = $this->getVirtualFieldProvider(
            [
                [
                    $en,
                    'vc1',
                    [
                        'select' => [
                            'expr'        => 'target.name',
                            'return_type' => 'string'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'          => 'entity.rel1',
                                    'alias'         => 'target',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'target.primary = true'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    $en,
                    'vc2',
                    [
                        'select' => [
                            'expr'        => 'target.name',
                            'return_type' => 'string'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'          => 'entity.rel2',
                                    'alias'         => 'target',
                                    'conditionType' => 'WITH',
                                    'condition'     => 'target.primary = true'
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
            'name'    => 'test_grid',
            'columns' => [
                'c1' => ['frontend_type' => 'string', 'label' => 'lbl1', 'translatable' => false],
                'c2' => ['frontend_type' => 'string', 'label' => 'lbl2', 'translatable' => false],
            ],
            'sorters' => [
                'columns' => [
                    'c1' => ['data_name' => 'c1'],
                    'c2' => ['data_name' => 'c2'],
                ],
            ],
            'filters' => [
                'columns' => [
                    'c1' => ['type' => 'string', 'data_name' => 'c1', 'translatable' => false],
                    'c2' => ['type' => 'string', 'data_name' => 'c2', 'translatable' => false],
                ]
            ],
            'source'  => [
                'query'        => [
                    'select' => [
                        't2.name as c1',
                        't3.name as c2',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'          => 't1.rel1',
                                'alias'         => 't2',
                                'conditionType' => 'WITH',
                                'condition'     => 't2.primary = true'
                            ],
                            [
                                'join'          => 't1.rel2',
                                'alias'         => 't3',
                                'conditionType' => 'WITH',
                                'condition'     => 't3.primary = true'
                            ]
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        ''                                    => 't1',
                        't1.rel1|left|WITH|t2.primary = true' => 't2',
                        't1.rel2|left|WITH|t3.primary = true' => 't3',
                    ],
                    'column_aliases' => [
                        'vc1' => 'c1',
                        'vc2' => 'c2',
                    ],
                ],
                'type'         => 'orm',
                'hints'        => [
                    [
                        'name'  => Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'value' => 'Gedmo\Translatable\Query\TreeWalker\TranslationWalker',
                    ]
                ]
            ]

        ];

        $this->assertEquals($expected, $result);
    }
}
