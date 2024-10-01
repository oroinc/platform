<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSyncUpdateListTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class SyncUpdateListTest extends RestJsonApiSyncUpdateListTestCase
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSynchronousBatchApiRequestWithoutIncludedData(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            [
                'data' => [
                    [
                        'type'       => $departmentEntityType,
                        'id'         => 'new_department1',
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 2']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => '<toString(@department1->id)>',
                        'meta'       => ['update' => true],
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'attributes' => ['title' => 'New Department 3']
                    ]
                ]
            ],
            ['HTTP_X-Mode' => 'sync']
        );

        $newDepartment1Id = $this->getDepartmentId('New Department 1');
        $newDepartment2Id = $this->getDepartmentId('New Department 2');
        $newDepartment3Id = $this->getDepartmentId('New Department 3');
        $department1Id = $this->getReference('department1')->getId();

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $departmentEntityType,
                        'id'            => (string)$newDepartment1Id,
                        'meta'          => ['dataId' => 'new_department1'],
                        'attributes'    => ['title' => 'New Department 1'],
                        'relationships' => ['staff' => ['data' => []]]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'id'            => (string)$newDepartment2Id,
                        'attributes'    => ['title' => 'New Department 2'],
                        'relationships' => ['staff' => ['data' => []]]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'id'            => '<toString(@department1->id)>',
                        'meta'          => ['dataId' => (string)$department1Id],
                        'attributes'    => ['title' => 'Updated Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => '<toString(@employee1->id)>']]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'id'            => (string)$newDepartment3Id,
                        'attributes'    => ['title' => 'New Department 3'],
                        'relationships' => ['staff' => ['data' => []]]
                    ]
                ]
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $departmentEntityType,
                        'id'            => '<toString(@department1->id)>',
                        'attributes'    => ['title' => 'Updated Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => '<toString(@employee1->id)>']]
                            ]
                        ]
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => (string)$newDepartment1Id,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => (string)$newDepartment2Id,
                        'attributes' => ['title' => 'New Department 2']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => (string)$newDepartment3Id,
                        'attributes' => ['title' => 'New Department 3']
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSynchronousBatchApiRequestWithIncludedData(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            [
                'data'     => [
                    [
                        'type'          => $departmentEntityType,
                        'id'            => 'new_department1',
                        'attributes'    => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => 'new_employee1']]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'attributes'    => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => 'new_employee2']]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'id'            => '<toString(@department1->id)>',
                        'meta'          => ['update' => true],
                        'attributes'    => ['title' => 'Updated Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => '<toString(@employee1->id)>']]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'attributes'    => ['title' => 'New Department 3'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => 'new_employee3']]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee2',
                        'attributes' => ['name' => 'New Employee 2']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => '<toString(@employee1->id)>',
                        'meta'       => ['update' => true],
                        'attributes' => ['name' => 'Updated Employee 1']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee3',
                        'attributes' => ['name' => 'New Employee 3']
                    ]
                ]
            ],
            ['HTTP_X-Mode' => 'sync']
        );

        $newDepartment1Id = $this->getDepartmentId('New Department 1');
        $newDepartment2Id = $this->getDepartmentId('New Department 2');
        $newDepartment3Id = $this->getDepartmentId('New Department 3');
        $newEmployee1Id = $this->getEmployeeId('New Employee 1');
        $newEmployee2Id = $this->getEmployeeId('New Employee 2');
        $newEmployee3Id = $this->getEmployeeId('New Employee 3');
        $department1Id = $this->getReference('department1')->getId();
        $employee1Id = $this->getReference('employee1')->getId();

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'          => $departmentEntityType,
                        'id'            => (string)$newDepartment1Id,
                        'meta'          => ['dataId' => 'new_department1'],
                        'attributes'    => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => (string)$newEmployee1Id]]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'id'            => (string)$newDepartment2Id,
                        'attributes'    => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => (string)$newEmployee2Id]]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'id'            => '<toString(@department1->id)>',
                        'meta'          => ['dataId' => (string)$department1Id],
                        'attributes'    => ['title' => 'Updated Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => '<toString(@employee1->id)>']]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'id'            => (string)$newDepartment3Id,
                        'attributes'    => ['title' => 'New Department 3'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => (string)$newEmployee3Id]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => $employeeEntityType,
                        'id'            => (string)$newEmployee1Id,
                        'meta'          => ['includeId' => 'new_employee1'],
                        'attributes'    => ['name' => 'New Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment1Id]
                            ]
                        ]
                    ],
                    [
                        'type'          => $employeeEntityType,
                        'id'            => (string)$newEmployee2Id,
                        'meta'          => ['includeId' => 'new_employee2'],
                        'attributes'    => ['name' => 'New Employee 2'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment2Id]
                            ]
                        ]
                    ],
                    [
                        'type'          => $employeeEntityType,
                        'id'            => '<toString(@employee1->id)>',
                        'meta'          => ['includeId' => (string)$employee1Id],
                        'attributes'    => ['name' => 'Updated Employee 1'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => '<toString(@department1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => $employeeEntityType,
                        'id'            => (string)$newEmployee3Id,
                        'meta'          => ['includeId' => 'new_employee3'],
                        'attributes'    => ['name' => 'New Employee 3'],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => $departmentEntityType, 'id' => (string)$newDepartment3Id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );

        $response = $this->cget(['entity' => $departmentEntityType], ['page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $departmentEntityType,
                        'id'         => '<toString(@department1->id)>',
                        'attributes' => ['title' => 'Updated Department 1']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => '<toString(@department2->id)>',
                        'attributes' => ['title' => 'Existing Department 2']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => (string)$newDepartment1Id,
                        'attributes' => ['title' => 'New Department 1']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => (string)$newDepartment2Id,
                        'attributes' => ['title' => 'New Department 2']
                    ],
                    [
                        'type'       => $departmentEntityType,
                        'id'         => (string)$newDepartment3Id,
                        'attributes' => ['title' => 'New Department 3']
                    ]
                ]
            ],
            $response
        );

        $response = $this->cget(['entity' => $employeeEntityType], ['page[size]' => 10]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => '<toString(@employee1->id)>',
                        'attributes' => ['name' => 'Updated Employee 1']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => (string)$newEmployee1Id,
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => (string)$newEmployee2Id,
                        'attributes' => ['name' => 'New Employee 2']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => (string)$newEmployee3Id,
                        'attributes' => ['name' => 'New Employee 3']
                    ]
                ]
            ],
            $response
        );
    }

    public function testTrySynchronousBatchApiRequestWhenRequestDataEmpty(): void
    {
        $response = $this->cpatch(
            ['entity' => $this->getEntityType(TestDepartment::class)],
            [],
            ['HTTP_X-Mode' => 'sync'],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'request data constraint',
                    'detail' => 'The request data should not be empty.'
                ]
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );
    }

    public function testTrySynchronousBatchApiRequestWhenPrimaryRequestDataHaveErrors(): void
    {
        $initialEntityCounts = $this->getEntityCounts(
            [AsyncOperation::class, TestDepartment::class, TestEmployee::class]
        );

        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            [
                'data'     => [
                    [
                        'type'          => $departmentEntityType,
                        'attributes'    => ['title' => ''],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => 'new_employee1']]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'attributes'    => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => 'new_employee2']]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee2',
                        'attributes' => ['name' => 'New Employee 2']
                    ]
                ]
            ],
            ['HTTP_X-Mode' => 'sync'],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/0/attributes/title']
                ]
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );
        self::assertEquals(
            $initialEntityCounts,
            $this->getEntityCounts([AsyncOperation::class, TestDepartment::class, TestEmployee::class])
        );
    }

    public function testTrySynchronousBatchApiRequestWhenIncludedRequestDataHaveErrors(): void
    {
        $initialEntityCounts = $this->getEntityCounts(
            [AsyncOperation::class, TestDepartment::class, TestEmployee::class]
        );

        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            [
                'data'     => [
                    [
                        'type'          => $departmentEntityType,
                        'attributes'    => ['title' => 'New Department 1'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => 'new_employee1']]
                            ]
                        ]
                    ],
                    [
                        'type'          => $departmentEntityType,
                        'attributes'    => ['title' => 'New Department 2'],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => 'new_employee2']]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee1',
                        'attributes' => ['name' => '']
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee2',
                        'attributes' => ['name' => 'New Employee 2']
                    ]
                ]
            ],
            ['HTTP_X-Mode' => 'sync'],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/0/attributes/name']
                ]
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );
        self::assertEquals(
            $initialEntityCounts,
            $this->getEntityCounts([AsyncOperation::class, TestDepartment::class, TestEmployee::class])
        );
    }

    public function testTrySynchronousBatchApiRequestWhenPrimaryRequestDataLimitExceeded(): void
    {
        $initialEntityCounts = $this->getEntityCounts(
            [AsyncOperation::class, TestDepartment::class, TestEmployee::class]
        );

        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $data = [
            'data'     => [
                [
                    'type'          => $departmentEntityType,
                    'attributes'    => ['title' => 'New Department 1'],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $employeeEntityType, 'id' => 'new_employee1']]
                        ]
                    ]
                ],
                [
                    'type'          => $departmentEntityType,
                    'attributes'    => ['title' => 'New Department 2'],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $employeeEntityType, 'id' => 'new_employee2']]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $employeeEntityType,
                    'id'         => 'new_employee1',
                    'attributes' => ['name' => 'New Employee 1']
                ],
                [
                    'type'       => $employeeEntityType,
                    'id'         => 'new_employee2',
                    'attributes' => ['name' => 'New Employee 2']
                ]
            ]
        ];
        for ($i = 3; $i <= 101; $i++) {
            $data['data'][] = [
                'type'       => $departmentEntityType,
                'attributes' => ['title' => sprintf('New Department %d', $i)]
            ];
        }
        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            $data,
            ['HTTP_X-Mode' => 'sync'],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'request data constraint',
                    'detail' => 'The data limit for the synchronous operation exceeded.'
                        . ' The maximum number of records that can be processed by the synchronous operation is 100.'
                ]
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );
        self::assertEquals(
            $initialEntityCounts,
            $this->getEntityCounts([AsyncOperation::class, TestDepartment::class, TestEmployee::class])
        );
    }

    public function testTrySynchronousBatchApiRequestWhenIncludedRequestDataLimitExceeded(): void
    {
        $initialEntityCounts = $this->getEntityCounts(
            [AsyncOperation::class, TestDepartment::class, TestEmployee::class]
        );

        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $data = [
            'data'     => [
                [
                    'type'          => $departmentEntityType,
                    'attributes'    => ['title' => 'New Department 1'],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $employeeEntityType, 'id' => 'new_employee1']]
                        ]
                    ]
                ],
                [
                    'type'          => $departmentEntityType,
                    'attributes'    => ['title' => 'New Department 2'],
                    'relationships' => [
                        'staff' => [
                            'data' => [['type' => $employeeEntityType, 'id' => 'new_employee2']]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $employeeEntityType,
                    'id'         => 'new_employee1',
                    'attributes' => ['name' => 'New Employee 1']
                ],
                [
                    'type'       => $employeeEntityType,
                    'id'         => 'new_employee2',
                    'attributes' => ['name' => 'New Employee 2']
                ]
            ]
        ];
        for ($i = 3; $i <= 51; $i++) {
            $employeeId = sprintf('new_employee%d', $i);
            $data['data'][0]['relationships']['staff']['data'][] = [
                'type' => $employeeEntityType,
                'id'   => $employeeId
            ];
            $data['included'][] = [
                'type'       => $employeeEntityType,
                'id'         => $employeeId,
                'attributes' => ['name' => sprintf('New Employee %d', $i)]
            ];
        }
        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            $data,
            ['HTTP_X-Mode' => 'sync'],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'request data constraint',
                    'detail' => 'The data limit for the synchronous operation exceeded for the section "included".'
                        . ' The maximum number of included records that can be processed by the synchronous operation'
                        . ' is 50.'
                ]
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );
        self::assertEquals(
            $initialEntityCounts,
            $this->getEntityCounts([AsyncOperation::class, TestDepartment::class, TestEmployee::class])
        );
    }
}
