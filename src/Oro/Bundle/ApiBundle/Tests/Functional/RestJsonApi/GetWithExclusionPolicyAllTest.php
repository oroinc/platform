<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class GetWithExclusionPolicyAllTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/exclusion_policy_all.yml'
        ]);
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'title' => ['property_path' => 'name'],
                    'staff' => null
                ]
            ]
        );
        $this->appendEntityConfig(
            TestEmployee::class,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'           => null,
                    'name'         => null,
                    'organization' => null
                ]
            ]
        );
    }

    public function testGetWithoutFilters()
    {
        $response = $this->get(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapidepartments',
                    'id'            => '<toString(@department1->id)>',
                    'attributes'    => [
                        'title' => 'Department 1'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => 'testapiemployees', 'id' => '<toString(@employee1->id)>'],
                                ['type' => 'testapiemployees', 'id' => '<toString(@employee2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $data = self::jsonToArray($response->getContent());
        self::assertCount(1, $data['data']['attributes'], 'attributes');
        self::assertCount(1, $data['data']['relationships'], 'relationships');
    }

    public function testGetWithIncludeFilter()
    {
        $response = $this->get(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>'],
            ['include' => 'staff']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapidepartments',
                    'id'            => '<toString(@department1->id)>',
                    'attributes'    => [
                        'title' => 'Department 1'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => 'testapiemployees', 'id' => '<toString(@employee1->id)>'],
                                ['type' => 'testapiemployees', 'id' => '<toString(@employee2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'testapiemployees',
                        'id'            => '<toString(@employee1->id)>',
                        'attributes'    => [
                            'name' => 'Employee 1'
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testapiemployees',
                        'id'            => '<toString(@employee2->id)>',
                        'attributes'    => [
                            'name' => 'Employee 2'
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $data = self::jsonToArray($response->getContent());
        self::assertCount(1, $data['data']['attributes'], 'attributes');
        self::assertCount(1, $data['data']['relationships'], 'relationships');
        self::assertCount(2, $data['included'], 'included');
        self::assertCount(1, $data['included'][0]['attributes'], 'included.0.attributes');
        self::assertCount(1, $data['included'][0]['relationships'], 'included.0.relationships');
        self::assertCount(1, $data['included'][1]['attributes'], 'included.1.attributes');
        self::assertCount(1, $data['included'][1]['relationships'], 'included.1.relationships');
    }

    public function testGetWithIncludeAndFieldsFilters()
    {
        $response = $this->get(
            ['entity' => 'testapidepartments', 'id' => '<toString(@department1->id)>'],
            ['include' => 'staff', 'fields' => ['testapidepartments' => 'staff', 'testapiemployees' => 'name']]
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapidepartments',
                    'id'            => '<toString(@department1->id)>',
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => 'testapiemployees', 'id' => '<toString(@employee1->id)>'],
                                ['type' => 'testapiemployees', 'id' => '<toString(@employee2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapiemployees',
                        'id'         => '<toString(@employee1->id)>',
                        'attributes' => [
                            'name' => 'Employee 1'
                        ]
                    ],
                    [
                        'type'       => 'testapiemployees',
                        'id'         => '<toString(@employee2->id)>',
                        'attributes' => [
                            'name' => 'Employee 2'
                        ]
                    ]
                ]
            ],
            $response
        );
        $data = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $data['data'], 'attributes');
        self::assertCount(1, $data['data']['relationships'], 'relationships');
        self::assertCount(2, $data['included'], 'included');
        self::assertCount(1, $data['included'][0]['attributes'], 'included.0.attributes');
        self::assertArrayNotHasKey('relationships', $data['included'][0], 'included.0.relationships');
        self::assertCount(1, $data['included'][1]['attributes'], 'included.1.attributes');
        self::assertArrayNotHasKey('relationships', $data['included'][1], 'included.1.relationships');
    }
}
