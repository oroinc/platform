<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;

class GetWithRenamedFieldsTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/renamed_fields.yml']);

        $this->appendEntityConfig(
            TestProduct::class,
            [
                'fields'  => [
                    'renamedId'   => ['property_path' => 'id'],
                    'renamedName' => ['property_path' => 'name']
                ],
                'filters' => [
                    'fields' => [
                        'renamedName' => null
                    ]
                ],
                'sorters' => [
                    'fields' => [
                        'renamedName' => null
                    ]
                ]
            ]
        );
        $this->appendEntityConfig(
            TestProductType::class,
            [
                'fields'  => [
                    'renamedName' => ['property_path' => 'name']
                ],
                'filters' => [
                    'fields' => [
                        'renamedName' => null
                    ]
                ],
                'sorters' => [
                    'fields' => [
                        'renamedName' => null
                    ]
                ]
            ]
        );
    }

    public function testFilteringByRenamedIdentityField()
    {
        $response = $this->cget(
            ['entity' => 'testproducts'],
            ['filter[id]' => '@test_product2->id']
        );

        $this->assertResponseContains('renamed_fields_filter.yml', $response);
    }

    public function testFilteringByRenamedField()
    {
        $response = $this->cget(
            ['entity' => 'testproducts'],
            ['filter[renamedName]' => 'product 2']
        );

        $this->assertResponseContains('renamed_fields_filter.yml', $response);
    }

    public function testFilteringByRenamedRelatedField()
    {
        $response = $this->cget(
            ['entity' => 'testproducts'],
            ['filter[productType.renamedName]' => 'type2']
        );

        $this->assertResponseContains('renamed_fields_filter.yml', $response);
    }

    /**
     * @param array $params
     * @param array $expected
     *
     * @dataProvider getSortingByRenamedFieldData
     */
    public function testSortingByRenamedField($params, $expected)
    {
        $response = $this->cget(
            ['entity' => 'testproducts'],
            $params
        );

        $this->assertResponseContains($expected, $response);
    }

    /**
     * @return array
     */
    public function getSortingByRenamedFieldData()
    {
        return [
            'use default sorting'                   => [
                'params'   => [],
                'expected' => 'renamed_fields_sort_asc.yml'
            ],
            'sort by renamed identity field (ASC)'  => [
                'params'   => [
                    'sort' => 'id'
                ],
                'expected' => 'renamed_fields_sort_asc.yml'
            ],
            'sort by renamed identity field (DESC)' => [
                'params'   => [
                    'sort' => '-id'
                ],
                'expected' => 'renamed_fields_sort_desc.yml'
            ],
            'sort by renamed field (ASC)'           => [
                'params'   => [
                    'sort' => 'renamedName'
                ],
                'expected' => 'renamed_fields_sort_asc.yml'
            ],
            'sort by renamed field (DESC)'          => [
                'params'   => [
                    'sort' => '-renamedName'
                ],
                'expected' => 'renamed_fields_sort_desc.yml'
            ],
            'sort by renamed related field (ASC)'   => [
                'params'   => [
                    'sort' => 'productType.renamedName'
                ],
                'expected' => 'renamed_fields_sort_asc.yml'
            ],
            'sort by renamed related field (DESC)'  => [
                'params'   => [
                    'sort' => '-productType.renamedName'
                ],
                'expected' => 'renamed_fields_sort_desc.yml'
            ],
        ];
    }
}
