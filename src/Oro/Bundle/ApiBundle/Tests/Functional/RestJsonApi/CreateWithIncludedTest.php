<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;
use Oro\Bundle\UserBundle\Entity\User;

class CreateWithIncludedTest extends RestJsonApiTestCase
{
    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->getEntityManager()
            ->getRepository(Organization::class)
            ->getFirst();
    }

    /**
     * @return BusinessUnit
     */
    protected function getBusinessUnit()
    {
        return $this->getEntityManager()
            ->getRepository(BusinessUnit::class)
            ->getFirst();
    }

    /**
     * @return array [$productId, $productTypeId]
     */
    public function testCreateIncludedEntity()
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

        return [$productId, $productTypeId];
    }

    /**
     * @depends testCreateIncludedEntity
     *
     * @param array $ids [$productId, $productTypeId]
     */
    public function testUpdateIncludedEntity($ids)
    {
        list($productId, $productTypeId) = $ids;

        $data = [
            'data'     => [
                'type'          => 'testproducts',
                'id'            => $productId,
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
        $product = $this->getEntityManager()->find(TestProduct::class, (int)$productId);
        self::assertEquals('Test Product 1 (updated)', $product->getName());
        self::assertNotNull($product->getProductType());
        self::assertEquals($productTypeId, $product->getProductType()->getName());
    }

    public function testCreateNotRelatedIncludedEntity()
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

    public function testCreateIncludedEntityWithNestedDependency()
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
                        ]
                    ]
                ],
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
                        'owner'        => [
                            'data' => ['type' => $buEntityType, 'id' => 'BU1']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $result = self::jsonToArray($response->getContent());

        self::assertEquals('test_user_2', $result['data']['attributes']['username']);
        self::assertCount(1, $result['data']['relationships']['businessUnits']['data']);
        self::assertCount(2, $result['included']);
        self::assertEquals($buEntityType, $result['included'][0]['type']);
        self::assertEquals('Business Unit 1', $result['included'][0]['attributes']['name']);
        self::assertEquals($buEntityType, $result['included'][1]['type']);
        self::assertEquals('Business Unit 2', $result['included'][1]['attributes']['name']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame('BU1', $result['included'][0]['meta']['includeId']);
        self::assertNotEmpty($result['included'][1]['meta']);
        self::assertSame('BU2', $result['included'][1]['meta']['includeId']);
    }

    public function testCreateIncludedEntityWithInversedDependency()
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
}
