<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class TagTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroTagBundle/Tests/Functional/Api/DataFixtures/tag.yml'
        ]);
    }

    protected function postFixtureLoad()
    {
        $em = $this->getEntityManager();
        $em->persist(new Tagging($this->getReference('tag1'), $this->getReference('user')));
        $em->persist(new Tagging($this->getReference('tag1'), $this->getReference('activity1')));
        $em->persist(new Tagging($this->getReference('tag1'), $this->getReference('activity2')));
        $em->persist(new Tagging($this->getReference('tag2'), $this->getReference('activity1')));
        $em->persist(new Tagging($this->getReference('tag2'), $this->getReference('activity3')));
        $em->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'tags']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'tags',
                        'id'            => '<toString(@tag1->id)>',
                        'attributes'    => [
                            'name'      => 'tag1',
                            'createdAt' => '@tag1->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt' => '@tag1->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'taxonomy'     => [
                                'data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'entities'     => [
                                'data' => [
                                    ['type' => 'users', 'id' => '<toString(@user->id)>'],
                                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                                    ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'tags',
                        'id'            => '<toString(@tag2->id)>',
                        'attributes'    => [
                            'name'      => 'tag2',
                            'createdAt' => '@tag2->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt' => '@tag2->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'taxonomy'     => [
                                'data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'entities'     => [
                                'data' => [
                                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                                    ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'tags',
                        'id'            => '<toString(@tag3->id)>',
                        'attributes'    => [
                            'name'      => 'tag3',
                            'createdAt' => '@tag3->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt' => '@tag3->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'taxonomy'     => [
                                'data' => null
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'entities'     => [
                                'data' => []
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
        $response = $this->get(
            ['entity' => 'tags', 'id' => '<toString(@tag1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'tags',
                    'id'            => '<toString(@tag1->id)>',
                    'attributes'    => [
                        'name'      => 'tag1',
                        'createdAt' => '@tag1->created->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt' => '@tag1->updated->format("Y-m-d\TH:i:s\Z")'
                    ],
                    'relationships' => [
                        'taxonomy'     => [
                            'data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
                        ],
                        'owner'        => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                        ],
                        'entities'     => [
                            'data' => [
                                ['type' => 'users', 'id' => '<toString(@user->id)>'],
                                ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                                ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithIncludeEntities(): void
    {
        $response = $this->get(
            ['entity' => 'tags', 'id' => '<toString(@tag1->id)>'],
            ['include' => 'entities']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'tags',
                    'id'            => '<toString(@tag1->id)>',
                    'attributes'    => [
                        'name'      => 'tag1',
                        'createdAt' => '@tag1->created->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt' => '@tag1->updated->format("Y-m-d\TH:i:s\Z")'
                    ],
                    'relationships' => [
                        'taxonomy'     => [
                            'data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
                        ],
                        'owner'        => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                        ],
                        'entities'     => [
                            'data' => [
                                ['type' => 'users', 'id' => '<toString(@user->id)>'],
                                ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                                ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'users',
                        'id'            => '<toString(@user->id)>',
                        'attributes'    => [
                            'email' => 'admin@example.com'
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'       => 'testactivities',
                        'id'         => '<toString(@activity1->id)>',
                        'attributes' => [
                            'message' => 'Activity 1'
                        ]
                    ],
                    [
                        'type'       => 'testactivities',
                        'id'         => '<toString(@activity2->id)>',
                        'attributes' => [
                            'message' => 'Activity 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $tagId = $this->getReference('tag1')->getId();

        $this->delete(
            ['entity' => 'tags', 'id' => (string)$tagId]
        );

        $tag = $this->getEntityManager()->find(Tag::class, $tagId);
        self::assertTrue(null === $tag);
    }

    public function testDeleteList(): void
    {
        $tagId = $this->getReference('tag1')->getId();

        $this->cdelete(
            ['entity' => 'tags'],
            ['filter[id]' => (string)$tagId]
        );

        $tag = $this->getEntityManager()->find(Tag::class, $tagId);
        self::assertTrue(null === $tag);
    }

    public function testCreate(): void
    {
        $userId = $this->getReference('user')->getId();
        $organizationId = $this->getReference('organization')->getId();

        $data = [
            'data' => [
                'type'       => 'tags',
                'attributes' => [
                    'name' => 'new_tag'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'tags'],
            $data
        );

        $expectedData = $data;
        $expectedData['data']['relationships']['taxonomy']['data'] = null;
        $expectedData['data']['relationships']['owner']['data'] = [
            'type' => 'users',
            'id'   => (string)$userId
        ];
        $expectedData['data']['relationships']['organization']['data'] = [
            'type' => 'organizations',
            'id'   => (string)$organizationId
        ];
        $this->assertResponseContains($expectedData, $response);

        $tag = $this->getEntityManager()->find(Tag::class, $this->getResourceId($response));
        self::assertNotNull($tag);
        self::assertEquals('new_tag', $tag->getName());
        self::assertNull($tag->getTaxonomy());
        self::assertEquals($userId, $tag->getOwner()->getId());
        self::assertEquals($organizationId, $tag->getOrganization()->getId());
    }

    public function testCreateForEntities(): void
    {
        $entities = [
            'data' => [
                ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>'],
                ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
            ]
        ];
        $data = [
            'data' => [
                'type'          => 'tags',
                'attributes'    => [
                    'name' => 'new_tag'
                ],
                'relationships' => [
                    'entities' => $entities
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'tags'],
            $data
        );

        $tagId = (int)$this->getResourceId($response);
        $data['data']['id'] = (string)$tagId;
        $this->assertResponseContains($data, $response);

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            $entities,
            $this->getRelationship(['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'])
        );
    }

    public function testUpdate(): void
    {
        $tagId = $this->getReference('tag1')->getId();
        $taxonomyId = $this->getReference('taxonomy3')->getId();

        $data = [
            'data' => [
                'type'          => 'tags',
                'id'            => (string)$tagId,
                'attributes'    => [
                    'name' => 'updated_tag'
                ],
                'relationships' => [
                    'taxonomy' => [
                        'data' => ['type' => 'taxonomies', 'id' => (string)$taxonomyId]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'tags', 'id' => (string)$tagId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $tag = $this->getEntityManager()->find(Tag::class, $this->getResourceId($response));
        self::assertNotNull($tag);
        self::assertEquals('updated_tag', $tag->getName());
        self::assertEquals($taxonomyId, $tag->getTaxonomy()->getId());
    }

    public function testUpdateForEntities(): void
    {
        $tagId = $this->getReference('tag1')->getId();

        $entities = [
            'data' => [
                ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>'],
                ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
            ]
        ];
        $data = [
            'data' => [
                'type'          => 'tags',
                'id'            => (string)$tagId,
                'relationships' => [
                    'entities' => $entities
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'tags', 'id' => (string)$tagId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            $entities,
            $this->getRelationship(['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'])
        );
    }

    public function testTryToCreateWithoutName(): void
    {
        $response = $this->post(
            ['entity' => 'tags'],
            ['data' => ['type' => 'tags']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testTryToSetNameToNull(): void
    {
        $tagId = $this->getReference('tag1')->getId();

        $response = $this->patch(
            ['entity' => 'tags', 'id' => (string)$tagId],
            [
                'data' => [
                    'type'       => 'tags',
                    'id'         => (string)$tagId,
                    'attributes' => [
                        'name' => null
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testGetSubresourceForTaxonomy(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'tags', 'id' => '<toString(@tag1->id)>', 'association' => 'taxonomy']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'taxonomies',
                    'id'         => '<toString(@taxonomy1->id)>',
                    'attributes' => [
                        'name' => '<toString(@taxonomy1->name)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForTaxonomy(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'tags', 'id' => '<toString(@tag1->id)>', 'association' => 'taxonomy']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']],
            $response
        );
    }

    public function testUpdateRelationshipForTaxonomy(): void
    {
        $tagId = $this->getReference('tag1')->getId();
        $taxonomyId = $this->getReference('taxonomy3')->getId();

        $this->patchRelationship(
            ['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'taxonomy'],
            ['data' => ['type' => 'taxonomies', 'id' => (string)$taxonomyId]]
        );

        $tag = $this->getEntityManager()->find(Tag::class, $tagId);
        self::assertSame($taxonomyId, $tag->getTaxonomy()->getId());
    }

    public function testUpdateRelationshipForTaxonomySetNull(): void
    {
        $tagId = $this->getReference('tag1')->getId();

        $this->patchRelationship(
            ['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'taxonomy'],
            ['data' => null]
        );

        $tag = $this->getEntityManager()->find(Tag::class, $tagId);
        self::assertNull($tag->getTaxonomy());
    }

    public function testGetSubresourceForEntities(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'tags', 'id' => '<toString(@tag1->id)>', 'association' => 'entities']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'users',
                        'id'            => '<toString(@user->id)>',
                        'attributes'    => [
                            'email' => 'admin@example.com'
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'       => 'testactivities',
                        'id'         => '<toString(@activity1->id)>',
                        'attributes' => [
                            'message' => 'Activity 1'
                        ]
                    ],
                    [
                        'type'       => 'testactivities',
                        'id'         => '<toString(@activity2->id)>',
                        'attributes' => [
                            'message' => 'Activity 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForEntities(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'tags', 'id' => '<toString(@tag1->id)>', 'association' => 'entities']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user->id)>'],
                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                    ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>']
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipForEntities(): void
    {
        $tagId = $this->getReference('tag1')->getId();
        $data = [
            'data' => [
                ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>'],
                ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
            ]
        ];
        $this->patchRelationship(
            ['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'],
            $data
        );

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            $data,
            $this->getRelationship(['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'])
        );
    }

    public function testAddRelationshipForEntities(): void
    {
        $tagId = $this->getReference('tag1')->getId();
        $this->postRelationship(
            ['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'],
            [
                'data' => [
                    ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
                ]
            ]
        );

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user->id)>'],
                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                    ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>'],
                    ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
                ]
            ],
            $this->getRelationship(['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'])
        );
    }

    public function testDeleteRelationshipForEntities(): void
    {
        $tagId = $this->getReference('tag1')->getId();
        $this->deleteRelationship(
            ['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'],
            [
                'data' => [
                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>']
                ]
            ]
        );

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user->id)>'],
                    ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>']
                ]
            ],
            $this->getRelationship(['entity' => 'tags', 'id' => (string)$tagId, 'association' => 'entities'])
        );
    }

    public function testTaggableEntityGetForTags(): void
    {
        $response = $this->get(
            ['entity' => 'testactivities', 'id' => '<toString(@activity1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testactivities',
                    'id'            => '<toString(@activity1->id)>',
                    'relationships' => [
                        'tags' => [
                            'data' => [
                                ['type' => 'tags', 'id' => '<toString(@tag1->id)>'],
                                ['type' => 'tags', 'id' => '<toString(@tag2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testTaggableEntityGetListForIncludeTags(): void
    {
        $response = $this->cget(
            ['entity' => 'testactivities'],
            ['include' => 'tags']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'          => 'testactivities',
                        'id'            => '<toString(@activity1->id)>',
                        'relationships' => [
                            'tags' => [
                                'data' => [
                                    ['type' => 'tags', 'id' => '<toString(@tag1->id)>'],
                                    ['type' => 'tags', 'id' => '<toString(@tag2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testactivities',
                        'id'            => '<toString(@activity2->id)>',
                        'relationships' => [
                            'tags' => [
                                'data' => [
                                    ['type' => 'tags', 'id' => '<toString(@tag1->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testactivities',
                        'id'            => '<toString(@activity3->id)>',
                        'relationships' => [
                            'tags' => [
                                'data' => [
                                    ['type' => 'tags', 'id' => '<toString(@tag2->id)>']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'tags',
                        'id'         => '<toString(@tag1->id)>',
                        'attributes' => [
                            'name' => 'tag1'
                        ]
                    ],
                    [
                        'type'       => 'tags',
                        'id'         => '<toString(@tag2->id)>',
                        'attributes' => [
                            'name' => 'tag2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTaggableEntityCreateForTags(): void
    {
        $tags = [
            'data' => [
                ['type' => 'tags', 'id' => '<toString(@tag2->id)>'],
                ['type' => 'tags', 'id' => '<toString(@tag3->id)>']
            ]
        ];
        $data = [
            'data' => [
                'type'          => 'testactivities',
                'attributes'    => [
                    'message' => 'New Activity'
                ],
                'relationships' => [
                    'tags' => $tags
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testactivities'],
            $data
        );

        $entityId = (int)$this->getResourceId($response);
        $data['data']['id'] = (string)$entityId;
        $this->assertResponseContains($data, $response);

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            $tags,
            $this->getRelationship(['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'])
        );
    }

    public function testTaggableEntityCreateWithoutTags(): void
    {
        $data = [
            'data' => [
                'type'       => 'testactivities',
                'attributes' => [
                    'message' => 'New Activity'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'testactivities'],
            $data
        );

        $entityId = (int)$this->getResourceId($response);
        $data['data']['id'] = (string)$entityId;
        $this->assertResponseContains($data, $response);

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            ['data' => []],
            $this->getRelationship(['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'])
        );
    }

    public function testTaggableEntityUpdateForTags(): void
    {
        $entityId = $this->getReference('activity1')->getId();
        $tags = [
            'data' => [
                ['type' => 'tags', 'id' => '<toString(@tag2->id)>'],
                ['type' => 'tags', 'id' => '<toString(@tag3->id)>']
            ]
        ];
        $data = [
            'data' => [
                'type'          => 'testactivities',
                'id'            => (string)$entityId,
                'relationships' => [
                    'tags' => $tags
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testactivities', 'id' => (string)$entityId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            $tags,
            $this->getRelationship(['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'])
        );
    }

    public function testTaggableEntityUpdateWithoutTags(): void
    {
        $entityId = $this->getReference('activity1')->getId();
        $data = [
            'data' => [
                'type'       => 'testactivities',
                'id'         => (string)$entityId,
                'attributes' => [
                    'message' => 'Updated Activity'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'testactivities', 'id' => (string)$entityId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'tags', 'id' => '<toString(@tag1->id)>'],
                    ['type' => 'tags', 'id' => '<toString(@tag2->id)>']
                ]
            ],
            $this->getRelationship(['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'])
        );
    }

    public function testTaggableEntityGetSubresourceForTags(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'testactivities', 'id' => '<toString(@activity1->id)>', 'association' => 'tags']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'tags',
                        'id'            => '<toString(@tag1->id)>',
                        'attributes'    => [
                            'name'      => 'tag1',
                            'createdAt' => '@tag1->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt' => '@tag1->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'taxonomy'     => [
                                'data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'entities'     => [
                                'data' => [
                                    ['type' => 'users', 'id' => '<toString(@user->id)>'],
                                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                                    ['type' => 'testactivities', 'id' => '<toString(@activity2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'tags',
                        'id'            => '<toString(@tag2->id)>',
                        'attributes'    => [
                            'name'      => 'tag2',
                            'createdAt' => '@tag2->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt' => '@tag2->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'taxonomy'     => [
                                'data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'entities'     => [
                                'data' => [
                                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                                    ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTaggableEntityGetSubresourceForTagsWithFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'testactivities', 'id' => '<toString(@activity1->id)>', 'association' => 'tags'],
            ['filter[name]' => 'tag2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'tags',
                        'id'            => '<toString(@tag2->id)>',
                        'attributes'    => [
                            'name'      => 'tag2',
                            'createdAt' => '@tag2->created->format("Y-m-d\TH:i:s\Z")',
                            'updatedAt' => '@tag2->updated->format("Y-m-d\TH:i:s\Z")'
                        ],
                        'relationships' => [
                            'taxonomy'     => [
                                'data' => ['type' => 'taxonomies', 'id' => '<toString(@taxonomy1->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'entities'     => [
                                'data' => [
                                    ['type' => 'testactivities', 'id' => '<toString(@activity1->id)>'],
                                    ['type' => 'testactivities', 'id' => '<toString(@activity3->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTaggableEntityGetRelationshipForTags(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'testactivities', 'id' => '<toString(@activity1->id)>', 'association' => 'tags']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'tags', 'id' => '<toString(@tag1->id)>'],
                    ['type' => 'tags', 'id' => '<toString(@tag2->id)>']
                ]
            ],
            $response
        );
    }

    public function testTaggableEntityUpdateRelationshipForTags(): void
    {
        $entityId = $this->getReference('activity1')->getId();
        $data = [
            'data' => [
                ['type' => 'tags', 'id' => '<toString(@tag2->id)>'],
                ['type' => 'tags', 'id' => '<toString(@tag3->id)>']
            ]
        ];
        $this->patchRelationship(
            ['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'],
            $data
        );

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            $data,
            $this->getRelationship(['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'])
        );
    }

    public function testTaggableEntityAddRelationshipForTags(): void
    {
        $entityId = $this->getReference('activity1')->getId();
        $this->postRelationship(
            ['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'],
            [
                'data' => [
                    ['type' => 'tags', 'id' => '<toString(@tag3->id)>']
                ]
            ]
        );

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'tags', 'id' => '<toString(@tag1->id)>'],
                    ['type' => 'tags', 'id' => '<toString(@tag2->id)>'],
                    ['type' => 'tags', 'id' => '<toString(@tag3->id)>']
                ]
            ],
            $this->getRelationship(['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'])
        );
    }

    public function testTaggableEntityDeleteRelationshipForTags(): void
    {
        $entityId = $this->getReference('activity1')->getId();
        $this->deleteRelationship(
            ['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'],
            [
                'data' => [
                    ['type' => 'tags', 'id' => '<toString(@tag1->id)>']
                ]
            ]
        );

        $this->getEntityManager()->clear();
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'tags', 'id' => '<toString(@tag2->id)>']
                ]
            ],
            $this->getRelationship(['entity' => 'testactivities', 'id' => (string)$entityId, 'association' => 'tags'])
        );
    }
}
