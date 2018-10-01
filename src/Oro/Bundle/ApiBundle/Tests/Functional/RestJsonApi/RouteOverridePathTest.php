<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestCurrentDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class RouteOverridePathTest extends RestJsonApiTestCase
{
    private function loadCurrentDepartment()
    {
        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/route_override_path.yml'
        ]);
    }

    public function testGetWhenCurrentDepartmentDoesNotExist()
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path')
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of an entity must be set in the context.'
            ],
            $response
        );
    }

    public function testGetWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $entityType = $this->getEntityType(TestCurrentDepartment::class);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path')
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '<toString(@current_department->id)>',
                    'attributes' => [
                        'title' => 'Current Department'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithTitleMetaPropertyWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $entityType = $this->getEntityType(TestCurrentDepartment::class);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path'),
            ['meta' => 'title']
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '<toString(@current_department->id)>',
                    'meta'       => [
                        'title' => 'Current Department'
                    ],
                    'attributes' => [
                        'title' => 'Current Department'
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateWhenCurrentDepartmentDoesNotExist()
    {
        $entityType = $this->getEntityType(TestCurrentDepartment::class);

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_tests_override_path'),
            $this->getRequestData([
                'data' => [
                    'type'       => $entityType,
                    'id'         => '111',
                    'attributes' => [
                        'title' => 'test'
                    ]
                ]
            ])
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of an entity must be set in the context.'
            ],
            $response
        );
    }

    public function testUpdateWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $entityType = $this->getEntityType(TestCurrentDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_tests_override_path'),
            $this->getRequestData([
                'data' => [
                    'type'          => $entityType,
                    'id'            => '<toString(@current_department->id)>',
                    'attributes'    => [
                        'title' => 'Changed Department'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => '<toString(@employee3->id)>']
                            ]
                        ]
                    ]
                ]
            ])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => '<toString(@current_department->id)>',
                    'attributes'    => [
                        'title' => 'Changed Department'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => '<toString(@employee3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $this->getEntityManager()->clear();
        $updatedDepartment = $this->getEntityManager()
            ->find(TestDepartment::class, $this->getReference('current_department')->getId());
        self::assertEquals('Changed Department', $updatedDepartment->getName());
        self::assertCount(1, $updatedDepartment->getStaff(), 'Unexpected number of employees');
        self::assertEquals(
            $this->getReference('employee3')->getId(),
            $updatedDepartment->getStaff()->first()->getId()
        );
    }

    public function testUpdateWhenCurrentDepartmentDoesNotExistButIdInRequestNotEqualToCurrentId()
    {
        $this->loadCurrentDepartment();

        $entityType = $this->getEntityType(TestCurrentDepartment::class);

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_tests_override_path'),
            $this->getRequestData([
                'data' => [
                    'type'       => $entityType,
                    'id'         => '1111111',
                    'attributes' => [
                        'title' => 'test'
                    ]
                ]
            ])
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'conflict constraint',
                'detail' => 'The \'id\' property of the primary data object'
                    . ' should match \'id\' parameter of the query sting',
                'source' => ['pointer' => '/data/id']
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testGetSubresourceWhenCurrentDepartmentDoesNotExist()
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path_subresource', ['association' => 'staff'])
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of the parent entity must be set in the context.'
            ],
            $response
        );
    }

    public function testGetSubresourceWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path_subresource', ['association' => 'staff'])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => '<toString(@employee1->id)>',
                        'attributes' => [
                            'name' => 'Employee 1'
                        ]
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => '<toString(@employee2->id)>',
                        'attributes' => [
                            'name' => 'Employee 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceWithTitleMetaPropertyWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path_subresource', ['association' => 'staff']),
            ['meta' => 'title']
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => '<toString(@employee1->id)>',
                        'meta'       => [
                            'title' => 'Employee 1'
                        ],
                        'attributes' => [
                            'name' => 'Employee 1'
                        ]
                    ],
                    [
                        'type'       => $employeeEntityType,
                        'id'         => '<toString(@employee2->id)>',
                        'meta'       => [
                            'title' => 'Employee 2'
                        ],
                        'attributes' => [
                            'name' => 'Employee 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipWhenCurrentDepartmentDoesNotExist()
    {
        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff'])
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of the parent entity must be set in the context.'
            ],
            $response
        );
    }

    public function testGetRelationshipWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff'])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee1->id)>'],
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee2->id)>']
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipWhenCurrentDepartmentDoesNotExist()
    {
        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff'])
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of the parent entity must be set in the context.'
            ],
            $response
        );
    }

    public function testUpdateRelationshipWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff']),
            $this->getRequestData([
                'data' => [
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee3->id)>']
                ]
            ])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        $this->getEntityManager()->clear();
        $updatedDepartment = $this->getEntityManager()
            ->find(TestDepartment::class, $this->getReference('current_department')->getId());
        self::assertCount(1, $updatedDepartment->getStaff(), 'Unexpected number of employees');
        self::assertEquals(
            $this->getReference('employee3')->getId(),
            $updatedDepartment->getStaff()->first()->getId()
        );
    }

    public function testAddRelationshipWhenCurrentDepartmentDoesNotExist()
    {
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff'])
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of the parent entity must be set in the context.'
            ],
            $response
        );
    }

    public function testAddRelationshipWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff']),
            $this->getRequestData([
                'data' => [
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee3->id)>']
                ]
            ])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        $this->getEntityManager()->clear();
        $updatedDepartment = $this->getEntityManager()
            ->find(TestDepartment::class, $this->getReference('current_department')->getId());
        self::assertCount(3, $updatedDepartment->getStaff(), 'Unexpected number of employees');
        $expectedStaffIds = [
            $this->getReference('employee1')->getId(),
            $this->getReference('employee2')->getId(),
            $this->getReference('employee3')->getId()
        ];
        $actualStaffIds = [];
        foreach ($updatedDepartment->getStaff() as $employee) {
            $actualStaffIds[] = $employee->getId();
        }
        sort($expectedStaffIds);
        sort($actualStaffIds);
        self::assertEquals($expectedStaffIds, $actualStaffIds);
    }

    public function testDeleteRelationshipWhenCurrentDepartmentDoesNotExist()
    {
        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff'])
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'The identifier of the parent entity must be set in the context.'
            ],
            $response
        );
    }

    public function testDeleteRelationshipWhenCurrentDepartmentExists()
    {
        $this->loadCurrentDepartment();

        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $response = $this->request(
            'DELETE',
            $this->getUrl('oro_rest_tests_override_path_relationship', ['association' => 'staff']),
            $this->getRequestData([
                'data' => [
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee2->id)>']
                ]
            ])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        $this->getEntityManager()->clear();
        $updatedDepartment = $this->getEntityManager()
            ->find(TestDepartment::class, $this->getReference('current_department')->getId());
        self::assertCount(1, $updatedDepartment->getStaff(), 'Unexpected number of employees');
        self::assertEquals(
            $this->getReference('employee1')->getId(),
            $updatedDepartment->getStaff()->first()->getId()
        );
    }
}
