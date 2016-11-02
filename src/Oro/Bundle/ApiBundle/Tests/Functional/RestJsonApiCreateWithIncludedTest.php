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
        $organization = $this->getOrganization();
        $businessUnit = $this->getBusinessUnit();

        $entityType = $this->getEntityType(User::class);

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
                            ['type' => $this->getEntityType(Group::class), 'id' => 'TEST_USER_GROUP_1']
                        ]
                    ],
                    'organization' => [
                        'data' => [
                            'type' => $this->getEntityType(Organization::class),
                            'id'   => (string)$organization->getId()
                        ]
                    ],
                    'owner'        => [
                        'data' => [
                            'type' => $this->getEntityType(BusinessUnit::class),
                            'id'   => (string)$businessUnit->getId()
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => $this->getEntityType(Group::class),
                    'id'            => 'TEST_USER_GROUP_1',
                    'attributes'    => [
                        'name' => 'Test Group 1'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => [
                                'type' => $this->getEntityType(Organization::class),
                                'id'   => (string)$organization->getId()
                            ]
                        ],
                        'owner'        => [
                            'data' => [
                                'type' => $this->getEntityType(BusinessUnit::class),
                                'id'   => (string)$businessUnit->getId()
                            ]
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
        self::assertEquals($this->getEntityType(Group::class), $result['included'][0]['type']);
        self::assertEquals($groupId, $result['included'][0]['id']);
        self::assertEquals('Test Group 1', $result['included'][0]['attributes']['name']);

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

        $data = [
            'data'     => [
                'type'       => $entityType,
                'id'         => $userId,
                'attributes' => [
                    'firstName' => 'Test First Name',
                ],
            ],
            'included' => [
                [
                    'type'       => $this->getEntityType(Group::class),
                    'id'         => $groupId,
                    'meta'       => [
                        '_update' => true
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
        self::assertEquals($this->getEntityType(Group::class), $result['included'][0]['type']);
        self::assertEquals($groupId, $result['included'][0]['id']);
        self::assertEquals('Test Group 1 (updated)', $result['included'][0]['attributes']['name']);

        // test that the group was updated in the database
        $this->getEntityManager()->clear();
        $group = $this->getEntityManager()->find(Group::class, (int)$groupId);
        self::assertNotNull($group);
        self::assertEquals('Test Group 1 (updated)', $group->getName());
    }
}
