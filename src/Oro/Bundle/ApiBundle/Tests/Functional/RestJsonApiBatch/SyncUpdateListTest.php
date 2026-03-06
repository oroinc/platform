<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestUniqueKeyIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSyncUpdateListTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class SyncUpdateListTest extends RestJsonApiSyncUpdateListTestCase
{
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
    public function testRequestWithoutIncludedData(): void
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
    public function testRequestWithIncludedData(): void
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

    public function testTryRequestDataEmpty(): void
    {
        $response = $this->cpatch(
            ['entity' => $this->getEntityType(TestDepartment::class)],
            [],
            ['HTTP_X-Mode' => 'sync'],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'request data constraint',
                'detail' => 'The request data should not be empty.'
            ],
            $response
        );
        self::assertFalse(
            $response->headers->has('Content-Location'),
            'The "Content-Location" header should not be returned.'
        );
    }

    public function testTryPrimaryRequestDataHaveErrors(): void
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

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/title']
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

    public function testTryUpdatePrimaryRequestDataHaveErrors(): void
    {
        $initialEntityCounts = $this->getEntityCounts(
            [AsyncOperation::class, TestDepartment::class, TestEmployee::class]
        );

        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            [
                'data' => [
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department1->id)>',
                        'meta' => ['update' => true],
                        'attributes' => ['title' => ''],
                        'relationships' => [
                            'staff' => [
                                'data' => [['type' => $employeeEntityType, 'id' => '<toString(@employee1->id)>']]
                            ]
                        ]
                    ],
                    [
                        'type' => $departmentEntityType,
                        'id' => '<toString(@department2->id)>',
                        'meta' => ['update' => true],
                        'attributes' => ['title' => 'Updated Department 2']
                    ]
                ],
                'included' => [
                    [
                        'type' => $employeeEntityType,
                        'id' => '<toString(@employee1->id)>',
                        'meta' => ['update' => true],
                        'attributes' => ['name' => 'Updated Employee 1']
                    ]
                ]
            ],
            ['HTTP_X-Mode' => 'sync'],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/0/attributes/title']
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

    public function testTryIncludedRequestDataHaveErrors(): void
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

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/0/attributes/name']
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

    public function testTryPrimaryRequestDataLimitExceeded(): void
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

        $this->assertResponseValidationError(
            [
                'title' => 'request data constraint',
                'detail' => 'The data limit for the synchronous operation exceeded.'
                    . ' The maximum number of records that can be processed by the synchronous operation is 100.'
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

    public function testTryIncludedRequestDataLimitExceeded(): void
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

        $this->assertResponseValidationError(
            [
                'title' => 'request data constraint',
                'detail' => 'The data limit for the synchronous operation exceeded for the section "included".'
                    . ' The maximum number of included records that can be processed by the synchronous operation'
                    . ' is 50.'
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

    public function testCreateWithIntersectedRelationships(): void
    {
        $this->markTestSkipped('Due to BAP-23318');
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $data = $this->getCreateWithIntersectedRelationshipsRequestData();

        $response = $this->cpatch(
            ['entity' => $departmentEntityType],
            $data,
            ['HTTP_X-Mode' => 'sync']
        );

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
                ]
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $newDepartment1Id = (int)$responseContent['data'][0]['id'];
        $newDepartment2Id = (int)$responseContent['data'][1]['id'];
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
    }

    private function getCreateWithIntersectedRelationshipsRequestData(): array
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

    public function testUpdateWithIntersectedRelationships(): void
    {
        $this->markTestSkipped('Due to BAP-23318');
        $data = $this->getUpdateWithIntersectedRelationshipsRequestData();

        $response = $this->cpatch(
            ['entity' => $this->getEntityType(TestUniqueKeyIdentifier::class)],
            $data,
            ['HTTP_X-Mode' => 'sync']
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
        $this->markTestSkipped('Due to BAP-23318');
        $data = $this->getUpdateWithIntersectedRelationshipsRequestData();
        $data['data'][0]['attributes']['name'] = null;

        $response = $this->cpatch(
            ['entity' => $this->getEntityType(TestUniqueKeyIdentifier::class)],
            $data,
            ['HTTP_X-Mode' => 'sync'],
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
            'Item 2',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item2')->id)->name
        );
        self::assertEquals(
            'Item 2.1',
            $em->find(TestUniqueKeyIdentifier::class, $this->getReference('item21')->id)->name
        );
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

    public function testTryToUpdateEntitiesWithDuplicatedIncludeEntities(): void
    {
        $owner2Id = $this->getReference('owner2')->id;
        $target1Id = $this->getReference('target1')->id;

        $data = [
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

        $response = $this->cpatch(
            ['entity' => 'testapiowners'],
            $data,
            ['HTTP_X-Mode' => 'sync'],
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
