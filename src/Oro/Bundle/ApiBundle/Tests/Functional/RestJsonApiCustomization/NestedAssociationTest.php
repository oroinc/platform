<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadNestedAssociationData;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier as TestRelatedEntityWithCustomId;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull as TestRelatedEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEntityForNestedObjects as TestEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NestedAssociationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadNestedAssociationData::class]);
    }

    public function testGetList()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(['entity' => $entityType]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => [
                                'firstName' => null,
                                'lastName' => 'Entity 1'
                            ]
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $this->getEntityType(TestRelatedEntity::class),
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => [
                                'firstName' => null,
                                'lastName' => 'Entity 2'
                            ]
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $this->getEntityType(TestRelatedEntityWithCustomId::class),
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
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
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => [
                            'firstName' => null,
                            'lastName' => 'Entity 1'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => [
                            'firstName' => null,
                            'lastName' => 'Entity 2'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetWithTitleMetaProperty()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['meta' => 'title']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'meta' => [
                        'title' => 'Entity 1 ' . TestRelatedEntity::class
                    ],
                    'attributes' => [
                        'name' => [
                            'firstName' => null,
                            'lastName' => 'Entity 1'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetWithTitleMetaPropertyForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['meta' => 'title']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'meta' => [
                        'title' => 'Entity 2 ' . TestRelatedEntityWithCustomId::class
                    ],
                    'attributes' => [
                        'name' => [
                            'firstName' => null,
                            'lastName' => 'Entity 2'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetWithIncludeFilter()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['include' => 'relatedEntity']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => [
                            'firstName' => null,
                            'lastName' => 'Entity 1'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_1->id)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $relatedEntityType,
                        'id' => '<toString(@test_related_entity_1->id)>',
                        'attributes' => [
                            'withNotBlank' => 'Related Entity 1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
        foreach ($responseContent['included'] as $key => $item) {
            self::assertArrayNotHasKey('meta', $item, sprintf('included[%s]', $key));
        }
    }

    public function testGetWithIncludeFilterForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['include' => 'relatedEntity']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => [
                            'firstName' => null,
                            'lastName' => 'Entity 2'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $relatedEntityType,
                        'id' => '<toString(@test_related_entity_with_custom_id_1->key)>',
                        'attributes' => [
                            'name' => 'Related Entity 1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
        foreach ($responseContent['included'] as $key => $item) {
            self::assertArrayNotHasKey('meta', $item, sprintf('included[%s]', $key));
        }
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity1 */
        $relatedEntity1 = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->id
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->id
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$this->getResourceId($response));
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity1->id, $entity->getRelatedId());
    }

    public function testCreateForRelatedEntityWithCustomId()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity1 */
        $relatedEntity1 = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->key
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->key
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$this->getResourceId($response));
        self::assertEquals(TestRelatedEntityWithCustomId::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity1->autoincrementKey, $entity->getRelatedId());
    }

    public function testCreateWithoutNestedAssociationData()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$responseContent['data']['id']);
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testCreateWithoutNestedAssociationDataForRelatedEntityWithCustomId()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$responseContent['data']['id']);
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testUpdate()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity2->id
                            ]
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id' => (string)$relatedEntity2->id
            ],
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_with_custom_id_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity2->key
                            ]
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id' => (string)$relatedEntity2->key
            ],
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntityWithCustomId::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->autoincrementKey, $entity->getRelatedId());
    }

    public function testUpdateToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testUpdateToNullForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testGetSubresource()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->id,
                    'attributes' => [
                        'withNotBlank' => 'Related Entity 1'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
    }

    public function testGetSubresourceForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->key,
                    'attributes' => [
                        'name' => 'Related Entity 1'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
    }

    public function testGetSubresourceWithTitle()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity',
            'meta' => 'title'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->id,
                    'meta' => [
                        'title' => 'default default_NotBlank default_NotNull Related Entity 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceWithTitleForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity',
            'meta' => 'title'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->key,
                    'meta' => [
                        'title' => 'key1 Related Entity 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationship()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getRelationship([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->id
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('attributes', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testGetRelationshipForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->getRelationship([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->key
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('attributes', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testUpdateRelationship()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity2->id
                ]
            ]
        );

        // test that the data was updated
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateRelationshipForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_with_custom_id_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity2->key
                ]
            ]
        );

        // test that the data was updated
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntityWithCustomId::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->autoincrementKey, $entity->getRelatedId());
    }

    public function testUpdateRelationshipToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            ['data' => null]
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testUpdateRelationshipToNullForRelatedEntityWithCustomId()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            ['data' => null]
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }
}
