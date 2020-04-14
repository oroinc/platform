<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;

class EmptyNoFiltersNoJoinsCasesTest extends DatagridConfigurationBuilderTestCase
{
    public function testEmpty()
    {
        $this->expectException(\Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "columns" definition does not exist.');

        $model = new QueryDesignerModel();
        $model->setDefinition(json_encode([]));
        $builder = $this->createDatagridConfigurationBuilder($model);
        $builder->getConfiguration();
    }

    public function testEmptyColumns()
    {
        $this->expectException(\Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "columns" definition must not be empty.');

        $model = new QueryDesignerModel();
        $model->setDefinition(json_encode(['columns' => []]));
        $builder = $this->createDatagridConfigurationBuilder($model);
        $builder->getConfiguration();
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
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.column1']
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
            'fields_acl' => [
                'columns' => [
                    'c1' => ['data_name' => 't1.column1']
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }
}
