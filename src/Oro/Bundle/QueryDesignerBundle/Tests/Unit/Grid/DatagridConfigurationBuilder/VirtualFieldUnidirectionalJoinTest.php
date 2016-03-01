<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;

use Doctrine\ORM\Query;

class VirtualFieldUnidirectionalJoinTest extends DatagridConfigurationBuilderTestCase
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
        $doctrine              = $this->getDoctrine(
            [
                $en  => [
                    'column1' => 'string',
                    'rc1'     => ['nullable' => true],
                ]
            ]
        );
        $virtualColumnProvider = $this->getVirtualFieldProvider(
            [
                [
                    $en,
                    'vc1',
                    [
                        'select' => [
                            'expr'        => 'h.amount',
                            'return_type' => 'money'
                        ],
                        'join'   => [
                            'left' => [
                                [
                                    'join'  => 'OroCRM\Bundle\ChannelBundle\Entity\LifetimeValueHistory',
                                    'alias' => 'h'
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
                'c1' => ['frontend_type' => 'currency', 'label' => 'lbl1', 'translatable' => false],
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
                    'c1' => ['type' => 'number', 'data_name' => 'c1', 'translatable' => false],
                    'c2' => ['type' => 'string', 'data_name' => 'c2', 'translatable' => false],
                ]
            ],
            'source'  => [
                'query'        => [
                    'select' => [
                        't2.amount as c1',
                        't3.iso2Code as c2',
                    ],
                    'from'   => [
                        ['table' => $en, 'alias' => 't1']
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'  => 'OroCRM\Bundle\ChannelBundle\Entity\LifetimeValueHistory',
                                'alias' => 't2'
                            ],
                            [
                                'join'  => 't1.country',
                                'alias' => 't3'
                            ]
                        ]
                    ]
                ],
                'query_config' => [
                    'table_aliases'  => [
                        '' => 't1',
                        'OroCRM\Bundle\ChannelBundle\Entity\LifetimeValueHistory|left' => 't2',
                        't1.country|left' => 't3'
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
