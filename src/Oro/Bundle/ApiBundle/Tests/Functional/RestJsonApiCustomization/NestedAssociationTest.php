<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadNestedAssociationData;
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

    public function testGet()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_entity->id)>',
                    'attributes'    => [
                        'name' => [
                            'firstName' => null,
                            'lastName'  => 'test'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id'   => '<toString(@test_related_entity1->id)>'
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
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['meta' => 'title']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_entity->id)>',
                    'meta'          => [
                        'title' => 'test ' . TestRelatedEntity::class
                    ],
                    'attributes'    => [
                        'name' => [
                            'firstName' => null,
                            'lastName'  => 'test'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id'   => '<toString(@test_related_entity1->id)>'
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
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['include' => 'relatedEntity']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_entity->id)>',
                    'attributes'    => [
                        'name' => [
                            'firstName' => null,
                            'lastName'  => 'test'
                        ]
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id'   => '<toString(@test_related_entity1->id)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $relatedEntityType,
                        'id'         => '<toString(@test_related_entity1->id)>',
                        'attributes' => [
                            'withDefaultValueString' => 'default'
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
        $relatedEntity1 = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'relationships' => [
                    'relatedEntity' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => (string)$relatedEntity1->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id'   => (string)$relatedEntity1->id
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

    public function testCreateWithoutNestedAssociationData()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

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
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entity->getId(),
                'relationships' => [
                    'relatedEntity' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => (string)$relatedEntity2->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => (string)$relatedEntity2->id
            ],
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entity->getId(),
                'relationships' => [
                    'relatedEntity' => [
                        'data' => null
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
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
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getSubresource([
            'entity'      => $entityType,
            'id'          => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => $relatedEntityType,
                    'id'         => (string)$relatedEntity->id,
                    'attributes' => [
                        'withDefaultValueString' => 'default'
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
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getSubresource([
            'entity'      => $entityType,
            'id'          => (string)$entity->getId(),
            'association' => 'relatedEntity',
            'meta'        => 'title'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id'   => (string)$relatedEntity->id,
                    'meta' => [
                        'title' => 'default default_NotBlank default_NotNull'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationship()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getRelationship([
            'entity'      => $entityType,
            'id'          => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id'   => (string)$relatedEntity->id
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
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            ['data' => ['type' => $relatedEntityType, 'id' => (string)$relatedEntity2->id]]
        );

        // test that the data was updated
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateRelationshipToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
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
