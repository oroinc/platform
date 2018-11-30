<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SendDeletedEntitiesToMessageQueueTest extends WebTestCase
{
    use SendChangedEntitiesToMessageQueueExtensionTrait;
    
    protected function setUp()
    {
        $this->initClient();
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

        $em = $this->getEntityManager();
        $em->remove($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesDeletedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);
        
        $deletedEntity = $message->getBody()['entities_deleted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $deletedEntity['entity_class']);
        $this->assertEquals($ownerId, $deletedEntity['entity_id']);
        $this->assertArrayNotHasKey('change_set', $deletedEntity);
    }

    public function testShouldSendDeletedProxyEntity()
    {
        $owner = $this->createOwnerProxy();
        $ownerId = $owner->getId();

        $em = $this->getEntityManager();
        $em->remove($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesDeletedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $deletedEntity = $message->getBody()['entities_deleted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $deletedEntity['entity_class']);
        $this->assertEquals($ownerId, $deletedEntity['entity_id']);
        $this->assertArrayNotHasKey('change_set', $deletedEntity);
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

        $deletedEntity = $message->getBody()['entities_deleted'][0];

        $this->assertEquals(TestAuditDataChild::class, $deletedEntity['entity_class']);
        $this->assertEquals($childId, $deletedEntity['entity_id']);
        $this->assertEquals(
            [
                'ownerManyToOne' => [
                    ['entity_class' => TestAuditDataOwner::class, 'entity_id' => $ownerId],
                    null
                ]
            ],
            $deletedEntity['change_set']
        );
    }
}
