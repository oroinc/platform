<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
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
            ],
            [
                'id' => $operationId . '-0-6',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The entity should have a relationship with at least one primary entity'
                    . ' and this should be explicitly specified in the request',
                'source' => ['pointer' => '/included/0']
            ],
            [
                'id' => $operationId . '-0-7',
                'status' => 400,
                'title' => 'request data constraint',
                'detail' => 'The entity should have a relationship with at least one primary entity'
                    . ' and this should be explicitly specified in the request',
                'source' => ['pointer' => '/included/5']
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
}
