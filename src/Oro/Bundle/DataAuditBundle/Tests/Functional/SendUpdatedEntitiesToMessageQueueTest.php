<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SendUpdatedEntitiesToMessageQueueTest extends WebTestCase
{
    use SendChangedEntitiesToMessageQueueExtensionTrait;

    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldSendAllUpdatedEntities()
    {
        $fooOwner = $this->createOwner();
        $barOwner = $this->createOwner();
        $bazOwner = $this->createOwner();

        $em = $this->getEntityManager();

        $fooOwner->setStringProperty('aString');
        $barOwner->setStringProperty('aString');
        $bazOwner->setStringProperty('aString');

        $em->flush();
        
        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(3, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);
    }

    public function testShouldSendWhenStringPropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['stringProperty' => [null, 'aString']],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendRealClassWhenProxyEntityChanged()
    {
        $owner = $this->createOwner();

        $em = $this->getEntityManager();
        $em->clear();

        $owner = $em->getReference(TestAuditDataOwner::class, $owner->getId());
        //guard
        $this->assertInstanceOf(Proxy::class, $owner);

        $owner->setStringProperty('aString');
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['stringProperty' => [null, 'aString']],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenIntPropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setIntProperty(1234);

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['intProperty' => [null, 1234]],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenSerializedPropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setSerializedProperty(['foo' => 'fooVal']);

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['serializedProperty' => [null, ['foo' => 'fooVal']]],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenJsonPropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setJsonProperty(['foo' => 'fooVal']);

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertTrue(isset($message->getBody()['entities_updated'][0]));
        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['jsonProperty' => [null, ['foo' => 'fooVal']]],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenDateTimePropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setDateProperty(new \DateTime('2010-11-12 00:01:02+0000'));

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            ['dateProperty' => [null, '2010-11-12T00:01:02+0000']],
            $insertedEntity['change_set']
        );
    }

    public function testShouldNotSendWhenDateTimePropertyChangedToExactlySameDateTime()
    {
        $date = new \DateTime('2010-11-12 00:01:02+0000');
        $sameDate = clone $date;
        
        $owner = $this->createOwner();
        $owner->setDateProperty($date);

        $em = $this->getEntityManager();
        $em->flush();

        self::getMessageCollector()->clear();

        $owner->setDateProperty($sameDate);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertArrayNotHasKey('change_set', $insertedEntity);
    }

    public function testShouldSendWhenOneToOnePropertyChangedFromNullToChild()
    {
        $child = $this->createChild();

        $em = $this->getEntityManager();
        $em->flush();

        self::getMessageCollector()->clear();

        $owner = $this->createOwner();
        $owner->setChild($child);

        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

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

    public function testShouldSendWhenOneToOnePropertyChangedFromOneChildToAnother()
    {
        $firstChild = $this->createChild();
        $secondChild = $this->createChild();
        $owner = $this->createOwner();

        $em = $this->getEntityManager();

        $owner->setChild($firstChild);

        $em->flush();
        self::getMessageCollector()->clear();

        $owner->setChild($secondChild);
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals(
            [
                'child' => [
                    ['entity_class' => TestAuditDataChild::class, 'entity_id' => $firstChild->getId()],
                    ['entity_class' => TestAuditDataChild::class, 'entity_id' => $secondChild->getId()]
                ]
            ],
            $insertedEntity['change_set']
        );
    }

    public function testShouldSendWhenOneToOnePropertyChangedWithProxyChild()
    {
        $child = $this->createChild();

        $em = $this->getEntityManager();
        $em->clear();

        $childProxy = $em->getReference(TestAuditDataChild::class, $child->getId());
        //guard
        $this->assertInstanceOf(Proxy::class, $childProxy);

        self::getMessageCollector()->clear();

        $owner = $this->createOwner();
        $owner->setChild($childProxy);

        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][0];

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
