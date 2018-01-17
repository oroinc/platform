<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class RestJsonApiGetWithFiltersTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/filters.yml'
        ]);
    }

    public function testTotalCount()
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(
            ['entity' => $entityType],
            ['page' => ['size' => 2]],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@TestEmployee1->id)>',
                        'attributes' => [
                            'name' => 'TestEmployee1'
                        ]
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@TestEmployee2->id)>',
                        'attributes' => [
                            'name' => 'TestEmployee2'
                        ]
                    ]
                ]
            ],
            $response
        );

        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testTotalCountWithFilterByField()
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(
            ['entity' => $entityType],
            ['filter' => ['name' => 'TestEmployee1']],
            ['HTTP_X-Include' => 'totalCount']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@TestEmployee1->id)>',
                        'attributes' => [
                            'name' => 'TestEmployee1'
                        ]
                    ]
                ]
            ],
            $response
        );

        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'));
    }

    public function testFilterByField()
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(
            ['entity' => $entityType],
            ['filter' => ['name' => 'TestEmployee1']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@TestEmployee1->id)>',
                        'attributes' => [
                            'name' => 'TestEmployee1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByStringFieldWithComma()
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(
            ['entity' => $entityType],
            ['filter' => ['name' => 'TestEmployee, with comma in name']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@TestEmployee3->id)>',
                        'attributes' => [
                            'name' => 'TestEmployee, with comma in name'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByFieldOfAssociation()
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(
            ['entity' => $entityType],
            ['filter' => ['department.name' => 'TestDepartment2']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@TestEmployee2->id)>',
                        'attributes' => [
                            'name' => 'TestEmployee2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByFieldOfSecondLevelAssociation()
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(
            ['entity' => $entityType],
            ['filter' => ['department.owner.name' => '@business_unit->name']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '<toString(@TestEmployee1->id)>',
                        'attributes' => [
                            'name' => 'TestEmployee1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByWrongFieldName()
    {
        $response = $this->cget(
            ['entity' => $this->getEntityType(TestEmployee::class)],
            ['filter' => ['wrongFieldName' => 'value']],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 400);
        $this->assertResponseContains(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'filter constraint',
                        'detail' => 'The filter is not supported.',
                        'source' => [
                            'parameter' => 'filter[wrongFieldName]'
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
