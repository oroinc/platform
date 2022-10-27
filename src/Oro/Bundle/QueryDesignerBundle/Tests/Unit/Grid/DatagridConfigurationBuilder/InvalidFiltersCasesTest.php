<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\DatagridConfigurationBuilder;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidFiltersException;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;

class InvalidFiltersCasesTest extends DatagridConfigurationBuilderTestCase
{
    /**
     * @dataProvider invalidFiltersStructureProvider
     */
    public function testInvalidFiltersStructure(string $expectedExceptionMessage, array $filters)
    {
        $en = 'Acme\Entity\TestEntity';
        $definition = [
            'columns' => [
                ['name' => 'column1', 'label' => 'lbl1', 'sorting' => ''],
            ],
            'filters' => $filters,
        ];
        $doctrine = $this->getDoctrine(
            [
                $en => [
                    'column1' => 'string',
                ],
            ]
        );

        $model = new QueryDesigner($en, QueryDefinitionUtil::encodeDefinition($definition));

        try {
            $builder = $this->createDatagridConfigurationBuilder($model, $doctrine);
            $builder->getConfiguration()->toArray();
            $this->fail('Expected "Oro\Bundle\QueryDesignerBundle\Exception\InvalidFiltersException" exception.');
        } catch (InvalidFiltersException $ex) {
            if (!str_contains($ex->getMessage(), $expectedExceptionMessage)) {
                $this->fail(sprintf(
                    'Expected exception message "%s", but given "%s".',
                    $expectedExceptionMessage,
                    $ex->getMessage()
                ));
            }
        }
    }

    public function invalidFiltersStructureProvider(): array
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
                            'filter' => 'string',
                            'data'   => [
                                'value' => '1',
                                'type'  => 1,
                            ]
                        ],
                    ],
                    [
                        'columnName' => 'column1',
                        'criterion'  => [
                            'filter' => 'string',
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
                            'filter' => 'string',
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
                            'filter' => 'string',
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
                            'filter' => 'string',
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
}
