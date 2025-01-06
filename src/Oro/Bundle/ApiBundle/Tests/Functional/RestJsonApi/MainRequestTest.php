<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;

/**
 * Tests that "main request" flag is TRUE for main requests and FALSE for sub requests.
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MainRequestTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/main_request.yml'
        ]);
    }

    private function getProductId(): int
    {
        return $this->getReference('product')->getId();
    }

    private function getProductTypeId(int $number): string
    {
        return $this->getReference('product_type_' . $number)->getName();
    }

    private function getDepartmentId(): int
    {
        return $this->getReference('department')->getId();
    }

    private function getEmployeeId(int $number): string
    {
        return $this->getReference('employee_' . $number)->getId();
    }

    public function testOptionsRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $this->options($this->getListRouteName(), ['entity' => $entityType]);

        self::assertEquals(
            [
                sprintf('Process "options" action for "%s" (MAIN_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetListRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $this->cget(['entity' => $entityType]);

        self::assertEquals(
            [
                sprintf('Process "get_list" action for "%s" (MAIN_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->getProductId();
        $this->get(['entity' => $entityType, 'id' => (string)$entityId]);

        self::assertEquals(
            [
                sprintf('Process "get" action for "%s" (MAIN_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testCreateRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];
        $this->post(['entity' => $entityType], $data);

        self::assertEquals(
            [
                sprintf('Process "create" action for "%s" (MAIN_REQUEST)', TestProduct::class),
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
            'data' => [
                'type' => $entityType,
                'attributes' => [
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
                    'id' => 'new_product_type'
                ]
            ]
        ];
        $this->post(['entity' => $entityType], $data);

        self::assertEquals(
            [
                sprintf('Process "create" action for "%s" (MAIN_REQUEST)', TestProduct::class),
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
        $entityId = $this->getProductId();
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];
        $this->patch(['entity' => $entityType, 'id' => (string)$entityId], $data);

        self::assertEquals(
            [
                sprintf('Process "update" action for "%s" (MAIN_REQUEST)', TestProduct::class),
                sprintf('Process "get" action for "%s" (SUB_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testUpdateRequestWithIncludedEntities()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->getProductId();
        $includedEntityType = $this->getEntityType(TestProductType::class);
        $data = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'attributes' => [
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
                    'id' => 'new_product_type'
                ]
            ]
        ];
        $this->patch(['entity' => $entityType, 'id' => (string)$entityId], $data);

        self::assertEquals(
            [
                sprintf('Process "update" action for "%s" (MAIN_REQUEST)', TestProduct::class),
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
        $entityId = $this->getProductId();
        $this->delete(['entity' => $entityType, 'id' => (string)$entityId]);

        self::assertEquals(
            [
                sprintf('Process "delete" action for "%s" (MAIN_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testDeleteListRequest()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->getProductId();
        $this->cdelete(['entity' => $entityType], ['filter[id]' => (string)$entityId]);

        self::assertEquals(
            [
                sprintf('Process "delete_list" action for "%s" (MAIN_REQUEST)', TestProduct::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetSubresourceRequestForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->getProductId();
        $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'productType']
        );

        self::assertEquals(
            [
                sprintf('Process "get_subresource" action for "%s" (MAIN_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetRelationshipRequestForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->getProductId();
        $this->getRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'productType']
        );

        self::assertEquals(
            [
                sprintf('Process "get_relationship" action for "%s" (MAIN_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testUpdateRelationshipRequestForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $entityId = $this->getProductId();
        $associatedEntityType = $this->getEntityType(TestProductType::class);
        $associatedEntityId = $this->getProductTypeId(2);
        $data = [
            'data' => ['type' => $associatedEntityType, 'id' => $associatedEntityId]
        ];
        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'productType'],
            $data
        );

        self::assertEquals(
            [
                sprintf('Process "update_relationship" action for "%s" (MAIN_REQUEST)', TestProductType::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetSubresourceRequestForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $entityId = $this->getDepartmentId();
        $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'staff']
        );

        self::assertEquals(
            [
                sprintf('Process "get_subresource" action for "%s" (MAIN_REQUEST)', EntityIdentifier::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testGetRelationshipRequestForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $entityId = $this->getDepartmentId();
        $this->getRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'staff']
        );

        self::assertEquals(
            [
                sprintf('Process "get_relationship" action for "%s" (MAIN_REQUEST)', EntityIdentifier::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testUpdateRelationshipRequestForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $entityId = $this->getDepartmentId();
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $associatedEntityId = $this->getEmployeeId(2);
        $data = [
            'data' => [
                ['type' => $associatedEntityType, 'id' => $associatedEntityId]
            ]
        ];
        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'staff'],
            $data
        );

        self::assertEquals(
            [
                sprintf('Process "update_relationship" action for "%s" (MAIN_REQUEST)', EntityIdentifier::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testAddRelationshipRequestForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $entityId = $this->getDepartmentId();
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $associatedEntityId = $this->getEmployeeId(2);
        $data = [
            'data' => [
                ['type' => $associatedEntityType, 'id' => $associatedEntityId]
            ]
        ];
        $this->postRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'staff'],
            $data
        );

        self::assertEquals(
            [
                sprintf('Process "add_relationship" action for "%s" (MAIN_REQUEST)', EntityIdentifier::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }

    public function testDeleteRelationshipRequestForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $entityId = $this->getDepartmentId();
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $associatedEntityId = $this->getEmployeeId(1);
        $data = [
            'data' => [
                ['type' => $associatedEntityType, 'id' => $associatedEntityId]
            ]
        ];
        $this->deleteRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'staff'],
            $data
        );

        self::assertEquals(
            [
                sprintf('Process "delete_relationship" action for "%s" (MAIN_REQUEST)', EntityIdentifier::class)
            ],
            $this->getRequestTypeLogMessages()
        );
    }
}
