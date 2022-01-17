<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class MaxResultsForAssociationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/max_results_for_association.yml']);
    }

    public function testAssociationDefaultMaxResultsLimit()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>']
        );

        $data = self::jsonToArray($response->getContent());
        self::assertCount(100, $data['data']['relationships']['staff']['data']);
    }

    public function testAssociationDefaultMaxResultsLimitWithIncludeFilter()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>'],
            ['include' => 'staff']
        );

        $data = self::jsonToArray($response->getContent());
        self::assertCount(100, $data['data']['relationships']['staff']['data']);
        self::assertCount(100, $data['included']);
    }

    public function testAssociationCustomMaxResultsLimit()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'fields' => [
                    'staff' => [
                        'max_results' => 10
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>']
        );

        $data = self::jsonToArray($response->getContent());
        self::assertCount(10, $data['data']['relationships']['staff']['data']);
    }

    public function testAssociationCustomMaxResultsLimitWithIncludeFilter()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'fields' => [
                    'staff' => [
                        'max_results' => 10
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>'],
            ['include' => 'staff']
        );

        $data = self::jsonToArray($response->getContent());
        self::assertCount(10, $data['data']['relationships']['staff']['data']);
        self::assertCount(10, $data['included']);
    }

    public function testAssociationUnlimitedMaxResults()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'fields' => [
                    'staff' => [
                        'max_results' => -1
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>']
        );

        $data = self::jsonToArray($response->getContent());
        self::assertCount(105, $data['data']['relationships']['staff']['data']);
    }

    public function testAssociationUnlimitedMaxResultsWithIncludeFilter()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'fields' => [
                    'staff' => [
                        'max_results' => -1
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>'],
            ['include' => 'staff']
        );

        $data = self::jsonToArray($response->getContent());
        self::assertCount(105, $data['data']['relationships']['staff']['data']);
        self::assertCount(105, $data['included']);
    }


    public function testAssociationCustomMaxResultsLimitAndOrderBy()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'fields' => [
                    'staff' => [
                        'max_results' => 5,
                        'order_by' => ['name' => 'DESC']
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $associationEntityType = $this->getEntityType(TestEmployee::class);
        $expectedResponse = [
            'data' => [
                'type' => $entityType,
                'id'   => '<toString(@department1->id)>'
            ]
        ];
        foreach ([99, 98, 97, 96, 95] as $i) {
            $expectedResponse['data']['relationships']['staff']['data'][] = [
                'type' => $associationEntityType,
                'id'   => sprintf('<toString(@employee%d->id)>', $i)
            ];
        }
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>']
        );
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(5, $data['data']['relationships']['staff']['data']);
    }

    public function testAssociationCustomMaxResultsLimitAndOrderByWithIncludeFilter()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'fields' => [
                    'staff' => [
                        'max_results' => 5,
                        'order_by' => ['name' => 'DESC']
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $associationEntityType = $this->getEntityType(TestEmployee::class);
        $expectedResponse = [
            'data' => [
                'type' => $entityType,
                'id'   => '<toString(@department1->id)>'
            ]
        ];
        foreach ([99, 98, 97, 96, 95] as $i) {
            $expectedResponse['data']['relationships']['staff']['data'][] = [
                'type' => $associationEntityType,
                'id'   => sprintf('<toString(@employee%d->id)>', $i)
            ];
            $expectedResponse['included'][] = [
                'type'       => $associationEntityType,
                'id'         => sprintf('<toString(@employee%d->id)>', $i),
                'attributes' => [
                    'name' => sprintf('<(@employee%d->name)>', $i)
                ]
            ];
        }
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@department1->id)>'],
            ['include' => 'staff']
        );
        $this->assertResponseContains($expectedResponse, $response);

        $data = self::jsonToArray($response->getContent());
        self::assertCount(5, $data['data']['relationships']['staff']['data']);
        self::assertCount(5, $data['included']);
    }
}
