<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCollection;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCollectionItem;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractCollectionTestCase extends RestJsonApiTestCase
{
    abstract protected function getCollectionEntityClass(): string;

    abstract protected function getCollectionItemEntityClass(): string;

    abstract protected function isManyToMany(): bool;

    abstract protected function isOrphanRemoval(): bool;

    abstract protected function getAssociationName(): string;

    /**
     * @param TestCollection|TestCollectionItem $entity
     *
     * @return Collection|TestCollectionItem[]|TestCollection[]
     */
    abstract protected function getItems($entity): Collection;

    public function testGetWithCollection()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => (string)$entityId,
                    'attributes'    => [
                        'name' => 'Entity 1'
                    ],
                    'relationships' => [
                        $this->getAssociationName() => [
                            'data' => [
                                ['type' => $itemEntityType, 'id' => (string)$itemEntity1Id],
                                ['type' => $itemEntityType, 'id' => (string)$itemEntity2Id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationship()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $this->getAssociationName()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity1Id],
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity2Id]
                ]
            ],
            $response
        );
    }

    public function testGetSubresource()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $this->getAssociationName()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => $itemEntityType,
                        'id'         => (string)$itemEntity1Id,
                        'attributes' => [
                            'name' => 'Item Entity 1'
                        ]
                    ],
                    [
                        'type'       => $itemEntityType,
                        'id'         => (string)$itemEntity2Id,
                        'attributes' => [
                            'name' => 'Item Entity 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testAddRelationship()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $entity2Id = $this->getReference('test_entity2')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity3Id = $this->getReference('test_item_entity3')->getId();

        $this->postRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $this->getAssociationName()],
            [
                'data' => [
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity3Id]
                ]
            ]
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(3, $this->getItems($entity));

        $entity2 = $em->find($this->getCollectionEntityClass(), $entity2Id);
        self::assertNotNull($entity2);
        if ($this->isManyToMany()) {
            self::assertCount(2, $this->getItems($entity2));
        } else {
            self::assertCount(1, $this->getItems($entity2));
        }
    }

    public function testDeleteRelationship()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $this->deleteRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $this->getAssociationName()],
            [
                'data' => [
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity1Id]
                ]
            ]
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(1, $this->getItems($entity));
        if ($this->isOrphanRemoval()) {
            self::assertTrue(null === $em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        } else {
            self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        }
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }

    public function testUpdateRelationshipWithRemoveItemFromCollection()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $this->getAssociationName()],
            [
                'data' => [
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity2Id]
                ]
            ]
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(1, $this->getItems($entity));
        if ($this->isOrphanRemoval()) {
            self::assertTrue(null === $em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        } else {
            self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        }
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }

    public function testUpdateRelationshipWithRemoveAllItemsFromCollection()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $this->getAssociationName()],
            [
                'data' => []
            ]
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(0, $this->getItems($entity));
        if ($this->isOrphanRemoval()) {
            self::assertTrue(null === $em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
            self::assertTrue(null === $em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
        } else {
            self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
            self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
        }
    }

    public function testUpdateRelationshipWithAddItemToCollection()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $entity2Id = $this->getReference('test_entity2')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();
        $itemEntity3Id = $this->getReference('test_item_entity3')->getId();

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $this->getAssociationName()],
            [
                'data' => [
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity1Id],
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity2Id],
                    ['type' => $itemEntityType, 'id' => (string)$itemEntity3Id]
                ]
            ]
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(3, $this->getItems($entity));

        $entity2 = $em->find($this->getCollectionEntityClass(), $entity2Id);
        self::assertNotNull($entity2);
        if ($this->isManyToMany()) {
            self::assertCount(2, $this->getItems($entity2));
        } else {
            self::assertCount(1, $this->getItems($entity2));
        }
    }

    public function testUpdateWithRemoveItemFromCollection()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'relationships' => [
                    $this->getAssociationName() => [
                        'data' => [
                            ['type' => $itemEntityType, 'id' => (string)$itemEntity2Id]
                        ]
                    ]
                ]
            ]
        ];

        $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(1, $this->getItems($entity));
        if ($this->isOrphanRemoval()) {
            self::assertTrue(null === $em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        } else {
            self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        }
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }

    public function testUpdateWithRemoveAllItemsFromCollection()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'relationships' => [
                    $this->getAssociationName() => [
                        'data' => []
                    ]
                ]
            ]
        ];

        $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(0, $this->getItems($entity));
        if ($this->isOrphanRemoval()) {
            self::assertTrue(null === $em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
            self::assertTrue(null === $em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
        } else {
            self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
            self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
        }
    }

    public function testUpdateWithAddItemToCollection()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $entity2Id = $this->getReference('test_entity2')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();
        $itemEntity3Id = $this->getReference('test_item_entity3')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'relationships' => [
                    $this->getAssociationName() => [
                        'data' => [
                            ['type' => $itemEntityType, 'id' => (string)$itemEntity1Id],
                            ['type' => $itemEntityType, 'id' => (string)$itemEntity2Id],
                            ['type' => $itemEntityType, 'id' => (string)$itemEntity3Id]
                        ]
                    ]
                ]
            ]
        ];

        $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data
        );

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(3, $this->getItems($entity));

        $entity2 = $em->find($this->getCollectionEntityClass(), $entity2Id);
        self::assertNotNull($entity2);
        if ($this->isManyToMany()) {
            self::assertCount(2, $this->getItems($entity2));
        } else {
            self::assertCount(1, $this->getItems($entity2));
        }
    }

    public function testTryToUpdateWithRemoveItemFromCollectionAndHasValidationErrors()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'attributes'    => [
                    'name' => null
                ],
                'relationships' => [
                    $this->getAssociationName() => [
                        'data' => [
                            ['type' => $itemEntityType, 'id' => (string)$itemEntity2Id]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data,
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

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(2, $this->getItems($entity));
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }

    public function testTryToUpdateWithRemoveAllItemsFromCollectionAndHasValidationErrors()
    {
        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntity1Id = $this->getReference('test_item_entity1')->getId();
        $itemEntity2Id = $this->getReference('test_item_entity2')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'attributes'    => [
                    'name' => null
                ],
                'relationships' => [
                    $this->getAssociationName() => [
                        'data' => []
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data,
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

        $em = $this->getEntityManager();
        $entity = $em->find($this->getCollectionEntityClass(), $entityId);
        self::assertNotNull($entity);
        self::assertCount(2, $this->getItems($entity));
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }
}
