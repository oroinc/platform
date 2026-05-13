<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\ExtIdRestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class RegularApiWithRegularExternalIdTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/ext_id_entities.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'testapidepartments']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_1->id)>',
                        'attributes' => [
                            'title' => 'Department 1',
                            'externalId' => 'ext_department_1'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_1->id)>'],
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_2->id)>',
                        'attributes' => [
                            'title' => 'Department 2',
                            'externalId' => 'ext_department_2'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_3->id)>',
                        'attributes' => [
                            'title' => 'Department 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_3->id)>']
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
                        'id' => '<toString(@department_1->id)>',
                        'attributes' => [
                            'title' => 'Department 1',
                            'externalId' => 'ext_department_1'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_1->id)>'],
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_2->id)>',
                        'attributes' => [
                            'title' => 'Department 2',
                            'externalId' => 'ext_department_2'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_3->id)>',
                        'attributes' => [
                            'title' => 'Department 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_3->id)>']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_1->id)>',
                        'attributes' => [
                            'name' => 'Employee 1',
                            'externalId' => 'ext_employee_1'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_4->id)>',
                        'attributes' => [
                            'name' => 'Employee 4',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_2->id)>',
                        'attributes' => [
                            'name' => 'Employee 2',
                            'externalId' => 'ext_employee_2'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_2->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_3->id)>',
                        'attributes' => [
                            'name' => 'Employee 3',
                            'externalId' => 'ext_employee_3'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_3->id)>']
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
                        'id' => '<toString(@employee_1->id)>',
                        'attributes' => [
                            'name' => 'Employee 1',
                            'externalId' => 'ext_employee_1'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_2->id)>',
                        'attributes' => [
                            'name' => 'Employee 2',
                            'externalId' => 'ext_employee_2'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_2->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_3->id)>',
                        'attributes' => [
                            'name' => 'Employee 3',
                            'externalId' => 'ext_employee_3'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_3->id)>']
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
                        'id' => '<toString(@employee_1->id)>',
                        'attributes' => [
                            'name' => 'Employee 1',
                            'externalId' => 'ext_employee_1'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_2->id)>',
                        'attributes' => [
                            'name' => 'Employee 2',
                            'externalId' => 'ext_employee_2'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_2->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiemployees',
                        'id' => '<toString(@employee_3->id)>',
                        'attributes' => [
                            'name' => 'Employee 3',
                            'externalId' => 'ext_employee_3'
                        ],
                        'relationships' => [
                            'department' => [
                                'data' => ['type' => 'testapidepartments', 'id' => '<toString(@department_3->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_1->id)>',
                        'attributes' => [
                            'title' => 'Department 1',
                            'externalId' => 'ext_department_1'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_1->id)>'],
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_2->id)>',
                        'attributes' => [
                            'title' => 'Department 2',
                            'externalId' => 'ext_department_2'
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapidepartments',
                        'id' => '<toString(@department_3->id)>',
                        'attributes' => [
                            'title' => 'Department 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'staff' => [
                                'data' => [
                                    ['type' => 'testapiemployees', 'id' => '<toString(@employee_3->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'testapidepartments',
                'attributes' => [
                    'title' => 'New Department 1',
                    'externalId' => 'ext_new_department_1'
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
        $response = $this->post(['entity' => 'testapidepartments'], $data);
        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithIncludedEntities(): void
    {
        $data = [
            'data' => [
                'type' => 'testapidepartments',
                'attributes' => [
                    'title' => 'New Department 1',
                    'externalId' => 'ext_new_department_1'
                ],
                'relationships' => [
                    'staff' => [
                        'data' => [
                            ['type' => 'testapiemployees', 'id' => 'new_employee_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'testapiemployees',
                    'id' => 'new_employee_1',
                    'attributes' => [
                        'name' => 'New Employee 1',
                        'externalId' => 'ext_new_employee_1'
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapidepartments'], $data);
        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['staff']['data'][0]['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][0]['relationships']['department']['data'] = [
            'type' => 'testapidepartments',
            'id' => 'new'
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToUpdateExternalIdWhenEntityWithSpecifiedExternalIdAlreadyExist(): void
    {
        $response = $this->patch(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department_1->id)>'],
            [
                'data' => [
                    'type' => 'testapidepartments',
                    'id' => '<toString(@department_1->id)>',
                    'attributes' => [
                        'externalId' => 'ext_department_2'
                    ]
                ]
            ],
            [],
            false
        );
        // The expected error here is "The entity already exists." rather than
        // "Value for field 'External ID' must be unique.", because unique keys are not configured for this entity,
        // and the unique key validator is not enabled.
        // As a result, the unique constraint violation exception is raised at the database level.
        $this->assertResponseValidationError(
            [
                'title' => 'conflict constraint',
                'detail' => 'The entity already exists.'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }
}
