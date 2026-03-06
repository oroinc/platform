<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOwner;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestUniqueKeyIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Component\MessageQueue\Job\Job;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class UpdateListForEntityWithIncludedDataTest extends RestJsonApiUpdateListTestCase
{
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

    private function getEmployeeId(string $name): int
    {
        /** @var TestEmployee|null $employee */
        $employee = $this->getEntityManager()->getRepository(TestEmployee::class)->findOneBy(['name' => $name]);
        if (null === $employee) {
            throw new \RuntimeException(sprintf('The employee "%s" not found.', $name));
        }

        return $employee->getId();
    }

    public function testCreateEntitiesWithNotIntersectedRelationships(): void
    {
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            $this->getCreateEntitiesWithNotIntersectedRelationshipsRequestData()
        );
        $this->assertCreateEntitiesWithNotIntersectedRelationshipsResult($operationId);
    }

    public function testCreateEntitiesWithNotIntersectedRelationshipsWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestDepartment::class,
            $this->getCreateEntitiesWithNotIntersectedRelationshipsRequestData()
        );
        $this->assertCreateEntitiesWithNotIntersectedRelationshipsResult($operationId);
    }

    public function testCreateEntitiesWithNotIntersectedRelationshipsWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestDepartment::class,
            $this->getCreateEntitiesWithNotIntersectedRelationshipsRequestData()
        );

        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new'],
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new',
                        'meta' => ['includeId' => 'new_employee1'],
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new',
                        'meta' => ['includeId' => 'new_employee2'],
                        'attributes' => ['name' => 'New Employee 2']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new',
                        'meta' => ['includeId' => 'new_employee3'],
                        'attributes' => ['name' => 'New Employee 3']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertCreateEntitiesWithNotIntersectedRelationshipsResult($this->getLastOperationId());
    }

    private function getCreateEntitiesWithNotIntersectedRelationshipsRequestData(): array
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        return [
            'data' => [
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 1'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee1']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 2'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee2'],
                                ['type' => $employeeEntityType, 'id' => 'new_employee3']
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee1',
                    'attributes' => ['name' => 'New Employee 1']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee2',
                    'attributes' => ['name' => 'New Employee 2']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee3',
                    'attributes' => ['name' => 'New Employee 3']
                ]
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function assertCreateEntitiesWithNotIntersectedRelationshipsResult(int $operationId): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new'],
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $newDepartment1Id = (int)$responseContent['data'][2]['id'];
        $newDepartment2Id = (int)$responseContent['data'][3]['id'];
        $newEmployee1Id = $this->getEmployeeId('New Employee 1');
        $newEmployee2Id = $this->getEmployeeId('New Employee 2');
        $newEmployee3Id = $this->getEmployeeId('New Employee 3');
        $response = $this->cget(['entity' => $employeeEntityType], ['sort' => 'name', 'page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Existing Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee1Id,
                        'attributes' => ['name' => 'New Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment1Id]
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee2Id,
                        'attributes' => ['name' => 'New Employee 2'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee3Id,
                        'attributes' => ['name' => 'New Employee 3'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
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
                    [$this->getDepartmentId('New Department 1'), null, false],
                    [$this->getDepartmentId('New Department 2'), null, false]
                ],
                'included' => [
                    [TestEmployee::class, $this->getEmployeeId('New Employee 1'), 'new_employee1', false],
                    [TestEmployee::class, $this->getEmployeeId('New Employee 2'), 'new_employee2', false],
                    [TestEmployee::class, $this->getEmployeeId('New Employee 3'), 'new_employee3', false]
                ]
            ],
            $operation->getAffectedEntities()
        );
    }

    public function testCreateEntitiesWithIntersectedRelationships(): void
    {
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            $this->getCreateEntitiesWithIntersectedRelationshipsRequestData()
        );
        $this->assertCreateEntitiesWithIntersectedRelationshipsResult($operationId);
    }

    public function testCreateEntitiesWithIntersectedRelationshipsWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestDepartment::class,
            $this->getCreateEntitiesWithIntersectedRelationshipsRequestData()
        );
        $this->assertCreateEntitiesWithIntersectedRelationshipsResult($operationId);
    }

    public function testCreateEntitiesWithIntersectedRelationshipsWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestDepartment::class,
            $this->getCreateEntitiesWithIntersectedRelationshipsRequestData()
        );

        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new'],
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new',
                        'meta' => ['includeId' => 'new_employee1'],
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new',
                        'meta' => ['includeId' => 'new_employee3'],
                        'attributes' => ['name' => 'New Employee 3']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new',
                        'meta' => ['includeId' => 'new_employee2'],
                        'attributes' => ['name' => 'New Employee 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $this->assertCreateEntitiesWithIntersectedRelationshipsResult($this->getLastOperationId());
    }

    private function getCreateEntitiesWithIntersectedRelationshipsRequestData(): array
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        return [
            'data' => [
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 1'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee3'],
                                ['type' => $employeeEntityType, 'id' => 'new_employee1']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 2'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee2'],
                                ['type' => $employeeEntityType, 'id' => 'new_employee1']
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee1',
                    'attributes' => ['name' => 'New Employee 1']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee2',
                    'attributes' => ['name' => 'New Employee 2']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee3',
                    'attributes' => ['name' => 'New Employee 3']
                ]
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function assertCreateEntitiesWithIntersectedRelationshipsResult(int $operationId): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new'],
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $newDepartment1Id = (int)$responseContent['data'][2]['id'];
        $newDepartment2Id = (int)$responseContent['data'][3]['id'];
        $newEmployee1Id = $this->getEmployeeId('New Employee 1');
        $newEmployee2Id = $this->getEmployeeId('New Employee 2');
        $newEmployee3Id = $this->getEmployeeId('New Employee 3');
        $response = $this->cget(['entity' => $employeeEntityType], ['sort' => 'name', 'page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Existing Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee1Id,
                        'attributes' => ['name' => 'New Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee2Id,
                        'attributes' => ['name' => 'New Employee 2'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee3Id,
                        'attributes' => ['name' => 'New Employee 3'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment1Id]
                            ]
                        ]
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
                    [$this->getDepartmentId('New Department 1'), null, false],
                    [$this->getDepartmentId('New Department 2'), null, false]
                ],
                'included' => [
                    [TestEmployee::class, $this->getEmployeeId('New Employee 1'), 'new_employee1', false],
                    [TestEmployee::class, $this->getEmployeeId('New Employee 3'), 'new_employee3', false],
                    [TestEmployee::class, $this->getEmployeeId('New Employee 2'), 'new_employee2', false]
                ]
            ],
            $operation->getAffectedEntities()
        );
    }

    public function testUpdateWithIntersectedRelationships(): void
    {
        $operationId = $this->processUpdateList(
            TestUniqueKeyIdentifier::class,
            $this->getUpdateWithIntersectedRelationshipsRequestData()
        );

        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            false,
            [
                'readCount' => 2,
                'writeCount' => 2,
                'errorCount' => 0,
                'createCount' => 0,
                'updateCount' => 2
            ]
        );

        $this->assertUpdateWithIntersectedRelationshipsResult();
    }

    public function testUpdateWithIntersectedRelationshipsWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestUniqueKeyIdentifier::class,
            $this->getUpdateWithIntersectedRelationshipsRequestData()
        );

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
        self::assertEquals(AsyncOperation::STATUS_SUCCESS, $operation->getStatus());
        self::assertFalse($operation->isHasErrors());

        $this->assertUpdateWithIntersectedRelationshipsResult();
    }

    public function testUpdateWithIntersectedRelationshipsWithoutMessageQueueAndWithSyncMode(): void
    {
        $data = $this->getUpdateWithIntersectedRelationshipsRequestData();

        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestUniqueKeyIdentifier::class,
            $data
        );

        $expectedData = $data;
        foreach ($expectedData['data'] as &$item) {
            unset($item['meta']);
        }
        unset($item);
        foreach ($expectedData['included'] as &$item) {
            unset($item['meta']);
        }
        unset($item);
        $this->assertResponseContains($expectedData, $response);
        $this->assertUpdateWithIntersectedRelationshipsResult();
    }

    private function getUpdateWithIntersectedRelationshipsRequestData(): array
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $entity1Id = $this->getReference('item1')->id;
        $entity11Id = $this->getReference('item11')->id;
        $entity111Id = $this->getReference('item111')->id;
        $entity1111Id = $this->getReference('item1111')->id;
        $entity2Id = $this->getReference('item2')->id;
        $entity21Id = $this->getReference('item21')->id;

        return [
            'data' => [
                [
                    'type' => $entityType,
                    'id' => (string)$entity1Id,
                    'meta' => ['update' => true],
                    'attributes' => ['name' => 'Updated Item 1'],
                    'relationships' => [
                        'children' => ['data' => [['type' => $entityType, 'id' => (string)$entity11Id]]]
                    ]
                ],
                [
                    'type' => $entityType,
                    'id' => (string)$entity2Id,
                    'meta' => ['update' => true],
                    'attributes' => ['name' => 'Updated Item 2'],
                    'relationships' => [
                        'children' => ['data' => [['type' => $entityType, 'id' => (string)$entity21Id]]]
                    ]
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
                    'id' => (string)$entity21Id,
                    'meta' => ['update' => true],
                    'attributes' => ['name' => 'Updated Item 2.1'],
                    'relationships' => [
                        'parent' => ['data' => ['type' => $entityType, 'id' => (string)$entity2Id]],
                        'relations' => ['data' => [['type' => $entityType, 'id' => (string)$entity111Id]]]
                    ]
                ]
            ]
        ];
    }

    private function assertUpdateWithIntersectedRelationshipsResult(): void
    {
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
            'Updated Item 2',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item2')->id)->name
        );
        self::assertEquals(
            'Updated Item 2.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item21')->id)->name
        );
    }

    public function testTryToUpdateWithIntersectedRelationshipsAndHasError(): void
    {
        $operationId = $this->processUpdateList(
            TestUniqueKeyIdentifier::class,
            $this->getTryToUpdateWithIntersectedRelationshipsAndHasErrorRequestData(),
            false
        );

        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            true,
            [
                'readCount' => 2,
                'writeCount' => 1,
                'errorCount' => 1,
                'createCount' => 0,
                'updateCount' => 1
            ]
        );
        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/name']
            ],
            $operationId
        );

        $this->assertTryToUpdateWithIntersectedRelationshipsAndHasErrorResult();
    }

    public function testTryToUpdateWithIntersectedRelationshipsAndHasErrorWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestUniqueKeyIdentifier::class,
            $this->getTryToUpdateWithIntersectedRelationshipsAndHasErrorRequestData()
        );

        $operation = $this->getEntityManager()->find(AsyncOperation::class, $operationId);
        $summary = $operation->getSummary();
        unset($summary['aggregateTime']);
        self::assertSame(
            [
                'readCount' => 2,
                'writeCount' => 1,
                'errorCount' => 1,
                'createCount' => 0,
                'updateCount' => 1
            ],
            $summary
        );
        self::assertEquals(AsyncOperation::STATUS_FAILED, $operation->getStatus());
        self::assertTrue($operation->isHasErrors());
        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/name']
            ],
            $operationId
        );

        $this->assertTryToUpdateWithIntersectedRelationshipsAndHasErrorResult();
    }

    public function testTryToUpdateWithIntersectedRelationshipsAndHasErrorWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestUniqueKeyIdentifier::class,
            $this->getTryToUpdateWithIntersectedRelationshipsAndHasErrorRequestData(),
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/name']
            ],
            $response
        );
        $this->assertTryToUpdateWithIntersectedRelationshipsAndHasErrorResult();
    }

    private function getTryToUpdateWithIntersectedRelationshipsAndHasErrorRequestData(): array
    {
        $data = $this->getUpdateWithIntersectedRelationshipsRequestData();
        $data['data'][0]['attributes']['name'] = null;

        return $data;
    }

    private function assertTryToUpdateWithIntersectedRelationshipsAndHasErrorResult(): void
    {
        $em = $this->getEntityManager(TestUniqueKeyIdentifier::class);
        self::assertEquals(
            'Item 1',
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
            'Updated Item 2',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item2')->id)->name
        );
        self::assertEquals(
            'Updated Item 2.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item21')->id)->name
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntitiesWithProcessedIncludedItemsButWithoutIncludedItemsOnSecondIteration(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee1']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ]
                ]
            ]
        );

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $newDepartment2Id = (int)$responseContent['data'][3]['id'];
        $newEmployee1Id = $this->getEmployeeId('New Employee 1');
        $response = $this->cget(['entity' => $employeeEntityType], ['sort' => 'name', 'page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Existing Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee1Id,
                        'attributes' => ['name' => 'New Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateEntitiesWithSeveralIncludedDataChunks(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $chunkSize = $this->getChunkSizeProvider()->getChunkSize(TestDepartment::class);
        $includedDataChunkSize = $this->getChunkSizeProvider()->getIncludedDataChunkSize(TestDepartment::class);
        $data = [];
        $expectedDepartments = [
            'data' => [
                [
                    'type' => $departmentEntityType,
                    'id' => '<toString(@department1->id)>',
                    'attributes' => ['title' => 'Existing Department 1']
                ],
                [
                    'type' => $departmentEntityType,
                    'id' => '<toString(@department2->id)>',
                    'attributes' => ['title' => 'Existing Department 2']
                ]
            ]
        ];
        for ($i = 0; $i <= $chunkSize; $i++) {
            $departmentName = sprintf('New Department %d', $i + 1);
            $data['data'][] = [
                'type' => $departmentEntityType,
                'attributes' => ['title' => $departmentName]
            ];
            $expectedDepartments['data'][] = [
                'type' => $departmentEntityType,
                'id' => 'new'
            ];
        }
        for ($j = 0; $j <= $includedDataChunkSize * 2; $j++) {
            $employeeId = sprintf('new_employee%d', $j + 1);
            $employeeName = sprintf('New Employee %d', $j + 1);
            $departmentIndex = $j % $chunkSize;
            $data['data'][$departmentIndex]['relationships']['staff']['data'][] = [
                'type' => $employeeEntityType,
                'id' => $employeeId
            ];
            $data['included'][] = [
                'type' => $employeeEntityType,
                'id' => $employeeId,
                'attributes' => ['name' => $employeeName]
            ];
            $expectedDepartments['data'][$departmentIndex + 2]['relationships']['staff']['data'][] = [
                'type' => $employeeEntityType,
                'id' => 'new'
            ];
        }
        $this->processUpdateList(TestDepartment::class, $data);

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent($expectedDepartments, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntitiesWithValidationErrorsInIncludedData(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $operationId = $this->sendUpdateListRequest(TestDepartment::class, [
            'data' => [
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 1'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee1']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 2'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee2'],
                                ['type' => $employeeEntityType, 'id' => 'new_employee3']
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee1',
                    'attributes' => ['name' => 'New Employee 1']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee2',
                    'attributes' => ['name' => 'New Employee 2']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee3',
                    'attributes' => ['name' => null]
                ]
            ]
        ]);

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);
        $this->consumeAllMessages();

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/2/attributes/name']
            ],
            $operationId
        );

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $newDepartment1Id = (int)$responseContent['data'][2]['id'];
        $newEmployee1Id = $this->getEmployeeId('New Employee 1');
        $response = $this->cget(['entity' => $employeeEntityType], ['sort' => 'name', 'page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Existing Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee1Id,
                        'attributes' => ['name' => 'New Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment1Id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntitiesWithRequestDataErrorInIncludedData(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee2'],
                                    ['type' => $employeeEntityType, 'id' => 'new_employee3']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee2',
                        'attributes' => ['name' => 'New Employee 2']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee3',
                        'attributes' => []
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The \'attributes\' property should not be empty',
                'source' => ['pointer' => '/included/2/attributes']
            ],
            $operationId
        );

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $newDepartment1Id = (int)$responseContent['data'][2]['id'];
        $newEmployee1Id = $this->getEmployeeId('New Employee 1');
        $response = $this->cget(['entity' => $employeeEntityType], ['sort' => 'name', 'page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Existing Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee1Id,
                        'attributes' => ['name' => 'New Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment1Id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntitiesWithInvalidAndUnlinkedIncludedItems(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee3']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee4'],
                                    ['type' => $employeeEntityType, 'id' => 'new_employee5']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'attributes' => ['name' => 'New Employee 2']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee3',
                        'attributes' => ['name' => 'New Employee 3']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee4',
                        'attributes' => ['name' => 'New Employee 4']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee5',
                        'attributes' => ['name' => 'New Employee 5']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee6',
                        'attributes' => ['name' => 'New Employee 6']
                    ],
                    null,
                    0,
                    'invalid value',
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee5',
                        'attributes' => ['name' => 'New Employee 5 (duplicate)']
                    ]
                ]
            ],
            false
        );

        $expectedErrors = [
            [
                'id' => $operationId . '-0-1',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => "The 'id' property is required",
                'source' => ['pointer' => '/included/1']
            ],
            [
                'id' => $operationId . '-0-2',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The item should be an object',
                'source' => ['pointer' => '/included/6']
            ],
            [
                'id' => $operationId . '-0-3',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The item should be an object',
                'source' => ['pointer' => '/included/7']
            ],
            [
                'id' => $operationId . '-0-4',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The item should be an object',
                'source' => ['pointer' => '/included/8']
            ],
            [
                'id' => $operationId . '-0-5',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The item duplicates the item with the index 4',
                'source' => ['pointer' => '/included/9']
            ]
        ];
        $this->assertAsyncOperationErrors($expectedErrors, $operationId);

        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            true,
            [
                'readCount' => 0,
                'writeCount' => 0,
                'errorCount' => count($expectedErrors),
                'createCount' => 0,
                'updateCount' => 0
            ]
        );

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $response = $this->cget(['entity' => $employeeEntityType], ['sort' => 'name', 'page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Existing Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntitiesWithUnlinkedIncludedItems(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $operationId = $this->processUpdateList(
            TestDepartment::class,
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee3']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new_employee4'],
                                    ['type' => $employeeEntityType, 'id' => 'new_employee5']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee3',
                        'attributes' => ['name' => 'New Employee 3']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee4',
                        'attributes' => ['name' => 'New Employee 4']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee5',
                        'attributes' => ['name' => 'New Employee 5']
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => 'new_employee6',
                        'attributes' => ['name' => 'New Employee 6']
                    ]
                ]
            ],
            false
        );

        $expectedErrors = [
            [
                'id' => $operationId . '-0-1',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The entity should have a relationship with at least one primary entity'
                    . ' and this should be explicitly specified in the request',
                'source' => ['pointer' => '/included/0']
            ],
            [
                'id' => $operationId . '-0-2',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The entity should have a relationship with at least one primary entity'
                    . ' and this should be explicitly specified in the request',
                'source' => ['pointer' => '/included/4']
            ]
        ];
        $this->assertAsyncOperationErrors($expectedErrors, $operationId);

        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            true,
            [
                'readCount' => 2,
                'writeCount' => 2,
                'errorCount' => count($expectedErrors),
                'createCount' => 2,
                'updateCount' => 0
            ]
        );

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Existing Department 1']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => 'new',
                        'attributes' => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => $employeeEntityType, 'id' => 'new'],
                                    ['type' => $employeeEntityType, 'id' => 'new']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $newDepartment1Id = (int)$responseContent['data'][2]['id'];
        $newDepartment2Id = (int)$responseContent['data'][3]['id'];
        $newEmployee3Id = $this->getEmployeeId('New Employee 3');
        $newEmployee4Id = $this->getEmployeeId('New Employee 4');
        $newEmployee5Id = $this->getEmployeeId('New Employee 5');
        $response = $this->cget(['entity' => $employeeEntityType], ['sort' => 'name', 'page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Existing Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee3Id,
                        'attributes' => ['name' => 'New Employee 3'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment1Id]
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee4Id,
                        'attributes' => ['name' => 'New Employee 4'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
                    ],
                    [
                        'type' => $employeeEntityType,
                        'id' => (string)$newEmployee5Id,
                        'attributes' => ['name' => 'New Employee 5'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateEntitiesWithDuplicatedIncludeEntities(): void
    {
        $operationId = $this->processUpdateList(
            TestOwner::class,
            $this->getUpdateEntitiesWithDuplicatedIncludeEntitiesRequestData(),
            false
        );
        $this->assertUpdateEntitiesWithDuplicatedIncludeEntitiesResult($operationId);
    }

    public function testTryToUpdateEntitiesWithDuplicatedIncludeEntitiesWithoutMessageQueue(): void
    {
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            TestOwner::class,
            $this->getUpdateEntitiesWithDuplicatedIncludeEntitiesRequestData()
        );
        $this->assertUpdateEntitiesWithDuplicatedIncludeEntitiesResult($operationId);
    }

    public function testTryToUpdateEntitiesWithDuplicatedIncludeEntitiesWithoutMessageQueueAndWithSyncMode(): void
    {
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            TestOwner::class,
            $this->getUpdateEntitiesWithDuplicatedIncludeEntitiesRequestData(),
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

    private function getUpdateEntitiesWithDuplicatedIncludeEntitiesRequestData(): array
    {
        $owner2Id = $this->getReference('owner2')->id;
        $target1Id = $this->getReference('target1')->id;

        return [
            'data' => [
                [
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
    }

    private function assertUpdateEntitiesWithDuplicatedIncludeEntitiesResult(int $operationId): void
    {
        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-0-1',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The item duplicates the item with the index 0',
                'source' => ['pointer' => '/included/1']
            ],
            $operationId
        );
    }
}
