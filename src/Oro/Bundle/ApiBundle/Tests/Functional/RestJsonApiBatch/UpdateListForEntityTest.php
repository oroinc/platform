<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProductType;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpdateListForEntityTest extends RestJsonApiUpdateListTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/update_list_for_entity.yml']);
    }

    public function testCreateEntities()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ]
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new',
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateEntities()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Updated Department 2']
                    ]
                ]
            ]
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Updated Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateAndUpdateEntities()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ]
                ]
            ]
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateEntitiesWhenRequestDataHasHeaderAndMetaAndLinksSections()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'jsonapi' => ['version' => '1.0'],
                'meta'    => ['authors' => ['John Doo']],
                'links'   => [['self' => 'http://example.com/api/' . $entityType]],
                'data'    => [
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ]
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new',
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateEntityWithoutTypeInRequestData()
    {
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 400,
                'title'  => 'entity type constraint',
                'detail' => 'The entity class must be set in the context.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateEntityWhenCreateActionDisabled()
    {
        $entityType = $this->getEntityType(TestDepartment::class);

        $operationId = $this->sendUpdateListRequest(TestDepartment::class, [
            'data' => [
                [
                    'type' => $entityType,
                    'attributes' => ['name' => 'Updated Department']
                ]
            ]
        ]);

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['create' => false]],
            true
        );

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);
        $this->consumeAllMessages();

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 405,
                'title' => 'action not allowed exception',
                'detail' => 'The action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateEntityWhenCreateActionDisabledAfterUpdateListRequestWasAlreadySent()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->sendUpdateListRequest(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ]
        );

        $this->consumeMessages();

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['create' => false]],
            true
        );

        $this->consumeMessages();

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 405,
                'title' => 'action not allowed exception',
                'detail' => 'The action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['create' => true]],
            true
        );
    }

    public function testTryToCreateEntityWhenGetActionDisabledAfterUpdateListRequestWasAlreadySent()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->sendUpdateListRequest(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ]
        );

        $this->consumeMessages();

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => false]],
            true
        );

        $this->consumeMessages();

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 405,
                'title' => 'action not allowed exception',
                'detail' => 'The action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => true]],
            true
        );
    }

    public function testTryToCreateAndUpdateEntitiesWithoutCreatePermission()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            TestDepartment::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'EDIT'   => AccessLevel::GLOBAL_LEVEL,
                'DELETE' => AccessLevel::GLOBAL_LEVEL,
                'ASSIGN' => AccessLevel::GLOBAL_LEVEL
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ]
                ]
            ],
            false
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 403,
                'title'  => 'access denied exception',
                'detail' => 'No access to this type of entities.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateAndUpdateEntitiesWithoutEditPermission()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            TestDepartment::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::GLOBAL_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::GLOBAL_LEVEL,
                'ASSIGN' => AccessLevel::GLOBAL_LEVEL
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ]
                ]
            ],
            false
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 403,
                'title'  => 'access denied exception',
                'detail' => 'No access to this type of entities.',
                'source' => ['pointer' => '/data/1']
            ],
            $operationId
        );
    }

    public function testTryToUpdateNotExistingEntity()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => $entityType,
                        'id'         => '99999999',
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 404,
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToUpdateEntityWithoutTypeInRequestData()
    {
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta'       => ['update' => true],
                        'id'         => '1',
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 400,
                'title'  => 'entity type constraint',
                'detail' => 'The entity class must be set in the context.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToUpdateEntityWhenUpdateActionDisabled()
    {
        $entityType = $this->getEntityType(TestDepartment::class);

        $operationId = $this->sendUpdateListRequest(TestDepartment::class, [
            'data' => [
                [
                    'meta' => ['update' => true],
                    'type' => $entityType,
                    'id' => '1',
                    'attributes' => ['name' => 'Updated Department']
                ]
            ]
        ]);

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update' => false]],
            true
        );

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);
        $this->consumeAllMessages();

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 405,
                'title' => 'action not allowed exception',
                'detail' => 'The action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToUpdateEntityWhenUpdateActionDisabledAfterUpdateListRequestWasAlreadySent()
    {
        $entityType = $this->getEntityType(TestDepartment::class);

        $operationId = $this->sendUpdateListRequest(TestDepartment::class, [
            'data' => [
                [
                    'meta' => ['update' => true],
                    'type' => $entityType,
                    'id' => '1',
                    'attributes' => ['name' => 'Updated Department']
                ]
            ]
        ]);

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update' => false]],
            true
        );

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);
        $this->consumeAllMessages();

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 405,
                'title' => 'action not allowed exception',
                'detail' => 'The action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update' => true]],
            true
        );
    }

    public function testTryToUpdateEntityWhenGetActionDisabledAfterUpdateListRequestWasAlreadySent()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->sendUpdateListRequest(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'id' => '1',
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ]
        );

        $this->consumeMessages();

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => false]],
            true
        );
        $this->consumeMessages();

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 405,
                'title' => 'action not allowed exception',
                'detail' => 'The action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );

        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => true]],
            true
        );
    }

    public function testTryToUpdateEntityWithoutIdInRequestData()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => $entityType,
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 400,
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of an entity must be set in the context.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateEntitiesWhenEntityTypeInRequestDataDoesNotEqualToEntityTypeOfResource()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestEmployee::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 400,
                'title'  => 'entity type constraint',
                'detail' => sprintf(
                    'The entity type "%s" is not supported by this batch operation.',
                    $entityType
                ),
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testCreateEntitiesWhenDifferentEntityTypesExistInRequestData()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $anotherEntityType = $this->getEntityType(TestEmployee::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type'       => $anotherEntityType,
                        'attributes' => ['name' => 'New Employee']
                    ],
                    [
                        'type'       => $entityType,
                        'attributes' => ['title' => 'New Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 400,
                'title'  => 'entity type constraint',
                'detail' => sprintf(
                    'The entity type "%s" is not supported by this batch operation.',
                    $anotherEntityType
                ),
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new',
                        'attributes' => ['title' => 'New Department']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateEntitiesWhenRequestDataContainsEntityForWhichUpdateListActionIsNotEnabled()
    {
        $anotherEntityType = $this->getEntityType(TestProductType::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type'       => $anotherEntityType,
                        'attributes' => ['name' => 'New Product Type']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 400,
                'title'  => 'entity type constraint',
                'detail' => sprintf(
                    'The entity type "%s" is not supported by this batch operation.',
                    $anotherEntityType
                ),
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }
}
