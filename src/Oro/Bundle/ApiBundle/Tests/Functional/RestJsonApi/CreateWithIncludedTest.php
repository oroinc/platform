<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrder;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrderLineItem;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestUniqueKeyIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class CreateWithIncludedTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/create_with_included.yml'
        ]);
    }

    private function getOrganization(): Organization
    {
        return $this->getReference('organization');
    }

    private function getBusinessUnit(): BusinessUnit
    {
        return $this->getReference('business_unit');
    }

    public function testCreateIncludedEntity(): void
    {
        $data = [
            'data'     => [
                'type'          => 'testproducts',
                'attributes'    => [
                    'name' => 'Test Product 1'
                ],
                'relationships' => [
                    'productType' => [
                        'data' => ['type' => 'testproducttypes', 'id' => 'TEST_PRODUCT_TYPE_1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'testproducttypes',
                    'id'         => 'TEST_PRODUCT_TYPE_1',
                    'attributes' => [
                        'label' => 'Test Product Type 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'testproducts'], $data);

        $result = self::jsonToArray($response->getContent());

        $productId = $result['data']['id'];
        self::assertEquals('Test Product 1', $result['data']['attributes']['name']);
        self::assertNotEmpty($result['data']['relationships']['productType']['data']);
        self::assertCount(1, $result['included']);
        $productTypeId = $result['data']['relationships']['productType']['data']['id'];
        self::assertEquals('testproducttypes', $result['included'][0]['type']);
        self::assertEquals($productTypeId, $result['included'][0]['id']);
        self::assertEquals('Test Product Type 1', $result['included'][0]['attributes']['label']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame('TEST_PRODUCT_TYPE_1', $result['included'][0]['meta']['includeId']);

        // test that both the product and the product type was created in the database
        $this->getEntityManager()->clear();
        $productType = $this->getEntityManager()->find(TestProductType::class, $productTypeId);
        self::assertNotNull($productType);
        self::assertEquals('Test Product Type 1', $productType->getLabel());
        $product = $this->getEntityManager()->find(TestProduct::class, (int)$productId);
        self::assertEquals('Test Product 1', $product->getName());
        self::assertNotNull($product->getProductType());
        self::assertEquals($productTypeId, $product->getProductType()->getName());
    }

    public function testUpdateIncludedEntity(): void
    {
        $productType = new TestProductType();
        $productType->setName('TEST_PRODUCT_TYPE_1');
        $productType->setLabel('Test Product Type 1');
        $product = new TestProduct();
        $product->setName('Test Product 1');
        $product->setProductType($productType);
        $this->getEntityManager()->persist($productType);
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();

        $productId = $product->getId();
        $productTypeId = $productType->getName();

        $data = [
            'data'     => [
                'type'          => 'testproducts',
                'id'            => (string)$productId,
                'attributes'    => [
                    'name' => 'Test Product 1 (updated)'
                ],
                'relationships' => [
                    'productType' => [
                        'data' => ['type' => 'testproducttypes', 'id' => $productTypeId]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'testproducttypes',
                    'id'         => $productTypeId,
                    'meta'       => [
                        'update' => true
                    ],
                    'attributes' => [
                        'label' => 'Test Product Type 1 (updated)'
                    ]
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'testproducts', 'id' => $productId], $data);

        $result = self::jsonToArray($response->getContent());

        self::assertEquals('Test Product 1 (updated)', $result['data']['attributes']['name']);
        self::assertNotEmpty($result['data']['relationships']['productType']['data']);
        self::assertCount(1, $result['included']);
        $productTypeId = $result['data']['relationships']['productType']['data']['id'];
        self::assertEquals('testproducttypes', $result['included'][0]['type']);
        self::assertEquals($productTypeId, $result['included'][0]['id']);
        self::assertEquals('Test Product Type 1 (updated)', $result['included'][0]['attributes']['label']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame($productTypeId, $result['included'][0]['meta']['includeId']);

        // test that both the product and the product type was updated in the database
        $this->getEntityManager()->clear();
        $productType = $this->getEntityManager()->find(TestProductType::class, $productTypeId);
        self::assertNotNull($productType);
        self::assertEquals('Test Product Type 1 (updated)', $productType->getLabel());
        $product = $this->getEntityManager()->find(TestProduct::class, $productId);
        self::assertEquals('Test Product 1 (updated)', $product->getName());
        self::assertNotNull($product->getProductType());
        self::assertEquals($productTypeId, $product->getProductType()->getName());
    }

    public function testCreateNotRelatedIncludedEntity(): void
    {
        $data = [
            'data'     => [
                'type' => 'testproducts'
            ],
            'included' => [
                [
                    'type' => 'testproducttypes',
                    'id'   => 'TEST_PRODUCT_TYPE_1'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'testproducts'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The entity should have a relationship with the primary entity'
                    . ' and this should be explicitly specified in the request',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testCreateIncludedEntityWithNestedDependency(): void
    {
        $org = $this->getOrganization();
        $bu = $this->getBusinessUnit();

        $entityType = $this->getEntityType(User::class);
        $orgEntityType = $this->getEntityType(Organization::class);
        $buEntityType = $this->getEntityType(BusinessUnit::class);

        $data = [
            'data'     => [
                'type'          => $entityType,
                'attributes'    => [
                    'username'  => 'test_user_2',
                    'firstName' => 'Test First Name',
                    'lastName'  => 'Test Last Name',
                    'email'     => 'test_user_2@example.com',
                ],
                'relationships' => [
                    'organization'  => [
                        'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                    ],
                    'owner'         => [
                        'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                    ],
                    'businessUnits' => [
                        'data' => [
                            ['type' => $buEntityType, 'id' => 'BU2']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => $buEntityType,
                    'id'            => 'BU2',
                    'attributes'    => [
                        'name' => 'Business Unit 2'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                        ],
                        'users'        => [
                            'data' => [['type' => $entityType, 'id' => 'nested_user']]
                        ]
                    ]
                ],
                [
                    'type'          => $entityType,
                    'id'            => 'nested_user',
                    'attributes'    => [
                        'username'  => 'test_user_21',
                        'firstName' => 'Test Second Name',
                        'lastName'  => 'Test Last Name',
                        'email'     => 'test_user_21@example.com',
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                        ],
                        'owner'        => [
                            'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $responseContent = $this->updateResponseContent('create_included_entity_with_nested_dpendency.yml', $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateIncludedEntityWithInversedDependency(): void
    {
        $org = $this->getOrganization();
        $bu = $this->getBusinessUnit();

        $entityType = $this->getEntityType(User::class);
        $orgEntityType = $this->getEntityType(Organization::class);
        $buEntityType = $this->getEntityType(BusinessUnit::class);

        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => 'PRIMARY_USER_OBJECT',
                'attributes'    => [
                    'username'  => 'test_user_3',
                    'firstName' => 'Test First Name',
                    'lastName'  => 'Test Last Name',
                    'email'     => 'test_user_3@example.com',
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                    ],
                    'owner'        => [
                        'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => $buEntityType,
                    'id'            => 'BU1',
                    'attributes'    => [
                        'name' => 'Business Unit 1'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                        ],
                        'owner'        => [
                            'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                        ],
                        'users'        => [
                            'data' => [
                                ['type' => $entityType, 'id' => 'PRIMARY_USER_OBJECT']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $result = self::jsonToArray($response->getContent());

        $userId = $result['data']['id'];
        self::assertEquals('test_user_3', $result['data']['attributes']['username']);
        self::assertCount(1, $result['included']);
        self::assertEquals($buEntityType, $result['included'][0]['type']);
        self::assertEquals('Business Unit 1', $result['included'][0]['attributes']['name']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame('BU1', $result['included'][0]['meta']['includeId']);
        self::assertCount(1, $result['included'][0]['relationships']['users']['data']);
        self::assertSame($entityType, $result['included'][0]['relationships']['users']['data'][0]['type']);
        self::assertSame($userId, $result['included'][0]['relationships']['users']['data'][0]['id']);
    }

    public function testTryToCreateIncludedEntityWhenCreateActionForItIsDisabled(): void
    {
        $this->appendEntityConfig(
            TestProductType::class,
            [
                'actions' => [
                    'create' => false
                ]
            ],
            true
        );

        $data = [
            'data'     => [
                'type'          => 'testproducts',
                'attributes'    => [
                    'name' => 'Test Product 2'
                ],
                'relationships' => [
                    'productType' => [
                        'data' => ['type' => 'testproducttypes', 'id' => 'TEST_PRODUCT_TYPE_2']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'testproducttypes',
                    'id'         => 'TEST_PRODUCT_TYPE_2',
                    'attributes' => [
                        'label' => 'Test Product Type 2'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => 'testproducts'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'action not allowed exception',
                'detail' => 'The action is not allowed.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testUpdateIncludedEntityWithInversedDependency(): void
    {
        $orderId = $this->getReference('order1')->getId();
        $orderLineItem1Id = $this->getReference('order1_line_item1')->getId();
        $orderLineItem2Id = $this->getReference('order1_line_item2')->getId();
        $orderLineItem3Id = $this->getReference('order1_line_item3')->getId();

        // guard
        self::assertSame(
            3,
            $this->getEntityManager()->find(TestOrder::class, $orderId)->getLineItems()->count()
        );

        $orderEntityType = $this->getEntityType(TestOrder::class);
        $orderLineItemEntityType = $this->getEntityType(TestOrderLineItem::class);

        $data = [
            'data'     => [
                'type' => $orderEntityType,
                'id'   => (string)$orderId
            ],
            'included' => [
                [
                    'type'          => $orderLineItemEntityType,
                    'id'            => (string)$orderLineItem1Id,
                    'meta'          => ['update' => true],
                    'attributes'    => [
                        'quantity' => 110
                    ],
                    'relationships' => [
                        'order' => [
                            'data' => ['type' => $orderEntityType, 'id' => (string)$orderId]
                        ]
                    ]
                ],
                [
                    'type'          => $orderLineItemEntityType,
                    'id'            => (string)$orderLineItem3Id,
                    'meta'          => ['update' => true],
                    'attributes'    => [
                        'quantity' => 120
                    ],
                    'relationships' => [
                        'order' => [
                            'data' => ['type' => $orderEntityType, 'id' => (string)$orderId]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(['entity' => $orderEntityType, 'id' => (string)$orderId], $data);
        $result = self::jsonToArray($response->getContent());
        self::assertCount(3, $result['data']['relationships']['items']['data']);

        self::assertSame(
            3,
            $this->getEntityManager()->find(TestOrder::class, $orderId)->getLineItems()->count()
        );
        self::assertSame(
            110.0,
            $this->getEntityManager()->find(TestOrderLineItem::class, $orderLineItem1Id)->getQuantity()
        );
        self::assertSame(
            10.0,
            $this->getEntityManager()->find(TestOrderLineItem::class, $orderLineItem2Id)->getQuantity()
        );
        self::assertSame(
            120.0,
            $this->getEntityManager()->find(TestOrderLineItem::class, $orderLineItem3Id)->getQuantity()
        );
    }

    public function testUpdateWithIntersectedRelationships(): void
    {
        $data = $this->getUpdateWithIntersectedRelationshipsRequestData();

        $response = $this->patch(
            ['entity' => $this->getEntityType(TestUniqueKeyIdentifier::class), 'id' => '<toString(@item1->id)>'],
            $data
        );

        $expectedData = $data;
        foreach ($expectedData['included'] as &$item) {
            unset($item['meta']);
        }
        unset($item);
        $this->assertResponseContains($expectedData, $response);

        $em = $this->getEntityManager(TestUniqueKeyIdentifier::class);
        self::assertEquals(
            'Updated Item 1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item1')->id)->name
        );
        self::assertEquals(
            'Updated Item 1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item11')->id)->name
        );
        self::assertEquals(
            'Updated Item 1.1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item111')->id)->name
        );
        self::assertEquals(
            'Updated Item 1.1.1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item1111')->id)->name
        );
        self::assertEquals(
            'Updated Item 1.2',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item12')->id)->name
        );
    }

    public function testTryToUpdateWithIntersectedRelationshipsAndPrimaryEntityHasError(): void
    {
        $data = $this->getUpdateWithIntersectedRelationshipsRequestData();
        $data['data']['attributes']['name'] = null;

        $response = $this->patch(
            ['entity' => $this->getEntityType(TestUniqueKeyIdentifier::class), 'id' => '<toString(@item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );

        $em = $this->getEntityManager(TestUniqueKeyIdentifier::class);
        self::assertEquals(
            'Item 1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item1')->id)->name
        );
        self::assertEquals(
            'Item 1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item11')->id)->name
        );
        self::assertEquals(
            'Item 1.1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item111')->id)->name
        );
        self::assertEquals(
            'Item 1.1.1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item1111')->id)->name
        );
        self::assertEquals(
            'Item 1.2',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item12')->id)->name
        );
    }

    public function testTryToUpdateWithIntersectedRelationshipsAndIncludedEntityHasError(): void
    {
        $data = $this->getUpdateWithIntersectedRelationshipsRequestData();
        $data['included'][0]['attributes']['name'] = null;

        $response = $this->patch(
            ['entity' => $this->getEntityType(TestUniqueKeyIdentifier::class), 'id' => '<toString(@item1->id)>'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/0/attributes/name']
            ],
            $response
        );

        $em = $this->getEntityManager(TestUniqueKeyIdentifier::class);
        self::assertEquals(
            'Item 1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item1')->id)->name
        );
        self::assertEquals(
            'Item 1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item11')->id)->name
        );
        self::assertEquals(
            'Item 1.1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item111')->id)->name
        );
        self::assertEquals(
            'Item 1.1.1.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item1111')->id)->name
        );
        self::assertEquals(
            'Item 1.2',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item12')->id)->name
        );
    }

    private function getUpdateWithIntersectedRelationshipsRequestData(): array
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $entity1Id = $this->getReference('item1')->id;
        $entity11Id = $this->getReference('item11')->id;
        $entity111Id = $this->getReference('item111')->id;
        $entity1111Id = $this->getReference('item1111')->id;
        $entity12Id = $this->getReference('item12')->id;

        return [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entity1Id,
                'attributes' => ['name' => 'Updated Item 1'],
                'relationships' => [
                    'children' => ['data' => [['type' => $entityType, 'id' => (string)$entity11Id]]]
                ]
            ],
            'included' => [
                [
                    'type' => $entityType,
                    'id' => (string)$entity11Id,
                    'meta' => ['update' => true],
                    'attributes' => ['name' => 'Updated Item 1.1'],
                    'relationships' => [
                        'parent' => ['data' => ['type' => $entityType, 'id' => (string)$entity1Id]],
                        'children' => ['data' => [['type' => $entityType, 'id' => (string)$entity111Id]]]
                    ]
                ],
                [
                    'type' => $entityType,
                    'id' => (string)$entity111Id,
                    'meta' => ['update' => true],
                    'attributes' => ['name' => 'Updated Item 1.1.1'],
                    'relationships' => [
                        'parent' => ['data' => ['type' => $entityType, 'id' => (string)$entity11Id]],
                        'children' => ['data' => [['type' => $entityType, 'id' => (string)$entity1111Id]]]
                    ]
                ],
                [
                    'type' => $entityType,
                    'id' => (string)$entity1111Id,
                    'meta' => ['update' => true],
                    'attributes' => ['name' => 'Updated Item 1.1.1.1'],
                    'relationships' => [
                        'parent' => ['data' => ['type' => $entityType, 'id' => (string)$entity111Id]]
                    ]
                ],
                [
                    'type' => $entityType,
                    'id' => (string)$entity12Id,
                    'meta' => ['update' => true],
                    'attributes' => ['name' => 'Updated Item 1.2'],
                    'relationships' => [
                        'parent' => ['data' => ['type' => $entityType, 'id' => (string)$entity1Id]],
                        'relations' => ['data' => [['type' => $entityType, 'id' => (string)$entity111Id]]]
                    ]
                ]
            ]
        ];
    }

    public function testTryToUpdateWithDuplicatedIncludeEntities(): void
    {
        $owner2Id = $this->getReference('owner2')->id;
        $target1Id = $this->getReference('target1')->id;

        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => (string)$owner2Id,
                'attributes' => [
                    'name' => 'Test Owner 2 (updated)'
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapitargets', 'id' => (string)$target1Id]
                    ],
                    'targets' => [
                        'data' => [
                            ['type' => 'testapitargets', 'id' => (string)$target1Id]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'testapitargets',
                    'id' => (string)$target1Id,
                    'meta' => [
                        'update' => true
                    ],
                    'attributes' => [
                        'name' => 'Test Target 1 (updated, 1)'
                    ]
                ],
                [
                    'type' => 'testapitargets',
                    'id' => (string)$target1Id,
                    'meta' => [
                        'update' => true
                    ],
                    'attributes' => [
                        'name' => 'Test Target 1 (updated, 2)'
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => (string)$owner2Id],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'request data constraint',
                'detail' => 'The item duplicates the item with the index 0',
                'source' => ['pointer' => '/included/1']
            ],
            $response
        );
    }
}
