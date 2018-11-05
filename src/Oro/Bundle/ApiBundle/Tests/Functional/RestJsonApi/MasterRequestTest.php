<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;

/**
 * Tests that "master request" flag is TRUE for master requests and FALSE for sub requests.
 * @dbIsolationPerTest
 */
class MasterRequestTest extends RestJsonApiTestCase
{
    /**
     * @return int
     */
    private function createProduct()
    {
        $product = new TestProduct();
        $product->setName('New Product');
        $em = $this->getEntityManager();
        $em->persist($product);
        $em->flush();
        $productId = $product->getId();
        $em->clear();

        return $productId;
    }

    /**
     * @return string
     */
    private function createProductType()
    {
        $productType = new TestProductType();
        $productType->setName('new_product_type');
        $em = $this->getEntityManager();
        $em->persist($productType);
        $em->flush();
        $productTypeId = $productType->getName();
        $em->clear();

        return $productTypeId;
    }

    public function testOptionsRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $this->options($this->getListRouteName(), ['entity' => $entityType]);

        self::assertEquals(
            [
                sprintf('Process "options" action for "%s" (MASTER_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetListRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $this->createProduct();
        $this->cget(['entity' => $entityType]);

        self::assertEquals(
            [
                sprintf('Process "get_list" action for "%s" (MASTER_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $this->get(['entity' => $entityType, 'id' => (string)$entityId]);

        self::assertEquals(
            [
                sprintf('Process "get" action for "%s" (MASTER_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testCreateRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];
        $this->post(['entity' => $entityType], $data);

        self::assertEquals(
            [
                sprintf('Process "create" action for "%s" (MASTER_REQUEST)', TestProduct::class),
                sprintf('Process "get" action for "%s" (SUB_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testCreateRequestWithIncludedEntities()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $includedEntityType = $this->getEntityType(TestProductType::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'attributes'    => [
                    'name' => 'test'
                ],
                'relationships' => [
                    'productType' => [
                        'data' => ['type' => $includedEntityType, 'id' => 'new_product_type']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => $includedEntityType,
                    'id'   => 'new_product_type'
                ]
            ]
        ];
        $this->post(['entity' => $entityType], $data);

        self::assertEquals(
            [
                sprintf('Process "create" action for "%s" (MASTER_REQUEST)', TestProduct::class),
                sprintf('Process "create" action for "%s" (SUB_REQUEST)', TestProductType::class),
                sprintf('Process "get" action for "%s" (SUB_REQUEST)', TestProduct::class),
                sprintf('Process "get" action for "%s" (SUB_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testUpdateRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entityId,
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];
        $this->patch(['entity' => $entityType, 'id' => (string)$entityId], $data);

        self::assertEquals(
            [
                sprintf('Process "update" action for "%s" (MASTER_REQUEST)', TestProduct::class),
                sprintf('Process "get" action for "%s" (SUB_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testUpdateRequestWithIncludedEntities()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $includedEntityType = $this->getEntityType(TestProductType::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'attributes'    => [
                    'name' => 'test'
                ],
                'relationships' => [
                    'productType' => [
                        'data' => ['type' => $includedEntityType, 'id' => 'new_product_type']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => $includedEntityType,
                    'id'   => 'new_product_type'
                ]
            ]
        ];
        $this->patch(['entity' => $entityType, 'id' => (string)$entityId], $data);

        self::assertEquals(
            [
                sprintf('Process "update" action for "%s" (MASTER_REQUEST)', TestProduct::class),
                sprintf('Process "create" action for "%s" (SUB_REQUEST)', TestProductType::class),
                sprintf('Process "get" action for "%s" (SUB_REQUEST)', TestProduct::class),
                sprintf('Process "get" action for "%s" (SUB_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testDeleteRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $this->delete(['entity' => $entityType, 'id' => (string)$entityId]);

        self::assertEquals(
            [
                sprintf('Process "delete" action for "%s" (MASTER_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testDeleteListRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $this->cdelete(['entity' => $entityType], ['filter[id]' => (string)$entityId]);

        self::assertEquals(
            [
                sprintf('Process "delete_list" action for "%s" (MASTER_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetSubresourceRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'productType']
        );

        self::assertEquals(
            [
                sprintf('Process "get_subresource" action for "%s" (MASTER_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetRelationshipRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $this->getRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'productType']
        );

        self::assertEquals(
            [
                sprintf('Process "get_relationship" action for "%s" (MASTER_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testUpdateRelationshipRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->createProduct();
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $associatedEntityId = $this->createProductType();
        $data = [
            'data' => [
                'type' => $associatedEntityType,
                'id'   => $associatedEntityId
            ]
        ];
        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'productType'],
            $data
        );

        self::assertEquals(
            [
                sprintf('Process "update_relationship" action for "%s" (MASTER_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }
}
