<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\ExtIdRestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\ExtIdRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ExtIdApiWithRegularExternalIdTest extends ExtIdRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/ext_id_entities.yml'
        ]);
    }

    private function getDepartment(string $externalId): TestDepartment
    {
        return $this->getEntityManager()
            ->getRepository(TestDepartment::class)
            ->findOneBy(['externalId' => $externalId]);
    }

    private function getEmployee(string $externalId): TestEmployee
    {
        return $this->getEntityManager()
            ->getRepository(TestEmployee::class)
            ->findOneBy(['externalId' => $externalId]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'testapidepartments']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapidepartments',
                        'id' => 'ext_department_1',
                        'attributes' => [
                            'title' => 'Department 1',
                            'dbId' => '@department_1->id'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => 'ext_employee_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => 'ext_department_2',
                        'attributes' => [
                            'title' => 'Department 2',
                            'dbId' => '@department_2->id'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => 'ext_employee_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithIncludedEntities(): void
    {
        $response = $this->cget(['entity' => 'testapidepartments'], ['include' => 'staff']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapidepartments',
                        'id' => 'ext_department_1',
                        'attributes' => [
                            'title' => 'Department 1',
                            'dbId' => '@department_1->id'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => 'ext_employee_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => 'ext_department_2',
                        'attributes' => [
                            'title' => 'Department 2',
                            'dbId' => '@department_2->id'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => 'ext_employee_2']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_1',
                        'attributes' => [
                            'name' => 'Employee 1',
                            'dbId' => '@employee_1->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => 'ext_department_1']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_2',
                        'attributes' => [
                            'name' => 'Employee 2',
                            'dbId' => '@employee_2->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => 'ext_department_2']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForAssociatedEntity(): void
    {
        $response = $this->cget(['entity' => 'testapiemployees']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_1',
                        'attributes' => [
                            'name' => 'Employee 1',
                            'dbId' => '@employee_1->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => 'ext_department_1']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_2',
                        'attributes' => [
                            'name' => 'Employee 2',
                            'dbId' => '@employee_2->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => 'ext_department_2']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_3',
                        'attributes' => [
                            'name' => 'Employee 3',
                            'dbId' => '@employee_3->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForAssociatedEntityWithIncludedEntities(): void
    {
        $response = $this->cget(['entity' => 'testapiemployees'], ['include' => 'department']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_1',
                        'attributes' => [
                            'name' => 'Employee 1',
                            'dbId' => '@employee_1->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => 'ext_department_1']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_2',
                        'attributes' => [
                            'name' => 'Employee 2',
                            'dbId' => '@employee_2->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => 'ext_department_2']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => 'ext_employee_3',
                        'attributes' => [
                            'name' => 'Employee 3',
                            'dbId' => '@employee_3->id'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => null
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapidepartments',
                        'id' => 'ext_department_1',
                        'attributes' => [
                            'title' => 'Department 1',
                            'dbId' => '@department_1->id'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => 'ext_employee_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => 'ext_department_2',
                        'attributes' => [
                            'title' => 'Department 2',
                            'dbId' => '@department_2->id'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => 'ext_employee_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWhenDbIdIsUsedInRelationshipsInsteadOfExternalId(): void
    {
        $data = [
            'data' => [
                'type' => 'testapidepartments',
                'id' => 'ext_new_department_1',
                'attributes' => [
                    'title' => 'New Department 1'
                ],
                'relationships' => [
                    'staff' => [
                        'data' => [
                            ['type' => 'testapiemployees', 'id' => '<toString(@employee_1->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapidepartments'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/data/relationships/staff/data']
            ],
            $response
        );
    }

    public function testTryToCreateWhenEntityWithSpecifiedExternalIdAlreadyExist(): void
    {
        $data = [
            'data' => [
                'type' => 'testapidepartments',
                'id' => 'ext_department_1',
                'attributes' => [
                    'title' => 'New Department 1',
                    'dbId' => 12345
                ],
                'relationships' => [
                    'staff' => [
                        'data' => [
                            ['type' => 'testapiemployees', 'id' => 'ext_employee_1']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapidepartments'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'conflict constraint',
                'detail' => 'The entity already exists.'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'testapidepartments',
                'id' => 'ext_new_department_1',
                'attributes' => [
                    'title' => 'New Department 1',
                    'dbId' => 12345
                ],
                'relationships' => [
                    'staff' => [
                        'data' => [
                            ['type' => 'testapiemployees', 'id' => 'ext_employee_1']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapidepartments'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['dbId'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response, 'dbId');
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(
            $expectedData['data']['attributes']['dbId'],
            $this->getDepartment('ext_new_department_1')->getId()
        );
    }

    public function testCreateWithIncludedEntities(): void
    {
        $data = [
            'data' => [
                'type' => 'testapidepartments',
                'id' => 'ext_new_department_1',
                'attributes' => [
                    'title' => 'New Department 1',
                    'dbId' => 12345
                ],
                'relationships' => [
                    'staff' => [
                        'data' => [
                            ['type' => 'testapiemployees', 'id' => 'ext_new_employee_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'testapiemployees',
                    'id' => 'ext_new_employee_1',
                    'attributes' => [
                        'name' => 'New Employee 1',
                        'dbId' => 23456
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapidepartments'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['dbId'] = 'new';
        $expectedData['included'][0]['attributes']['dbId'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response, 'dbId');
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(
            $expectedData['data']['attributes']['dbId'],
            $this->getDepartment('ext_new_department_1')->getId()
        );
        self::assertSame(
            $expectedData['included'][0]['attributes']['dbId'],
            $this->getEmployee('ext_new_employee_1')->getId()
        );
    }

    public function testTryToUpdateDbId(): void
    {
        $existingDbId = $this->getDepartment('ext_department_1')->getId();
        $data = [
            'data' => [
                'type' => 'testapidepartments',
                'id' => 'ext_department_1',
                'attributes' => [
                    'dbId' => 12345
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'testapidepartments', 'id' => 'ext_department_1'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['dbId'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response, 'dbId');
        $this->assertResponseContains($expectedData, $response);
        self::assertSame($expectedData['data']['attributes']['dbId'], $existingDbId);
        self::assertSame($this->getDepartment('ext_department_1')->getId(), $existingDbId);
    }
}
