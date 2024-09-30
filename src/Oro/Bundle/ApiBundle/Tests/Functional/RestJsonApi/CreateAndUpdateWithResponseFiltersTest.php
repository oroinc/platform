<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrder;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrderLineItem;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateAndUpdateWithResponseFiltersTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/create_and_update_with_response_filters.yml'
        ]);
    }

    public function testCreateWithMetaFilters(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'meta' => 'meta=title',
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_1->name)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'meta' => [
                        'title' => 'New Product'
                    ],
                    'attributes' => [
                        'name' => 'New Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_1->name)>']
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testCreateWithFieldsFilters(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'fields' => 'fields[' . $entityType . ']=name',
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_1->name)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Product'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testCreateWithIncludeFilters(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'include' => 'include=productType',
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_1->name)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_1->name)>']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $associatedEntityType,
                        'id' => '<toString(@product_type_1->name)>',
                        'attributes' => [
                            'label' => 'Product Type 1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertCount(1, $responseContent['included']);
    }

    public function testCreateWithFieldsAndIncludeFiltersWhenIncludedRelationshipDoesNotAddedToFieldsFilter(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'fields' => 'fields[' . $entityType . ']=name',
                'include' => 'include=productType',
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_1->name)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Product'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testCreateWithIncludeFiltersForAssociationsThatDoesNotExistInRequestData(): void
    {
        $orderEntityType = $this->getEntityType(TestOrder::class);
        $lineItemEntityType = $this->getEntityType(TestOrderLineItem::class);
        $productEntityType = $this->getEntityType(TestProduct::class);
        $productTypeEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->post(
            ['entity' => $orderEntityType],
            [
                'include' => 'include=items,items.product.productType',
                'data' => [
                    'type' => $orderEntityType,
                    'attributes' => [
                        'poNumber' => 'NEW_ORDER'
                    ],
                    'relationships' => [
                        'items' => [
                            'data' => [['type' => $lineItemEntityType, 'id' => 'line_item_1']]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $lineItemEntityType,
                        'id' => 'line_item_1',
                        'attributes' => [
                            'quantity' => 1
                        ],
                        'relationships' => [
                            'product' => [
                                'data' => ['type' => $productEntityType, 'id' => '<toString(@product_1->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->dumpYmlTemplate(null, $response);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    'type' => $orderEntityType,
                    'attributes' => [
                        'poNumber' => 'NEW_ORDER'
                    ],
                    'relationships' => [
                        'items' => [
                            'data' => [['type' => $lineItemEntityType, 'id' => 'new']]
                        ],
                        'targetEntity' => [
                            'data' => null
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $productTypeEntityType,
                        'id' => '<toString(@product_type_1->name)>',
                        'attributes' => [
                            'label' => 'Product Type 1'
                        ]
                    ],
                    [
                        'type' => $productEntityType,
                        'id' => '<toString(@product_1->id)>',
                        'attributes' => [
                            'name' => 'Product 1'
                        ],
                        'relationships' => [
                            'productType' => [
                                'data' => [
                                    'type' => $productTypeEntityType,
                                    'id' => '<toString(@product_type_1->name)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $lineItemEntityType,
                        'id' => 'new',
                        'attributes' => [
                            'quantity' => 1
                        ],
                        'relationships' => [
                            'order' => [
                                'data' => ['type' => $orderEntityType, 'id' => 'new']
                            ],
                            'product' => [
                                'data' => ['type' => $productEntityType, 'id' => '<toString(@product_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateWithMetaFilters(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@product_1->id)>'],
            [
                'meta' => 'meta=title',
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'attributes' => [
                        'name' => 'Updated Product'
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'meta' => [
                        'title' => 'Updated Product'
                    ],
                    'attributes' => [
                        'name' => 'Updated Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_1->name)>']
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testUpdateWithFieldsFilters(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@product_1->id)>'],
            [
                'fields' => 'fields[' . $entityType . ']=name',
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'attributes' => [
                        'name' => 'Updated Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_2->name)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'attributes' => [
                        'name' => 'Updated Product'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testUpdateWithIncludeFilters(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@product_1->id)>'],
            [
                'include' => 'include=productType',
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'attributes' => [
                        'name' => 'Updated Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_2->name)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'attributes' => [
                        'name' => 'Updated Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_2->name)>']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $associatedEntityType,
                        'id' => '<toString(@product_type_2->name)>',
                        'attributes' => [
                            'label' => 'Product Type 2'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertCount(1, $responseContent['included']);
    }

    public function testUpdateWithFieldsAndIncludeFiltersWhenIncludedRelationshipDoesNotAddedToFieldsFilter(): void
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@product_1->id)>'],
            [
                'fields' => 'fields[' . $entityType . ']=name',
                'include' => 'include=productType',
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'attributes' => [
                        'name' => 'Updated Product'
                    ],
                    'relationships' => [
                        'productType' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@product_type_2->name)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@product_1->id)>',
                    'attributes' => [
                        'name' => 'Updated Product'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testCreateWithDisabledFieldsFilters(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'fields' => 'fields[' . $entityType . ']=title',
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'title' => 'New Department'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $associatedEntityType, 'id' => '<toString(@employee_1->id)>']]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'fields[' . $entityType . ']']
            ],
            $response
        );
    }

    public function testCreateWithDisabledIncludeFilters(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'include' => 'include=staff',
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'title' => 'New Department'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $associatedEntityType, 'id' => '<toString(@employee_1->id)>']]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'include']
            ],
            $response
        );
    }

    public function testUpdateWithFieldsFiltersDisabledForCreateAction(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@department_1->id)>'],
            [
                'fields' => 'fields[' . $entityType . ']=title',
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@department_1->id)>',
                    'attributes' => [
                        'title' => 'Updated Product'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $associatedEntityType, 'id' => '<toString(@employee_2->id)>']]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@department_1->id)>',
                    'attributes' => [
                        'title' => 'Updated Product'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testUpdateWithIncludeFiltersDisabledForCreateAction(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@department_1->id)>'],
            [
                'include' => 'include=staff',
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@department_1->id)>',
                    'attributes' => [
                        'title' => 'Updated Product'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $associatedEntityType, 'id' => '<toString(@employee_2->id)>']]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@department_1->id)>',
                    'attributes' => [
                        'title' => 'Updated Product'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $associatedEntityType, 'id' => '<toString(@employee_2->id)>']]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $associatedEntityType,
                        'id' => '<toString(@employee_2->id)>',
                        'attributes' => [
                            'name' => 'Employee 2'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertCount(1, $responseContent['included']);
    }
}
