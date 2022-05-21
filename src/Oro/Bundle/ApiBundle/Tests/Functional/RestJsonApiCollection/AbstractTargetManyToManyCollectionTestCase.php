<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCollection;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCollectionItem;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractTargetManyToManyCollectionTestCase extends AbstractCollectionTestCase
{
    protected function getCollectionEntityClass(): string
    {
        return TestCollectionItem::class;
    }

    protected function getCollectionItemEntityClass(): string
    {
        return TestCollection::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function isManyToMany(): bool
    {
        return true;
    }

    public function testDeleteRelationship()
    {
        if (!$this->isOrphanRemoval()) {
            parent::testDeleteRelationship();

            return;
        }

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
        self::assertTrue(null === $entity);
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }

    public function testUpdateRelationshipWithRemoveItemFromCollection()
    {
        if (!$this->isOrphanRemoval()) {
            parent::testUpdateRelationshipWithRemoveItemFromCollection();

            return;
        }

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
        self::assertTrue(null === $entity);
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }

    public function testUpdateRelationshipWithRemoveAllItemsFromCollection()
    {
        if (!$this->isOrphanRemoval()) {
            parent::testUpdateRelationshipWithRemoveAllItemsFromCollection();

            return;
        }

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
        self::assertTrue(null === $entity);
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity1Id), 'Item1');
        self::assertNotNull($em->find($this->getCollectionItemEntityClass(), $itemEntity2Id), 'Item2');
    }

    public function testUpdateWithRemoveItemFromCollection()
    {
        if (!$this->isOrphanRemoval()) {
            parent::testUpdateWithRemoveItemFromCollection();

            return;
        }

        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();
        $itemEntityType = $this->getEntityType($this->getCollectionItemEntityClass());
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

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testUpdateWithRemoveAllItemsFromCollection()
    {
        if (!$this->isOrphanRemoval()) {
            parent::testUpdateWithRemoveAllItemsFromCollection();

            return;
        }

        $entityType = $this->getEntityType($this->getCollectionEntityClass());
        $entityId = $this->getReference('test_entity1')->getId();

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

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
