<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;

/**
 * @dbIsolationPerTest
 */
class UpdateListForEntityWithCustomIdTest extends RestJsonApiUpdateListTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/update_list_for_entity_with_custom_id.yml'
        ]);
    }

    public function testCreateEntities(): void
    {
        $operationId = $this->processUpdateList(
            TestCustomIdentifier::class,
            $this->getCreateEntitiesRequestData()
        );
        $this->assertCreateEntitiesResult($operationId);
    }

    public function testCreateEntitiesWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestCustomIdentifier::class,
            $this->getCreateEntitiesRequestData()
        );
        $this->assertCreateEntitiesResult($operationId);
    }

    public function testCreateEntitiesWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestCustomIdentifier::class,
            $this->getCreateEntitiesRequestData()
        );

        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'new_entity_1',
                        'attributes' => ['name' => 'New Entity 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new_entity_2',
                        'attributes' => ['name' => 'New Entity 2']
                    ]
                ]
            ],
            $response
        );

        $this->assertCreateEntitiesResult($this->getLastOperationId());
    }

    private function getCreateEntitiesRequestData(): array
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        return [
            'data' => [
                [
                    'type' => $entityType,
                    'id' => 'new_entity_1',
                    'attributes' => ['name' => 'New Entity 1']
                ],
                [
                    'type' => $entityType,
                    'id' => 'new_entity_2',
                    'attributes' => ['name' => 'New Entity 2']
                ]
            ]
        ];
    }

    private function assertCreateEntitiesResult(int $operationId): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $response = $this->cget(['entity' => $entityType], ['page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_1',
                        'attributes' => ['name' => 'Existing Entity 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_2',
                        'attributes' => ['name' => 'Existing Entity 2']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new_entity_1',
                        'attributes' => ['name' => 'New Entity 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new_entity_2',
                        'attributes' => ['name' => 'New Entity 2']
                    ]
                ]
            ],
            $response
        );

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
                    ['new_entity_1', 'new_entity_1', false],
                    ['new_entity_2', 'new_entity_2', false]
                ]
            ],
            $operation->getAffectedEntities()
        );
    }

    public function testUpdateEntities(): void
    {
        $operationId = $this->processUpdateList(
            TestCustomIdentifier::class,
            $this->getUpdateEntitiesRequestData()
        );
        $this->assertUpdateEntitiesResult($operationId);
    }

    public function testUpdateEntitiesWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestCustomIdentifier::class,
            $this->getUpdateEntitiesRequestData()
        );
        $this->assertUpdateEntitiesResult($operationId);
    }

    public function testUpdateEntitiesWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestCustomIdentifier::class,
            $this->getUpdateEntitiesRequestData()
        );

        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_1',
                        'attributes' => ['name' => 'Updated Entity 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_2',
                        'attributes' => ['name' => 'Updated Entity 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertUpdateEntitiesResult($this->getLastOperationId());
    }

    private function getUpdateEntitiesRequestData(): array
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        return [
            'data' => [
                [
                    'meta' => ['update' => true],
                    'type' => $entityType,
                    'id' => 'existing_entity_1',
                    'attributes' => ['name' => 'Updated Entity 1']
                ],
                [
                    'meta' => ['update' => true],
                    'type' => $entityType,
                    'id' => 'existing_entity_2',
                    'attributes' => ['name' => 'Updated Entity 2']
                ]
            ]
        ];
    }

    private function assertUpdateEntitiesResult(int $operationId): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_1',
                        'attributes' => ['name' => 'Updated Entity 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_2',
                        'attributes' => ['name' => 'Updated Entity 2']
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
                'createCount' => 0,
                'updateCount' => 2
            ],
            $summary
        );
        self::assertSame(
            [
                'primary' => [
                    ['existing_entity_1', 'existing_entity_1', true],
                    ['existing_entity_2', 'existing_entity_2', true]
                ]
            ],
            $operation->getAffectedEntities()
        );
    }

    public function testCreateAndUpdateEntities(): void
    {
        $operationId = $this->processUpdateList(
            TestCustomIdentifier::class,
            $this->getCreateAndUpdateEntitiesRequestData()
        );
        $this->assertCreateAndUpdateEntitiesResult($operationId);
    }

    public function testCreateAndUpdateEntitiesWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestCustomIdentifier::class,
            $this->getCreateAndUpdateEntitiesRequestData()
        );
        $this->assertCreateAndUpdateEntitiesResult($operationId);
    }

    public function testCreateAndUpdateEntitiesWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestCustomIdentifier::class,
            $this->getCreateAndUpdateEntitiesRequestData()
        );

        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'new_entity_1',
                        'attributes' => ['name' => 'New Entity 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_1',
                        'attributes' => ['name' => 'Updated Entity 1']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertCreateAndUpdateEntitiesResult($this->getLastOperationId());
    }

    private function getCreateAndUpdateEntitiesRequestData(): array
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        return [
            'data' => [
                [
                    'type' => $entityType,
                    'id' => 'new_entity_1',
                    'attributes' => ['name' => 'New Entity 1']
                ],
                [
                    'meta' => ['update' => true],
                    'type' => $entityType,
                    'id' => 'existing_entity_1',
                    'attributes' => ['name' => 'Updated Entity 1']
                ]
            ]
        ];
    }

    private function assertCreateAndUpdateEntitiesResult(int $operationId): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_1',
                        'attributes' => ['name' => 'Updated Entity 1']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'existing_entity_2',
                        'attributes' => ['name' => 'Existing Entity 2']
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new_entity_1',
                        'attributes' => ['name' => 'New Entity 1']
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
                'createCount' => 1,
                'updateCount' => 1
            ],
            $summary
        );
        self::assertSame(
            [
                'primary' => [
                    ['new_entity_1', 'new_entity_1', false],
                    ['existing_entity_1', 'existing_entity_1', true]
                ]
            ],
            $operation->getAffectedEntities()
        );
    }
}
