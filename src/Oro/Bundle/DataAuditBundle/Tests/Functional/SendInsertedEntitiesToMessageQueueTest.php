<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SendInsertedEntitiesToMessageQueueTest extends WebTestCase
{
    use SendChangedEntitiesToMessageQueueExtensionTrait;
    
    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldSendWhenNewEntityInsertedWithoutChanges()
    {
        $owner = new TestAuditDataOwner();

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertArrayNotHasKey('change_set', $insertedEntity);
    }

    public function testShouldSendAllInsertedEntities()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $em->persist($owner);

        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(3, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);
    }

    public function testShouldSendWhenStringPropertyChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['stringProperty' => [null, 'aString']],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenIntPropertyChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setIntProperty(1234);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['intProperty' => [null, 1234]],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenSerializedPropertyChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setSerializedProperty(['foo' => 'fooVal']);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['serializedProperty' => [null, ['foo' => 'fooVal']]],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenJsonPropertyChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setJsonProperty(['foo' => 'fooVal']);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['jsonProperty' => [null, ['foo' => 'fooVal']]],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenDateTimePropertyChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setDateProperty(new \DateTime('2010-11-12 00:01:02+0000'));

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['dateProperty' => [null, '2010-11-12T00:01:02+0000']],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenOneToOnePropertyChanged()
    {
        $em = $this->getEntityManager();

        $child = new TestAuditDataChild();
        $em->persist($child);
        $em->flush();

        self::getMessageCollector()->clear();

        $owner = new TestAuditDataOwner();
        $owner->setChild($child);
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            [
                'child' => [
                    null,
                    ['entity_class' => TestAuditDataChild::class, 'entity_id' => $child->getId()]
                ]
            ],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenOneToOnePropertyChangedWithProxyChild()
    {
        $em = $this->getEntityManager();

        $child = new TestAuditDataChild();
        $em->persist($child);
        $em->flush();
        $em->clear();

        $childProxy = $em->getReference(TestAuditDataChild::class, $child->getId());
        //guard
        $this->assertInstanceOf(Proxy::class, $childProxy);

        self::getMessageCollector()->clear();

        $owner = new TestAuditDataOwner();
        $owner->setChild($childProxy);
        $em->persist($owner);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesInsertedInMessageCount(1, $message);
        $this->assertEntitiesUpdatedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_inserted'][0];

        $this->assertEquals(
            [
                'child' => [
                    null,
                    ['entity_class' => TestAuditDataChild::class, 'entity_id' => $child->getId()]
                ]
            ],
            $insertedEntity['change_set']
        );
    }
}
