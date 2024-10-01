<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tests\Functional\Api\DataFixtures\LoadEmailData;
use Oro\Bundle\EmailBundle\Tests\Functional\Api\DataFixtures\LoadEmailOriginData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group search
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailUserTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadEmailData::class, LoadEmailOriginData::class]);
        $indexer = self::getContainer()->get('oro_search.search.engine.indexer');
        $indexer->reindex(EmailUser::class);
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            EmailUser::class,
            [
                'VIEW'         => AccessLevel::GLOBAL_LEVEL,
                'VIEW_PRIVATE' => AccessLevel::LOCAL_LEVEL,
                'CREATE'       => AccessLevel::GLOBAL_LEVEL,
                'DELETE'       => AccessLevel::GLOBAL_LEVEL,
                'ASSIGN'       => AccessLevel::GLOBAL_LEVEL,
                'EDIT'         => AccessLevel::GLOBAL_LEVEL
            ]
        );
    }

    private function getEmailOrigin(string $userReference): EmailOrigin
    {
        /** @var User $user */
        $user = $this->getReference($userReference);

        return $this->getEntityManager()->getRepository(InternalEmailOrigin::class)->findOneBy([
            'organization' => $user->getOrganization(),
            'owner'        => $user,
            'internalName' => InternalEmailOrigin::BAP . '_API'
        ]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'emailusers']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_1->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => false,
                            'private'    => true,
                            'folders'    => [
                                ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_2->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => false,
                            'private'    => true,
                            'folders'    => [
                                ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_2->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_3->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => true,
                            'private'    => true,
                            'folders'    => [
                                ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_3->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_6->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => false,
                            'private'    => true,
                            'folders'    => [
                                ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_6->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_3_2->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => false,
                            'private'    => false,
                            'folders'    => [
                                ['type' => 'inbox', 'name' => 'Inbox', 'path' => 'Inbox']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user1->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByEmail(): void
    {
        $response = $this->cget(
            ['entity' => 'emailusers'],
            ['filter[email]' => '<toString(@email_3->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_3->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => true,
                            'private'    => true,
                            'folders'    => [
                                ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_3->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_3_2->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => false,
                            'private'    => false,
                            'folders'    => [
                                ['type' => 'inbox', 'name' => 'Inbox', 'path' => 'Inbox']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user1->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterBySearchText(): void
    {
        $response = $this->cget(
            ['entity' => 'emailusers'],
            ['filter[searchText]' => 'First']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'emailusers',
                        'id'            => '<toString(@emailUser_1->id)>',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'seen'       => false,
                            'private'    => true,
                            'folders'    => [
                                ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                            ]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'email'        => [
                                'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'emailusers',
                    'id'            => '<toString(@emailUser_1->id)>',
                    'attributes'    => [
                        'receivedAt' => '2022-05-01T15:00:00Z',
                        'seen'       => false,
                        'private'    => true,
                        'folders'    => [
                            ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                        ]
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                        ],
                        'owner'        => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'email'        => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetNotAccessible(): void
    {
        $response = $this->get(
            ['entity' => 'emailusers', 'id' => '<toString(@emailUser_4->id)>'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type'          => 'emailusers',
                'attributes'    => [
                    'receivedAt' => '2022-05-01T15:00:00Z',
                    'seen'       => true,
                    'private'    => false,
                    'folders'    => [
                        ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                    ]
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                    ],
                    'owner'        => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ],
                    'email'        => [
                        'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'emailusers'], $data);
        $emailUserId = (int)$this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['id'] = (string)$emailUserId;
        // the "private" field is read-only and its value is computed automatically
        $expectedData['data']['attributes']['private'] = true;
        $this->assertResponseContains($expectedData, $response);

        // the existing email folder should be reused
        $emailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        self::assertSame(
            $this->getEmailOrigin('user')->getFolder(FolderType::SENT)->getId(),
            $emailUser->getFolders()->first()->getId()
        );
    }

    public function testCreateForAnotherUser(): void
    {
        $data = [
            'data' => [
                'type'          => 'emailusers',
                'attributes'    => [
                    'receivedAt' => '2022-05-01T15:00:00Z',
                    'seen'       => true,
                    'folders'    => [
                        ['type' => 'inbox', 'name' => 'Inbox', 'path' => 'Inbox']
                    ]
                ],
                'relationships' => [
                    'organization' => [
                        'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                    ],
                    'owner'        => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user1->id)>']
                    ],
                    'email'        => [
                        'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'emailusers'], $data);
        $emailUserId = (int)$this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['id'] = (string)$emailUserId;
        $this->assertResponseContains($expectedData, $response);

        // the existing email folder should be reused
        $emailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        self::assertSame(
            $this->getEmailOrigin('user1')->getFolder(FolderType::INBOX)->getId(),
            $emailUser->getFolders()->first()->getId()
        );
    }

    public function testCreateWithRenameExistingFolder(): void
    {
        // guard - the folder to be renamed should exist
        self::assertNotNull($this->getEmailOrigin('user')->getFolder(FolderType::SENT));

        $data = [
            'data' => [
                'type'          => 'emailusers',
                'attributes'    => [
                    'receivedAt' => '2022-05-01T15:00:00Z',
                    'folders'    => [
                        ['type' => 'sent', 'name' => 'New Sent', 'path' => 'New Sent Path']
                    ]
                ],
                'relationships' => [
                    'email' => [
                        'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'emailusers'], $data);
        $emailUserId = (int)$this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['id'] = (string)$emailUserId;
        $this->assertResponseContains($expectedData, $response);

        // the existing email folder should be reused and renamed
        $emailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        $folder = $this->getEmailOrigin('user')->getFolder(FolderType::SENT);
        self::assertSame($folder->getId(), $emailUser->getFolders()->first()->getId());
        self::assertEquals('New Sent', $folder->getName());
        self::assertEquals('New Sent Path', $folder->getFullName());
    }

    public function testCreateWithNewFolder(): void
    {
        // guard - the folder to be renamed should exist
        self::assertNotNull($this->getEmailOrigin('user')->getFolder(FolderType::SENT));

        $data = [
            'data' => [
                'type'          => 'emailusers',
                'attributes'    => [
                    'receivedAt' => '2022-05-01T15:00:00Z',
                    'folders'    => [
                        ['type' => 'other', 'name' => 'Test', 'path' => 'My/Test']
                    ]
                ],
                'relationships' => [
                    'email' => [
                        'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'emailusers'], $data);
        $emailUserId = (int)$this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['id'] = (string)$emailUserId;
        $this->assertResponseContains($expectedData, $response);

        // the folder should be added to the API email origin
        $emailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        $folder = $this->getEmailOrigin('user')->getFolder(FolderType::OTHER, 'My/Test');
        self::assertSame($folder->getId(), $emailUser->getFolders()->first()->getId());
        self::assertEquals('Test', $folder->getName());
        self::assertEquals('My/Test', $folder->getFullName());
    }

    public function testTryToCreateWithInvalidFolderType(): void
    {
        $response = $this->post(
            ['entity' => 'emailusers'],
            [
                'data' => [
                    'type'          => 'emailusers',
                    'attributes'    => [
                        'receivedAt' => '2022-05-01T15:00:00Z',
                        'folders'    => [
                            ['type' => 'Sent', 'name' => 'Sent', 'path' => 'Sent']
                        ]
                    ],
                    'relationships' => [
                        'email' => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'choice constraint',
                'detail' => 'The value you selected is not a valid choice.',
                'source' => ['pointer' => '/data/attributes/folders/0/type']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutFolders(): void
    {
        $response = $this->post(
            ['entity' => 'emailusers'],
            [
                'data' => [
                    'type'          => 'emailusers',
                    'attributes'    => [
                        'receivedAt' => '2022-05-01T15:00:00Z'
                    ],
                    'relationships' => [
                        'email' => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'This collection should contain 1 element or more.',
                'source' => ['pointer' => '/data/attributes/folders']
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyFolders(): void
    {
        $response = $this->post(
            ['entity' => 'emailusers'],
            [
                'data' => [
                    'type'          => 'emailusers',
                    'attributes'    => [
                        'receivedAt' => '2022-05-01T15:00:00Z',
                        'folders'    => []
                    ],
                    'relationships' => [
                        'email' => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'This collection should contain 1 element or more.',
                'source' => ['pointer' => '/data/attributes/folders']
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>'],
            [
                'data' => [
                    'type'       => 'emailusers',
                    'id'         => '<toString(@emailUser_1->id)>',
                    'attributes' => [
                        'seen' => true
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'emailusers',
                    'id'            => '<toString(@emailUser_1->id)>',
                    'attributes'    => [
                        'receivedAt' => '2022-05-01T15:00:00Z',
                        'seen'       => true,
                        'private'    => true,
                        'folders'    => [
                            ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                        ]
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                        ],
                        'owner'        => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'email'        => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateReadOnlyFields(): void
    {
        $response = $this->patch(
            ['entity' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>'],
            [
                'data' => [
                    'type'          => 'emailusers',
                    'id'            => '<toString(@emailUser_1->id)>',
                    'attributes'    => [
                        'private' => false
                    ],
                    'relationships' => [
                        'email' => ['data' => ['type' => 'emails', 'id' => '<toString(@email_3->id)>']],
                        'owner' => ['data' => ['type' => 'users', 'id' => '<toString(@user1->id)>']]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'emailusers',
                    'id'            => '<toString(@emailUser_1->id)>',
                    'attributes'    => [
                        'receivedAt' => '2022-05-01T15:00:00Z',
                        'seen'       => false,
                        'private'    => true,
                        'folders'    => [
                            ['type' => 'sent', 'name' => 'Sent', 'path' => 'Sent']
                        ]
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                        ],
                        'owner'        => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'email'        => [
                            'data' => ['type' => 'emails', 'id' => '<toString(@email_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToRemoveAllFolders(): void
    {
        $response = $this->patch(
            ['entity' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>'],
            [
                'data' => [
                    'type'       => 'emailusers',
                    'id'         => '<toString(@emailUser_1->id)>',
                    'attributes' => [
                        'folders' => []
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'This collection should contain 1 element or more.',
                'source' => ['pointer' => '/data/attributes/folders']
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $emailUserId = $this->getReference('emailUser_3')->getId();
        $this->delete(
            ['entity' => 'emailusers', 'id' => (string)$emailUserId]
        );
        $deletedEmailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        self::assertTrue(null === $deletedEmailUser);
    }

    public function testDeleteList(): void
    {
        $emailUserId = $this->getReference('emailUser_3')->getId();
        $this->cdelete(
            ['entity' => 'emailusers'],
            ['filter[id]' => (string)$emailUserId]
        );
        $deletedEmailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        self::assertTrue(null === $deletedEmailUser);
    }

    public function testTryToDeleteLastEmailUser(): void
    {
        $emailUserId = $this->getReference('emailUser_1')->getId();
        $response = $this->delete(
            ['entity' => 'emailusers', 'id' => (string)$emailUserId],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: an email should have at least one email user.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
        $emailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        self::assertTrue(null !== $emailUser);
        self::assertCount(1, $emailUser->getEmail()->getEmailUsers());
    }

    public function testTryToDeleteListLastEmailUser(): void
    {
        $emailUserId = $this->getReference('emailUser_1')->getId();
        $response = $this->cdelete(
            ['entity' => 'emailusers'],
            ['filter[id]' => (string)$emailUserId],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: an email should have at least one email user.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
        $emailUser = $this->getEntityManager()->find(EmailUser::class, $emailUserId);
        self::assertTrue(null !== $emailUser);
        self::assertCount(1, $emailUser->getEmail()->getEmailUsers());
    }

    public function testTryToDeleteListForAllEmailUserForEmailEntity(): void
    {
        $emailId = $this->getReference('email_3')->getId();
        $emailUserId1 = $this->getReference('emailUser_3')->getId();
        $emailUserId2 = $this->getReference('emailUser_3_2')->getId();
        $response = $this->cdelete(
            ['entity' => 'emailusers'],
            ['filter[email]' => (string)$emailId],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: an email should have at least one email user.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
        $emailUser1 = $this->getEntityManager()->find(EmailUser::class, $emailUserId1);
        self::assertTrue(null !== $emailUser1);
        $emailUser2 = $this->getEntityManager()->find(EmailUser::class, $emailUserId2);
        self::assertTrue(null !== $emailUser2);
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        self::assertCount(2, $email->getEmailUsers());
    }

    public function testTryToGetSubresourceForEmail(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>', 'association' => 'email'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToGetRelationshipForEmail(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>', 'association' => 'email'],
            [],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
