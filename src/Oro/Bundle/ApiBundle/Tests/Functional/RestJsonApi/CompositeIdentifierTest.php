<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCompositeIdentifier as TestEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class CompositeIdentifierTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/composite_identifier.yml'
        ]);
    }

    /**
     * @param string $key1
     * @param int    $key2
     *
     * @return string
     */
    private function getEntityId($key1, $key2)
    {
        return http_build_query(
            ['renamedKey1' => $key1, 'key2' => $key2],
            '',
            ';'
        );
    }

    public function testGetList()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(['entity' => $entityType]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 1', 1),
                        'attributes'    => [
                            'name' => 'Item 1'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => null
                            ],
                            'children' => [
                                'data' => []
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 2', 2),
                        'attributes'    => [
                            'name' => 'Item 2'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => null
                            ],
                            'children' => [
                                'data' => []
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3', 3),
                        'attributes'    => [
                            'name' => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithTitles()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['meta' => 'title', 'fields[' . $entityType . ']' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1', 1),
                        'meta' => [
                            'title' => 'item 1 Item 1'
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 2', 2),
                        'meta' => [
                            'title' => 'item 2 Item 2'
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 3', 3),
                        'meta' => [
                            'title' => 'item 3 Item 3'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithTitlesAndIncludedDataRequested()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['meta' => 'title', 'fields[' . $entityType . ']' => 'id,parent', 'include' => 'parent']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1', 1),
                        'meta' => [
                            'title' => 'item 1 Item 1'
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 2', 2),
                        'meta' => [
                            'title' => 'item 2 Item 2'
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 3', 3),
                        'meta' => [
                            'title' => 'item 3 Item 3'
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1', 1),
                        'meta' => [
                            'title' => 'item 1 Item 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListSortById()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['sort' => '-id', 'fields[' . $entityType . ']' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 3', 3)
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 2', 2)
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1', 1)
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterById()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[id]' => $this->getEntityId('item 3', 3)]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3', 3),
                        'attributes'    => [
                            'name' => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterBySeveralIds()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[id]' => $this->getEntityId('item 1', 1) . ',' . $this->getEntityId('item 3', 3)]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 1', 1),
                        'attributes'    => [
                            'name' => 'Item 1'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => null
                            ],
                            'children' => [
                                'data' => []
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3', 3),
                        'attributes'    => [
                            'name' => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByField()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[name]' => 'Item+3']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3', 3),
                        'attributes'    => [
                            'name' => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3)]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('item 3', 3),
                    'attributes'    => [
                        'name' => 'Item 3'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                            ]
                        ]
                    ]
                ],
            ],
            $response
        );
    }

    public function testGetWithTitles()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3)],
            ['meta' => 'title']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => $this->getEntityId('item 3', 3),
                    'meta' => [
                        'title' => 'item 3 Item 3'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithTitlesAndIncludedDataRequested()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3)],
            ['meta' => 'title', 'fields[' . $entityType . ']' => 'id,parent', 'include' => 'parent']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type' => $entityType,
                    'id'   => $this->getEntityId('item 3', 3),
                    'meta' => [
                        'title' => 'item 3 Item 3'
                    ]
                ],
                'included' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1', 1),
                        'meta' => [
                            'title' => 'item 1 Item 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreate()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => $this->getEntityId('new item', 10),
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent'   => [
                        'data' => [
                            'type' => $entityType,
                            'id'   => $this->getEntityId('item 1', 1)
                        ]
                    ],
                    'children' => [
                        'data' => [
                            [
                                'type' => $entityType,
                                'id'   => $this->getEntityId('item 1', 1)
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('new item', 10),
                    'attributes'    => [
                        'name' => 'New Item'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                            ]
                        ]
                    ]
                ],
            ],
            $response
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $createdEntity */
        $createdEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key1' => 'new item', 'key2' => 10]);
        self::assertNotNull($createdEntity);

        self::assertSame('New Item', $createdEntity->name);
        self::assertSame($this->getReference('test_composite_id1')->key1, $createdEntity->getParent()->key1);
        self::assertSame($this->getReference('test_composite_id1')->key2, $createdEntity->getParent()->key2);
        self::assertCount(1, $createdEntity->getChildren());
        $childEntity = $createdEntity->getChildren()->first();
        self::assertSame($this->getReference('test_composite_id1')->key1, $childEntity->key1);
        self::assertSame($this->getReference('test_composite_id1')->key2, $childEntity->key2);
    }

    public function testUpdate()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => $this->getEntityId('item 1', 1),
                'attributes'    => [
                    'name' => 'Updated Name'
                ],
                'relationships' => [
                    'parent'   => [
                        'data' => [
                            'type' => $entityType,
                            'id'   => $this->getEntityId('item 2', 2)
                        ]
                    ],
                    'children' => [
                        'data' => [
                            [
                                'type' => $entityType,
                                'id'   => $this->getEntityId('item 2', 2)
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => $this->getEntityId('item 1', 1)], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('item 1', 1),
                    'attributes'    => [
                        'name' => 'Updated Name'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                            ]
                        ]
                    ]
                ],
            ],
            $response
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key1' => 'item 1', 'key2' => 1]);
        self::assertNotNull($updatedEntity);
        self::assertSame('Updated Name', $updatedEntity->name);
        self::assertSame($this->getReference('test_composite_id2')->key1, $updatedEntity->getParent()->key1);
        self::assertSame($this->getReference('test_composite_id2')->key2, $updatedEntity->getParent()->key2);
        self::assertCount(1, $updatedEntity->getChildren());
        $childEntity = $updatedEntity->getChildren()->first();
        self::assertSame($this->getReference('test_composite_id2')->key1, $childEntity->key1);
        self::assertSame($this->getReference('test_composite_id2')->key2, $childEntity->key2);
    }

    public function testDelete()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => $this->getEntityId('item 3', 3),
                'attributes' => [
                    'name' => 'Updated Name'
                ]
            ]
        ];

        $this->delete(['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3)], $data);

        $this->getEntityManager()->clear();
        $deletedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key1' => 'item 3', 'key2' => 3]);
        self::assertNull($deletedEntity);
    }

    public function testGetSubresourceForToOneAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'parent']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('item 1', 1),
                    'attributes'    => [
                        'name' => 'Item 1'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => null
                        ],
                        'children' => [
                            'data' => []
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToOneAssociationWithTitles()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'parent'],
            ['meta' => 'title']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => $this->getEntityId('item 1', 1),
                    'meta' => [
                        'title' => 'item 1 Item 1',
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToManyAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'children']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 2', 2),
                        'attributes'    => [
                            'name' => 'Item 2'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => null
                            ],
                            'children' => [
                                'data' => []
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToManyAssociationWithTitles()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'children'],
            ['meta' => 'title']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 2', 2),
                        'meta' => [
                            'title' => 'item 2 Item 2',
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForToOneAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'parent']
        );

        $this->assertResponseContains(
            [
                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
            ],
            $response
        );
    }

    public function testGetRelationshipForToManyAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'children']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipForToOneAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'parent'],
            [
                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key1' => 'item 3', 'key2' => 3]);
        self::assertNotNull($updatedEntity);
        self::assertSame($this->getReference('test_composite_id2')->key1, $updatedEntity->getParent()->key1);
        self::assertSame($this->getReference('test_composite_id2')->key2, $updatedEntity->getParent()->key2);
    }

    public function testUpdateRelationshipForToManyAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'children'],
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)],
                    ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)],
                ]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key1' => 'item 3', 'key2' => 3]);
        self::assertNotNull($updatedEntity);
        self::assertCount(2, $updatedEntity->getChildren());
        $ids = [];
        $child = $updatedEntity->getChildren()->get(0);
        $ids[$child->key1] = ['key1' => $child->key1, 'key2' => $child->key2];
        $child = $updatedEntity->getChildren()->get(1);
        $ids[$child->key1] = ['key1' => $child->key1, 'key2' => $child->key2];
        ksort($ids);
        $ids = array_values($ids);
        self::assertSame($this->getReference('test_composite_id1')->key1, $ids[0]['key1']);
        self::assertSame($this->getReference('test_composite_id1')->key2, $ids[0]['key2']);
        self::assertSame($this->getReference('test_composite_id2')->key1, $ids[1]['key1']);
        self::assertSame($this->getReference('test_composite_id2')->key2, $ids[1]['key2']);
    }

    public function testDeleteRelationshipForToManyAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->deleteRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'children'],
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 2', 2)]
                ]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key1' => 'item 3', 'key2' => 3]);
        self::assertNotNull($updatedEntity);
        self::assertCount(0, $updatedEntity->getChildren());
    }

    public function testAddRelationshipForToManyAssociation()
    {
        self::markTestSkipped('BAP-15595: Need to fix EntitySerializer to work with composite identifier');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->postRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3', 3), 'association' => 'children'],
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 1', 1)]
                ]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key1' => 'item 3', 'key2' => 3]);
        self::assertNotNull($updatedEntity);
        self::assertCount(2, $updatedEntity->getChildren());
        $ids = [];
        $child = $updatedEntity->getChildren()->get(0);
        $ids[$child->key1] = ['key1' => $child->key1, 'key2' => $child->key2];
        $child = $updatedEntity->getChildren()->get(1);
        $ids[$child->key1] = ['key1' => $child->key1, 'key2' => $child->key2];
        ksort($ids);
        $ids = array_values($ids);
        self::assertSame($this->getReference('test_composite_id1')->key1, $ids[0]['key1']);
        self::assertSame($this->getReference('test_composite_id1')->key2, $ids[0]['key2']);
        self::assertSame($this->getReference('test_composite_id2')->key1, $ids[1]['key1']);
        self::assertSame($this->getReference('test_composite_id2')->key2, $ids[1]['key2']);
    }
}
