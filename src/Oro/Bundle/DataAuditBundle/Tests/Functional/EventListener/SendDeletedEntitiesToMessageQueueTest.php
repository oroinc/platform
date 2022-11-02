<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\EventListener;

use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SendDeletedEntitiesToMessageQueueTest extends WebTestCase
{
    use SendChangedEntitiesToMessageQueueExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->getOptionalListenerManager()->enableListener(
            'oro_dataaudit.listener.send_changed_entities_to_message_queue'
        );
    }

    public function testShouldSendAllDeletedEntities()
    {
        $fooOwner = $this->createOwner();
        $barOwner = $this->createOwner();
        $bazOwner = $this->createOwner();

        $em = $this->getEntityManager();

        $em->remove($fooOwner);
        $em->remove($barOwner);
        $em->remove($bazOwner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesDeletedInMessageCount(3, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);
    }

    public function testShouldSendDeletedEntity()
    {
        $owner = $this->createOwner();
        $ownerId = $owner->getId();

        $owner->setStringProperty('aString');
        $owner->setIntegerProperty(1234);

        $em = $this->getEntityManager();
        $em->flush();
        self::getMessageCollector()->clear();

        $em->remove($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesDeletedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $deletedEntity = $message->getBody()['entities_deleted'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $deletedEntity['entity_class']);
        $this->assertEquals($ownerId, $deletedEntity['entity_id']);
        $this->assertEquals(
            [
                'stringProperty' => [$owner->getStringProperty(), null],
                'integerProperty' => [$owner->getIntegerProperty(), null],
                'id' => [$ownerId, null],
            ],
            $deletedEntity['change_set']
        );
    }

    public function testShouldSendDeletedProxyEntity()
    {
        $owner = $this->createOwnerProxy();
        $ownerId = $owner->getId();
        $owner->setBooleanProperty(false);
        $owner->setStringProperty('string');
        $owner = $this->getEntityManager()->getReference(TestAuditDataOwner::class, $owner->getId());

        $em = $this->getEntityManager();
        $em->remove($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesDeletedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $deletedEntity = $message->getBody()['entities_deleted'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $deletedEntity['entity_class']);
        $this->assertEquals($ownerId, $deletedEntity['entity_id']);
        $this->assertEquals(
            [
                'id' => [$ownerId, null],
                'booleanProperty' => [false, null],
                'stringProperty' => ['string', null],
                'jsonArrayProperty' => [[], null],
                'simpleArrayProperty' => [[], null],
            ],
            $deletedEntity['change_set']
        );
    }

    public function testShouldSendDeletedEntityFromManyToOneRelation()
    {
        $owner = $this->createOwner();
        $ownerId = $owner->getId();

        $child = $this->createChild();
        $childId = $child->getId();

        $child->setOwnerManyToOne($owner);

        $this->getEntityManager()->flush();
        self::getMessageCollector()->clear();

        $this->getEntityManager()->remove($child);
        $this->getEntityManager()->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesDeletedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $deletedEntity = $message->getBody()['entities_deleted'][spl_object_hash($child)];

        $this->assertEquals(TestAuditDataChild::class, $deletedEntity['entity_class']);
        $this->assertEquals($childId, $deletedEntity['entity_id']);
        $this->assertEquals([
            'ownerManyToOne' => [
                [
                    'entity_id' => $ownerId,
                    'entity_class' => TestAuditDataOwner::class,
                ],
                null
            ],
            'id' => [$childId, null],
        ], $deletedEntity['change_set']);
    }
}
