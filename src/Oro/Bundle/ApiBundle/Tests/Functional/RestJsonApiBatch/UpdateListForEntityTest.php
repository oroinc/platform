<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/update_list_for_entity.yml']);
    }

    private function getDepartmentId(string $title): int
    {
        /** @var TestDepartment|null $department */
        $department = $this->getEntityManager()->getRepository(TestDepartment::class)->findOneBy(['name' => $title]);
        if (null === $department) {
            throw new \RuntimeException(sprintf('The department "%s" not found.', $title));
        }

        return $department->getId();
    }

    public function testCreateEntities(): void
    {
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            $this->getCreateEntitiesRequestData()
        );
        $this->assertCreateEntitiesResult($operationId);
    }

    public function testCreateEntitiesWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestDepartment::class,
            $this->getCreateEntitiesRequestData()
        );
        $this->assertCreateEntitiesResult($operationId);
    }

    public function testCreateEntitiesWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestDepartment::class,
            $this->getCreateEntitiesRequestData()
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertCreateEntitiesResult($this->getLastOperationId());
    }

    private function getCreateEntitiesRequestData(): array
    {
        $entityType = $this->getEntityType(TestDepartment::class);

        return [
            'data' => [
                [
                    'type' => $entityType,
                    'attributes' => ['title' => 'New Department 1']
                ],
                [
                    'type' => $entityType,
                    'attributes' => ['title' => 'New Department 2']
                ]
            ]
        ];
    }

    private function assertCreateEntitiesResult(int $operationId): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);

        $response = $this->cget(['entity' => $entityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $operation = $this->getEntityManager()->find(AsyncOperation::class, $operationId);
        $summary = $operation->getSummary();
        unset($summary['aggregateTime']);
        self::assertSame(
            [
                'readCount' => 2,
                'writeCount' => 2,
                'errorCount' => 0,
                'createCount' => 2,
                'updateCount' => 0
            ],
            $summary
        );
        self::assertSame(
            [
                'primary' => [
                    [$this->getDepartmentId('New Department 1'), null, false],
                    [$this->getDepartmentId('New Department 2'), null, false]
                ]
            ],
            $operation->getAffectedEntities()
        );
    }

    public function testUpdateEntities(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
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
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Updated Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateAndUpdateEntities(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
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
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateEntitiesWhenRequestDataHasHeaderAndMetaAndLinksSections(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'jsonapi' => ['version' => '1.0'],
                'meta' => ['authors' => ['John Doo']],
                'links' => [['self' => 'http://example.com/api/' . $entityType]],
                'data' => [
                    [
                        'type' => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ]
        );

        $response = $this->cget(['entity' => $entityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateEntityWithoutTypeInRequestData(): void
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
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'entity type constraint',
                'detail' => 'The entity class must be set in the context.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateEntityWhenCreateActionDisabled(): void
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
                'detail' => 'The "create" action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateEntityWhenCreateActionDisabledAfterUpdateListRequestWasAlreadySent(): void
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
                'detail' => 'The "create" action is not allowed.',
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

    public function testTryToCreateEntityWhenGetActionDisabledAfterUpdateListRequestWasAlreadySent(): void
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
                'detail' => 'The "create" action is not allowed.',
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

    public function testTryToCreateAndUpdateEntitiesWithoutCreatePermission(): void
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            TestDepartment::class,
            [
                'VIEW' => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'EDIT' => AccessLevel::GLOBAL_LEVEL,
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
                        'type' => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
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
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 403,
                'title' => 'access denied exception',
                'detail' => 'No access to this type of entities.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateAndUpdateEntitiesWithoutEditPermission(): void
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            TestDepartment::class,
            [
                'VIEW' => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::GLOBAL_LEVEL,
                'EDIT' => AccessLevel::NONE_LEVEL,
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
                        'type' => $entityType,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
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
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 403,
                'title' => 'access denied exception',
                'detail' => 'No access to this type of entities.',
                'source' => ['pointer' => '/data/1']
            ],
            $operationId
        );
    }

    public function testTryToUpdateNotExistingEntity(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'id' => '99999999',
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 404,
                'title' => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToUpdateEntityWithoutTypeInRequestData(): void
    {
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta' => ['update' => true],
                        'id' => '1',
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'entity type constraint',
                'detail' => 'The entity class must be set in the context.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToUpdateEntityWhenUpdateActionDisabled(): void
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
                'detail' => 'The "update" action is not allowed.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToUpdateEntityWhenUpdateActionDisabledAfterUpdateListRequestWasAlreadySent(): void
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
                'detail' => 'The "update" action is not allowed.',
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

    public function testTryToUpdateEntityWhenGetActionDisabledAfterUpdateListRequestWasAlreadySent(): void
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
                'detail' => 'The "update" action is not allowed.',
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

    public function testTryToUpdateEntityWithoutIdInRequestData(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'meta' => ['update' => true],
                        'type' => $entityType,
                        'attributes' => ['name' => 'Updated Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'entity identifier constraint',
                'detail' => 'The identifier of an entity must be set in the context.',
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testTryToCreateEntitiesWhenEntityTypeInRequestDataDoesNotEqualToEntityTypeOfResource(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateList(
            TestEmployee::class,
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'attributes' => ['title' => 'New Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'entity type constraint',
                'detail' => sprintf(
                    'The entity type "%s" is not supported by this batch operation.',
                    $entityType
                ),
                'source' => ['pointer' => '/data/0']
            ],
            $operationId
        );
    }

    public function testCreateEntitiesWhenDifferentEntityTypesExistInRequestData(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $anotherEntityType = $this->getEntityType(TestEmployee::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $anotherEntityType,
                        'attributes' => ['name' => 'New Employee']
                    ],
                    [
                        'type' => $entityType,
                        'attributes' => ['title' => 'New Department']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'entity type constraint',
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
                        'type' => $entityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
    }

    public function testTryToCreateEntitiesWhenRequestDataContainsEntityForWhichUpdateListActionIsNotEnabled(): void
    {
        $anotherEntityType = $this->getEntityType(TestProductType::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $anotherEntityType,
                        'attributes' => ['name' => 'New Product Type']
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'entity type constraint',
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
