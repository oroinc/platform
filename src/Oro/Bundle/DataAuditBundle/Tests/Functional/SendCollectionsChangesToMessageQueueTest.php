<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SendCollectionsChangesToMessageQueueTest extends WebTestCase
{
    use SendChangedEntitiesToMessageQueueExtensionTrait;
    
    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldSendCollectionUpdateWhenNewChildAddedToNewOwner()
    {
        $child = new TestAuditDataChild();

        $owner = new TestAuditDataOwner();
        $owner->getChildrenManyToMany()->add($child);

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->persist($child);
        $this->getEntityManager()->flush();

        // gurad
        $this->assertNotEmpty($child->getId());
        $childId = $child->getId();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(2, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);

        $updateCollection = $message->getBody()['collections_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $updateCollection['entity_class']);
        $this->assertEquals($owner->getId(), $updateCollection['entity_id']);
        $this->assertEquals(
            [
                'childrenManyToMany' => [
                    null,
                    [
                        'inserted' => [
                            ['entity_class' => TestAuditDataChild::class, 'entity_id' => $childId]
                        ],
                        'deleted' => [],
                        'changed' => []
                    ]
                ]
            ],
            $updateCollection['change_set']
        );
    }

    public function testShouldSendCollectionUpdateWhenStoredChildAddedToNewOwner()
    {
        $child = $this->createChild();

        $owner = new TestAuditDataOwner();
        $owner->getChildrenManyToMany()->add($child);

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);

        $updateCollection = $message->getBody()['collections_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $updateCollection['entity_class']);
        $this->assertEquals($owner->getId(), $updateCollection['entity_id']);
        $this->assertEquals(
            [
                'childrenManyToMany' => [
                    null,
                    [
                        'inserted' => [
                            ['entity_class' => TestAuditDataChild::class, 'entity_id' => $child->getId()]
                        ],
                        'deleted' => [],
                        'changed' => []
                    ]
                ]
            ],
            $updateCollection['change_set']
        );
    }

    public function testShouldSendCollectionUpdateWhenStoredChildAddedToStoredOwnerAdded()
    {
        $owner = $this->createOwner();
        $child = $this->createChild();

        $owner->getChildrenManyToMany()->add($child);

        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updateCollection = $message->getBody()['collections_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $updateCollection['entity_class']);
        $this->assertEquals($owner->getId(), $updateCollection['entity_id']);
        $this->assertEquals(
            [
                'childrenManyToMany' => [
                    null,
                    [
                        'inserted' => [
                            ['entity_class' => TestAuditDataChild::class, 'entity_id' => $child->getId()]
                        ],
                        'deleted' => [],
                        'changed' => []
                    ]
                ]
            ],
            $updateCollection['change_set']
        );
    }

    public function testShouldSendCollectionUpdateWhenStoredChildAddedToStoredProxyOwnerAdded()
    {
        $owner = $this->createOwnerProxy();
        $child = $this->createChild();

        $owner->getChildrenManyToMany()->add($child);

        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updateCollection = $message->getBody()['collections_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $updateCollection['entity_class']);
        $this->assertEquals($owner->getId(), $updateCollection['entity_id']);
        $this->assertEquals(
            [
                'childrenManyToMany' => [
                    null,
                    [
                        'inserted' => [
                            ['entity_class' => TestAuditDataChild::class, 'entity_id' => $child->getId()]
                        ],
                        'deleted' => [],
                        'changed' => []
                    ]
                ]
            ],
            $updateCollection['change_set']
        );
    }

    public function testShouldSendCollectionWhenOneChildRemoved()
    {
        $owner = $this->createOwnerProxy();

        $child = $this->createChild();
        $owner->getChildrenManyToMany()->add($child);

        $this->getEntityManager()->flush();
        self::getMessageCollector()->clear();

        $owner->getChildrenManyToMany()->removeElement($child);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updateCollection = $message->getBody()['collections_updated'][0];
        $this->assertEquals(TestAuditDataOwner::class, $updateCollection['entity_class']);
        $this->assertEquals($owner->getId(), $updateCollection['entity_id']);
        $this->assertEquals(
            [
                'childrenManyToMany' => [
                    null,
                    [
                        'deleted' => [
                            ['entity_class' => TestAuditDataChild::class, 'entity_id' => $child->getId()]
                        ],
                        'inserted' => [],
                        'changed' => []
                    ]
                ]
            ],
            $updateCollection['change_set']
        );
    }

    public function testShouldSendCollectionWhenOneChildRemovedFromCollectionAndRemovedItself()
    {
        $owner = $this->createOwnerProxy();

        $child = $this->createChild();
        $childId = $child->getId();

        $owner->getChildrenManyToMany()->add($child);
        $this->getEntityManager()->flush();
        self::getMessageCollector()->clear();

        $owner->getChildrenManyToMany()->removeElement($child);
        $this->getEntityManager()->remove($child);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updateCollection = $message->getBody()['collections_updated'][0];
        $this->assertEquals(TestAuditDataOwner::class, $updateCollection['entity_class']);
        $this->assertEquals($owner->getId(), $updateCollection['entity_id']);
        $this->assertEquals(
            [
                'childrenManyToMany' => [
                    null,
                    [
                        'deleted' => [
                            ['entity_class' => TestAuditDataChild::class, 'entity_id' => $childId]
                        ],
                        'inserted' => [],
                        'changed' => []
                    ]
                ]
            ],
            $updateCollection['change_set']
        );
    }

    public function testShouldSendCollectionWhenClearedAndOneAdded()
    {
        $owner = $this->createOwnerProxy();
        $ownerId = $owner->getId();

        $child = $this->createChild();

        $owner->getChildrenManyToMany()->clear();
        $owner->getChildrenManyToMany()->add($child);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();
        $owner = $this->getEntityManager()->find(TestAuditDataOwner::class, $ownerId);

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updateCollection = $message->getBody()['collections_updated'][0];
        $this->assertEquals(TestAuditDataOwner::class, $updateCollection['entity_class']);
        $this->assertEquals($owner->getId(), $updateCollection['entity_id']);
        $this->assertEquals(
            [
                'childrenManyToMany' => [
                    null,
                    [
                        'inserted' => [
                            ['entity_class' => TestAuditDataChild::class, 'entity_id' => $child->getId()]
                        ],
                        'deleted' => [],
                        'changed' => []
                    ]
                ]
            ],
            $updateCollection['change_set']
        );
    }

    public function testShouldSendEntityInsertedWhenNewChildAddedToNewOwner()
    {
        $child = new TestAuditDataChild();

        $owner = new TestAuditDataOwner();
        $owner->addChildrenOneToMany($child);

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->persist($child);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(2, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);

        // guard
        $this->assertEquals(TestAuditDataOwner::class, $message->getBody()['entities_inserted'][0]['entity_class']);

        $insertedEntity = $message->getBody()['entities_inserted'][1];
        $this->assertEquals(TestAuditDataChild::class, $insertedEntity['entity_class']);
        $this->assertEquals($child->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            [
                'ownerManyToOne' => [
                    null,
                    ['entity_class' => TestAuditDataOwner::class, 'entity_id' => $owner->getId()]
                ]
            ],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendEntityUpdatedWhenStoredChildAddedToNewOwner()
    {
        $child = $this->createChild();

        $owner = new TestAuditDataOwner();
        $owner->addChildrenOneToMany($child);

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        // guard
        $this->assertEquals(TestAuditDataOwner::class, $message->getBody()['entities_inserted'][0]['entity_class']);

        $updatedEntity = $message->getBody()['entities_updated'][0];
        $this->assertEquals(TestAuditDataChild::class, $updatedEntity['entity_class']);
        $this->assertEquals($child->getId(), $updatedEntity['entity_id']);
        $this->assertEquals(
            [
                'ownerManyToOne' => [
                    null,
                    ['entity_class' => TestAuditDataOwner::class, 'entity_id' => $owner->getId()]
                ]
            ],
            $updatedEntity['change_set']
        );
    }

    public function testShouldSendEntityUpdateWhenStoredChildAddedToStoredOwnerAdded()
    {
        $owner = $this->createOwner();
        $child = $this->createChild();

        $owner->addChildrenOneToMany($child);

        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updatedEntity = $message->getBody()['entities_updated'][0];
        $this->assertEquals(TestAuditDataChild::class, $updatedEntity['entity_class']);
        $this->assertEquals($child->getId(), $updatedEntity['entity_id']);
        $this->assertEquals(
            [
                'ownerManyToOne' => [
                    null,
                    ['entity_class' => TestAuditDataOwner::class, 'entity_id' => $owner->getId()]
                ]
            ],
            $updatedEntity['change_set']
        );
    }

    public function testShouldSendEntityUpdateWhenStoredChildAddedToStoredProxyOwnerAdded()
    {
        $owner = $this->createOwnerProxy();
        $child = $this->createChild();

        $owner->addChildrenOneToMany($child);

        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updatedEntity = $message->getBody()['entities_updated'][0];
        $this->assertEquals(TestAuditDataChild::class, $updatedEntity['entity_class']);
        $this->assertEquals($child->getId(), $updatedEntity['entity_id']);
        $this->assertEquals(
            [
                'ownerManyToOne' => [
                    null,
                    ['entity_class' => TestAuditDataOwner::class, 'entity_id' => $owner->getId()]
                ]
            ],
            $updatedEntity['change_set']
        );
    }

    public function testShouldSendEntityRemoveWhenOneChildRemoved()
    {
        $owner = $this->createOwnerProxy();

        $child = $this->createChild();
        $owner->addChildrenOneToMany($child);

        $this->getEntityManager()->flush();
        self::getMessageCollector()->clear();

        $owner->removeChildrenOneToMany($child);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $updatedEntity = $message->getBody()['entities_updated'][0];
        $this->assertEquals(TestAuditDataChild::class, $updatedEntity['entity_class']);
        $this->assertEquals($child->getId(), $updatedEntity['entity_id']);
        $this->assertEquals(
            [
                'ownerManyToOne' => [
                    ['entity_class' => TestAuditDataOwner::class, 'entity_id' => $owner->getId()],
                    null
                ]
            ],
            $updatedEntity['change_set']
        );
    }

    public function testShouldSendEntityDeletedWhenOneChildRemovedFromCollectionAndRemovedItself()
    {
        $owner = $this->createOwnerProxy();

        $child = $this->createChild();
        $childId = $child->getId();

        $owner->addChildrenOneToMany($child);
        $this->getEntityManager()->flush();
        self::getMessageCollector()->clear();

        $owner->removeChildrenOneToMany($child);
        $this->getEntityManager()->remove($child);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);

        $deletedEntity = $message->getBody()['entities_deleted'][0];
        $this->assertEquals(TestAuditDataChild::class, $deletedEntity['entity_class']);
        $this->assertEquals($childId, $deletedEntity['entity_id']);
        $this->assertArrayNotHasKey('change_set', $deletedEntity);
    }

    public function testShouldSendEntityUpdatedWhenClearedAndOneAdded()
    {
        $owner = $this->createOwnerProxy();

        $child = $this->createChild();

        $owner->addChildrenOneToMany($this->createChild());
        $owner->addChildrenOneToMany($this->createChild());
        $this->getEntityManager()->flush();
        self::getMessageCollector()->clear();

        $owner->getChildrenOneToMany()->clear();
        $owner->addChildrenOneToMany($child);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertCollectionsUpdatedInMessageCount(1, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(1, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];
        $this->assertEquals(TestAuditDataChild::class, $insertedEntity['entity_class']);
        $this->assertEquals($child->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            [
                'ownerManyToOne' => [
                    null,
                    ['entity_class' => TestAuditDataOwner::class, 'entity_id' => $owner->getId()]
                ]
            ],
            $insertedEntity['change_set']
        );
    }
}
