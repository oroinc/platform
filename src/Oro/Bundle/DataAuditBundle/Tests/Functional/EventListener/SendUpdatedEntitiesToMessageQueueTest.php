<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SendUpdatedEntitiesToMessageQueueTest extends WebTestCase
{
    use SendChangedEntitiesToMessageQueueExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->getOptionalListenerManager()->enableListener(
            'oro_dataaudit.listener.send_changed_entities_to_message_queue'
        );
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

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'stringProperty' => [null, 'aString'],
        ], $insertedEntity['change_set']);
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

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'stringProperty' => [null, 'aString'],
        ], $insertedEntity['change_set']);
    }

    public function testShouldSendWhenIntegerPropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setIntegerProperty(1234);

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'integerProperty' => [null, 1234],
        ], $insertedEntity['change_set']);
    }

    public function testShouldSendWhenObjectPropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setObjectProperty(['foo' => 'fooVal']);

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertEntitiesUpdatedInMessageCount(1, $message);
        $this->assertEntitiesInsertedInMessageCount(0, $message);
        $this->assertEntitiesDeletedInMessageCount(0, $message);
        $this->assertCollectionsUpdatedInMessageCount(0, $message);

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'objectProperty' => [null, ['foo' => 'fooVal']],
        ], $insertedEntity['change_set']);
    }

    public function testShouldSendWhenJsonArrayPropertyChanged()
    {
        $owner = $this->createOwner();
        $owner->setJsonArrayProperty(['foo' => 'fooVal']);

        $em = $this->getEntityManager();
        $em->flush();

        $message = $this->getFirstEntitiesChangedMessage();
        $this->assertTrue(isset($message->getBody()['entities_updated'][spl_object_hash($owner)]));
        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'jsonArrayProperty' => [null, ['foo' => 'fooVal']],
        ], $insertedEntity['change_set']);
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

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'dateProperty' => [null, '2010-11-12T00:01:02+0000'],
        ], $insertedEntity['change_set']);
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

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
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

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'child' => [null, [
                'entity_class' => TestAuditDataChild::class,
                'entity_id' => $child->getId(),
            ]],
        ], $insertedEntity['change_set']);
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

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals(TestAuditDataOwner::class, $insertedEntity['entity_class']);
        $this->assertEquals($owner->getId(), $insertedEntity['entity_id']);
        $this->assertEquals([
            'child' => [
                [
                    'entity_class' => TestAuditDataChild::class,
                    'entity_id' => $firstChild->getId(),
                ],
                [
                    'entity_class' => TestAuditDataChild::class,
                    'entity_id' => $secondChild->getId(),
                ]
            ],
        ], $insertedEntity['change_set']);
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

        $insertedEntity = $message->getBody()['entities_updated'][spl_object_hash($owner)];

        $this->assertEquals([
            'child' => [null, [
                'entity_class' => TestAuditDataChild::class,
                'entity_id' => $child->getId(),
            ]],
        ], $insertedEntity['change_set']);
    }

    public function testShouldNotSendWhenMoneyPropertyNotChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setMoneyProperty(1.01);

        $em = $this->saveOwnerAndClearMessages($owner);
        $em->clear();
        /** @var TestAuditDataOwner $owner */
        $owner = $em->getRepository(TestAuditDataOwner::class)->findOneBy(['id' => $owner->getId()]);
        $owner->setMoneyProperty(1.01);
        $em->flush();

        self::assertSame([], self::getSentMessages());
    }

    public function testShouldNotSendWhenDecimalPropertyNotChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setDecimalProperty(1.01);

        $em = $this->saveOwnerAndClearMessages($owner);
        $em->clear();
        /** @var TestAuditDataOwner $owner */
        $owner = $em->getRepository(TestAuditDataOwner::class)->findOneBy(['id' => $owner->getId()]);
        $owner->setDecimalProperty(1.01);
        $em->flush();

        self::assertSame([], self::getSentMessages());
    }

    public function testShouldNotSendWhenMoneyValuePropertyNotChanged()
    {
        $owner = new TestAuditDataOwner();
        $owner->setMoneyValueProperty('1.01');

        $em = $this->saveOwnerAndClearMessages($owner);
        $em->clear();
        /** @var TestAuditDataOwner $owner */
        $owner = $em->getRepository(TestAuditDataOwner::class)->findOneBy(['id' => $owner->getId()]);
        $owner->setMoneyValueProperty('1.01');
        $em->flush();

        self::assertSame([], self::getSentMessages());
    }

    private function saveOwnerAndClearMessages(TestAuditDataOwner $owner): EntityManagerInterface
    {
        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();
        $this->getMessageCollector()->clear();

        return $em;
    }
}
