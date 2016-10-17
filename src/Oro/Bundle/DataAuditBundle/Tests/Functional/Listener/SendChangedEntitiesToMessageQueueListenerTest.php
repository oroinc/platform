<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataChild;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class SendChangedEntitiesToMessageQueueListenerTest extends WebTestCase
{
    use MessageQueueExtension;
    
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

    public function testCouldBeGetAsServiceFromContainer()
    {
        $listener = $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');

        $this->assertInstanceOf(SendChangedEntitiesToMessageQueueListener::class, $listener);
    }

    public function testShouldBeEnabledByDefault()
    {
        /** @var SendChangedEntitiesToMessageQueueListener $listener */
        $listener = $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');

        $this->assertAttributeEquals(true, 'enabled', $listener);
    }

    public function testShouldDoNothingIfListenerDisabled()
    {
        $em = $this->getEntityManager();

        /** @var SendChangedEntitiesToMessageQueueListener $listener */
        $listener = $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');
        $listener->setEnabled(false);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);
        $em->flush();

        $this->assertEmpty(self::getSentMessages());
    }

    /**
     * Emulates case when the following chain of events happens:
     * onFlush -> onFlush -> postFlush -> postFlush
     */
    public function testShouldPostFlushNotThrowExceptionIfFlushIsCalledInPostFlushListener()
    {
        /** @var SendChangedEntitiesToMessageQueueListener $listener */
        $listener = $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');

        $listener->postFlush(new PostFlushEventArgs($this->getEntityManager()));
    }

    public function testShouldSendEntitiesChangedMessageWithExpectedStructure()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $sentMessages[0]['topic']);

        /** @var Message $message */
        $message = $sentMessages[0]['message'];

        self::assertInstanceOf(Message::class, $message);

        $body = $message->getBody();
        $this->assertArrayHasKey('timestamp', $body);
        $this->assertArrayHasKey('transaction_id', $body);

        $this->assertArrayHasKey('entities_updated', $body);
        $this->assertInternalType('array', $body['entities_updated']);

        $this->assertArrayHasKey('entities_deleted', $body);
        $this->assertInternalType('array', $body['entities_deleted']);

        $this->assertArrayHasKey('entities_inserted', $body);
        $this->assertInternalType('array', $body['entities_inserted']);

        $this->assertArrayHasKey('collections_updated', $body);
        $this->assertInternalType('array', $body['collections_updated']);
    }

    public function testShouldSendMessageWithVeryLowPriority()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);
        $this->assertEquals(Topics::ENTITIES_CHANGED, $sentMessages[0]['topic']);
        $this->assertEquals(MessagePriority::VERY_LOW, $sentMessages[0]['message']->getPriority());
    }

    public function testShouldSetTimestampToMessage()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $sentMessages[0]['topic']);

        $message = $sentMessages[0]['message'];
        $this->assertArrayHasKey('timestamp', $message->getBody());
        $this->assertNotEmpty($message->getBody()['timestamp']);
        
        $this->assertGreaterThan(time() - 10, $message->getBody()['timestamp']);
        $this->assertLessThan(time() + 10, $message->getBody()['timestamp']);
    }

    public function testShouldSetTransactionIdToMessage()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $sentMessages[0]['topic']);

        $message = $sentMessages[0]['message'];
        $this->assertArrayHasKey('transaction_id', $message->getBody());
        $this->assertNotEmpty($message->getBody()['transaction_id']);
    }

    public function testShouldSendInsertedEntityToMessageQueue()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        self::assertMessageSent(Topics::ENTITIES_CHANGED);
    }

    public function testShouldSendUpdatedEntityToMessageQueue()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        self::getMessageCollector()->clear();

        $owner->setStringProperty('anotherString');
        $em->flush();

        self::assertMessageSent(Topics::ENTITIES_CHANGED);
    }

    public function testShouldSendDeletedEntityToMessageQueue()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        self::getMessageCollector()->clear();

        $em->remove($owner);
        $em->flush();

        self::assertMessageSent(Topics::ENTITIES_CHANGED);
    }

    public function testShouldSendUpdatedCollectionToMessageQueue()
    {
        $em = $this->getEntityManager();

        $toBeAddedChild = new TestAuditDataChild();
        $em->persist($toBeAddedChild);

        $owner = new TestAuditDataOwner();
        $em->persist($owner);

        $em->flush();

        self::getMessageCollector()->clear();

        $owner->getChildrenManyToMany()->add($toBeAddedChild);

        $em->flush();

        self::assertMessageSent(Topics::ENTITIES_CHANGED);
    }

    public function testShouldSendOneMessagePerFlush()
    {
        $em = $this->getEntityManager();

        $toBeUpdateEntity = new TestAuditDataOwner();
        $toBeUpdateEntity->setStringProperty('aString');
        $em->persist($toBeUpdateEntity);

        $toBeDeletedEntity = new TestAuditDataOwner();
        $toBeDeletedEntity->setStringProperty('aString');
        $em->persist($toBeUpdateEntity);
        $em->flush();

        self::getMessageCollector()->clear();

        $toBeUpdateEntity->setStringProperty('anotherString');

        $em->remove($toBeDeletedEntity);

        $toBeInsertedEntity = new TestAuditDataOwner();
        $toBeInsertedEntity->setStringProperty('aString');
        $em->persist($toBeInsertedEntity);

        $em->flush();

        self::assertMessageSent(Topics::ENTITIES_CHANGED);
    }

    public function testShouldNotSendLoggedInUserInfoIfPresentButNotUserInstance()
    {
        $token = new UsernamePasswordToken($this->getMock(UserInterface::class), 'someCredentinals', 'aProviderKey');

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $sentMessages[0]['topic']);

        $message = $sentMessages[0]['message'];

        $this->assertArrayNotHasKey('user_id', $message->getBody());
        $this->assertArrayNotHasKey('user_class', $message->getBody());
    }

    public function testShouldSendLoggedInUserInfoIfPresent()
    {
        $user = new User();
        $user->setId(123);

        $token = new UsernamePasswordToken($user, 'someCredentinals', 'aProviderKey');

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $sentMessages[0]['topic']);

        $message = $sentMessages[0]['message'];

        $this->assertArrayHasKey('user_id', $message->getBody());
        $this->assertSame(123, $message->getBody()['user_id']);

        $this->assertArrayHasKey('user_class', $message->getBody());
        $this->assertSame(User::class, $message->getBody()['user_class']);
    }

    public function testShouldSendOrganizationInfoIfPresent()
    {
        $organization = new Organization();
        $organization->setId(123);

        $token = new OrganizationToken($organization);

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $tokenStorage->setToken($token);

        $em = $this->getEntityManager();

        $entity = new TestAuditDataOwner();
        $entity->setStringProperty('aString');
        $em->persist($entity);
        $em->flush();

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $sentMessages[0]['topic']);

        $message = $sentMessages[0]['message'];

        $this->assertArrayHasKey('organization_id', $message->getBody());
        $this->assertSame(123, $message->getBody()['organization_id']);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
