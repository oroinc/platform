<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class RestJsonApiCreateWithIncludedTest extends RestJsonApiTestCase
{
    /**
     * @return Organization
     */
    protected function getOrganization()
    {
        return $this->getEntityManager()
            ->getRepository(Organization::class)
            ->getFirst();
    }

    /**
     * @return BusinessUnit
     */
    protected function getBusinessUnit()
    {
        return $this->getEntityManager()
            ->getRepository(BusinessUnit::class)
            ->getFirst();
    }

    /**
     * @return array [$userId, $groupId]
     */
    public function testCreateIncludedEntity()
    {
        $org = $this->getOrganization();
        $bu = $this->getBusinessUnit();

        $entityType = $this->getEntityType(User::class);
        $orgEntityType = $this->getEntityType(Organization::class);
        $buEntityType = $this->getEntityType(BusinessUnit::class);
        $groupEntityType = $this->getEntityType(Group::class);

        $data = [
            'data'     => [
                'type'          => $entityType,
                'attributes'    => [
                    'username'  => 'test_user_1',
                    'password'  => 'TestUser#12345',
                    'firstName' => 'Test First Name',
                    'lastName'  => 'Test Last Name',
                    'email'     => 'test_user_1@example.com',
                ],
                'relationships' => [
                    'groups'       => [
                        'data' => [
                            ['type' => $groupEntityType, 'id' => 'TEST_USER_GROUP_1']
                        ]
                    ],
                    'organization' => [
                        'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                    ],
                    'owner'        => [
                        'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => $groupEntityType,
                    'id'            => 'TEST_USER_GROUP_1',
                    'attributes'    => [
                        'name' => 'Test Group 1'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                        ],
                        'owner'        => [
                            'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');

        $result = self::jsonToArray($response->getContent());

        $userId = $result['data']['id'];
        self::assertEquals('test_user_1', $result['data']['attributes']['username']);
        self::assertCount(1, $result['data']['relationships']['groups']['data']);
        self::assertCount(1, $result['included']);
        $groupId = $result['data']['relationships']['groups']['data'][0]['id'];
        self::assertEquals($groupEntityType, $result['included'][0]['type']);
        self::assertEquals($groupId, $result['included'][0]['id']);
        self::assertEquals('Test Group 1', $result['included'][0]['attributes']['name']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame('TEST_USER_GROUP_1', $result['included'][0]['meta']['includeId']);

        // test that both the user and the group was created in the database
        $this->getEntityManager()->clear();
        $group = $this->getEntityManager()->find(Group::class, (int)$groupId);
        self::assertNotNull($group);
        self::assertEquals('Test Group 1', $group->getName());
        $user = $this->getEntityManager()->find(User::class, (int)$userId);
        self::assertCount(1, $user->getGroups());
        self::assertSame((int)$groupId, $user->getGroups()->first()->getId());

        return [$userId, $groupId];
    }

    /**
     * @depends testCreateIncludedEntity
     *
     * @param array $ids [$userId, $groupId]
     */
    public function testUpdateIncludedEntity($ids)
    {
        list($userId, $groupId) = $ids;

        $entityType = $this->getEntityType(User::class);
        $groupEntityType = $this->getEntityType(Group::class);

        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => $userId,
                'attributes'    => [
                    'firstName' => 'Test First Name',
                ],
                'relationships' => [
                    'groups' => [
                        'data' => [
                            ['type' => $groupEntityType, 'id' => $groupId]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $groupEntityType,
                    'id'         => $groupId,
                    'meta'       => [
                        'update' => true
                    ],
                    'attributes' => [
                        'name' => 'Test Group 1 (updated)'
                    ],
                ],
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => $userId]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');

        $result = self::jsonToArray($response->getContent());

        self::assertCount(1, $result['data']['relationships']['groups']['data']);
        self::assertCount(1, $result['included']);
        $groupId = $result['data']['relationships']['groups']['data'][0]['id'];
        self::assertEquals($groupEntityType, $result['included'][0]['type']);
        self::assertEquals($groupId, $result['included'][0]['id']);
        self::assertEquals('Test Group 1 (updated)', $result['included'][0]['attributes']['name']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame($groupId, $result['included'][0]['meta']['includeId']);

        // test that the group was updated in the database
        $this->getEntityManager()->clear();
        $group = $this->getEntityManager()->find(Group::class, (int)$groupId);
        self::assertNotNull($group);
        self::assertEquals('Test Group 1 (updated)', $group->getName());
    }

    public function testCreateNotRelatedIncludedEntity()
    {
        $entityType = $this->getEntityType(User::class);
        $groupEntityType = $this->getEntityType(Group::class);

        $data = [
            'data'     => [
                'type' => $entityType,
            ],
            'included' => [
                [
                    'type' => $groupEntityType,
                    'id'   => 'TEST_USER_GROUP_1',
                ]
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 400);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');

        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'request data constraint',
                        'detail' => 'The entity should have a relationship with the primary entity',
                        'source' => [
                            'pointer' => '/included/0'
                        ]
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testCreateIncludedEntityWithNestedDependency()
    {
        $org = $this->getOrganization();
        $bu = $this->getBusinessUnit();

        $entityType = $this->getEntityType(User::class);
        $orgEntityType = $this->getEntityType(Organization::class);
        $buEntityType = $this->getEntityType(BusinessUnit::class);

        $data = [
            'data'     => [
                'type'          => $entityType,
                'attributes'    => [
                    'username'  => 'test_user_2',
                    'password'  => 'TestUser#12345',
                    'firstName' => 'Test First Name',
                    'lastName'  => 'Test Last Name',
                    'email'     => 'test_user_2@example.com',
                ],
                'relationships' => [
                    'organization'  => [
                        'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                    ],
                    'owner'         => [
                        'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                    ],
                    'businessUnits' => [
                        'data' => [
                            ['type' => $buEntityType, 'id' => 'BU2']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => $buEntityType,
                    'id'            => 'BU1',
                    'attributes'    => [
                        'name' => 'Business Unit 1'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                        ],
                        'owner'        => [
                            'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                        ]
                    ]
                ],
                [
                    'type'          => $buEntityType,
                    'id'            => 'BU2',
                    'attributes'    => [
                        'name' => 'Business Unit 2'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                        ],
                        'owner'        => [
                            'data' => ['type' => $buEntityType, 'id' => 'BU1']
                        ]
                    ]
                ],
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');

        $result = self::jsonToArray($response->getContent());

        self::assertEquals('test_user_2', $result['data']['attributes']['username']);
        self::assertCount(1, $result['data']['relationships']['businessUnits']['data']);
        self::assertCount(2, $result['included']);
        self::assertEquals($buEntityType, $result['included'][0]['type']);
        self::assertEquals('Business Unit 1', $result['included'][0]['attributes']['name']);
        self::assertEquals($buEntityType, $result['included'][1]['type']);
        self::assertEquals('Business Unit 2', $result['included'][1]['attributes']['name']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame('BU1', $result['included'][0]['meta']['includeId']);
        self::assertNotEmpty($result['included'][1]['meta']);
        self::assertSame('BU2', $result['included'][1]['meta']['includeId']);
    }

    public function testCreateIncludedEntityWithInversedDependency()
    {
        $org = $this->getOrganization();
        $bu = $this->getBusinessUnit();

        $entityType = $this->getEntityType(User::class);
        $orgEntityType = $this->getEntityType(Organization::class);
        $buEntityType = $this->getEntityType(BusinessUnit::class);

        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => 'PRIMARY_USER_OBJECT',
                'attributes'    => [
                    'username'  => 'test_user_3',
                    'password'  => 'TestUser#12345',
                    'firstName' => 'Test First Name',
                    'lastName'  => 'Test Last Name',
                    'email'     => 'test_user_3@example.com',
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                    ],
                    'owner'        => [
                        'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => $buEntityType,
                    'id'            => 'BU1',
                    'attributes'    => [
                        'name' => 'Business Unit 1'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => $orgEntityType, 'id' => (string)$org->getId()]
                        ],
                        'owner'        => [
                            'data' => ['type' => $buEntityType, 'id' => (string)$bu->getId()]
                        ],
                        'users'        => [
                            'data' => [
                                ['type' => $entityType, 'id' => 'PRIMARY_USER_OBJECT']
                            ]
                        ]
                    ]
                ],
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, 'application/vnd.api+json');

        $result = self::jsonToArray($response->getContent());

        $userId = $result['data']['id'];
        self::assertEquals('test_user_3', $result['data']['attributes']['username']);
        self::assertCount(1, $result['included']);
        self::assertEquals($buEntityType, $result['included'][0]['type']);
        self::assertEquals('Business Unit 1', $result['included'][0]['attributes']['name']);
        self::assertNotEmpty($result['included'][0]['meta']);
        self::assertSame('BU1', $result['included'][0]['meta']['includeId']);
        self::assertCount(1, $result['included'][0]['relationships']['users']['data']);
        self::assertSame($entityType, $result['included'][0]['relationships']['users']['data'][0]['type']);
        self::assertSame($userId, $result['included'][0]['relationships']['users']['data'][0]['id']);
    }
}
