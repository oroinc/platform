<?php
namespace Oro\Bundle\DataAudit\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataChild;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SendDeletedEntitiesToMessageQueueTest extends WebTestCase
{
    use SendChangedEntitiesToMessageQueueExtensionTrait;
    
    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], [], true);
        $this->startTransaction();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
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
        $this->assertEquals([], $deletedEntity['change_set']);
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
        $this->assertEquals([], $deletedEntity['change_set']);
    }

    public function testShouldSendDeletedEntityFromManyToOneRelation()
    {
        $owner = $this->createOwner();
        $ownerId = $owner->getId();

        $child = $this->createChild();
        $childId = $child->getId();

        $child->setOwnerManyToOne($owner);

        $this->getEntityManager()->flush();
        $this->getMessageProducer()->clear();

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
        $this->assertEquals([
            'ownerManyToOne' => [
                [
                    'entity_id' => $ownerId,
                    'entity_class' => TestAuditDataOwner::class,
                    'change_set' => [],
                ],
                null
            ]
        ], $deletedEntity['change_set']);
    }
}
