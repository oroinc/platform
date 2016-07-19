<?php
namespace Oro\Bundle\DataAudit\Tests\Functional\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataChild;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataOwner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class SendChangedEntitiesToMessageQueueListenerTest extends WebTestCase
{
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

    public function testShouldDoNothingIfDisabled()
    {
        $this->getMessageProducer()->clearTraces();

        $em = $this->getEntityManager();
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertEmpty($traces);
    }

    public function testShouldBeEnabledByDefault()
    {
        /** @var SendChangedEntitiesToMessageQueueListener $listener */
        $listener = $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');

        $this->assertAttributeEquals(true, 'enabled', $listener);
    }

    public function testShouldDoNothingIfNothingChanged()
    {
        $em = $this->getEntityManager();

        $this->getMessageProducer()->clearTraces();

        /** @var SendChangedEntitiesToMessageQueueListener $listener */
        $listener = $this->getContainer()->get('oro_dataaudit.listener.send_changed_entities_to_message_queue');
        $listener->setEnabled(false);

        //guard
        $this->assertAttributeEquals(false, 'enabled', $listener);

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertEmpty($traces);
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

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);
        
        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        $message = $traces[0]['message'];
        $this->assertArrayHasKey('timestamp', $message);
        $this->assertArrayHasKey('transaction_id', $message);

        $this->assertArrayHasKey('entities_updated', $message);
        $this->assertInternalType('array', $message['entities_updated']);

        $this->assertArrayHasKey('entities_deleted', $message);
        $this->assertInternalType('array', $message['entities_deleted']);

        $this->assertArrayHasKey('entities_inserted', $message);
        $this->assertInternalType('array', $message['entities_inserted']);

        $this->assertArrayHasKey('collections_updated', $message);
        $this->assertInternalType('array', $message['collections_updated']);
    }

    public function testShouldSendMessageWithVeryLowPriority()
    {
        $em = $this->getEntityManager();

        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');
        $em->persist($owner);

        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        $this->assertEquals(MessagePriority::VERY_LOW, $traces[0]['priority']);
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

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        $message = $traces[0]['message'];
        $this->assertArrayHasKey('timestamp', $message);
        $this->assertNotEmpty($message['timestamp']);
        
        $this->assertGreaterThan(time() - 10, $message['timestamp']);
        $this->assertLessThan(time() + 10, $message['timestamp']);
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

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        $message = $traces[0]['message'];
        $this->assertArrayHasKey('transaction_id', $message);
        $this->assertNotEmpty($message['transaction_id']);
    }

    public function testShouldSendInsertedEntityToMessageQueue()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();

        $this->assertCount(1, $traces);
        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);
    }

    public function testShouldSendUpdatedEntityToMessageQueue()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $this->getMessageProducer()->clearTraces();
        
        $owner->setStringProperty('anotherString');
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);
    }

    public function testShouldSendDeletedEntityToMessageQueue()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('aString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        $this->getMessageProducer()->clearTraces();
        $em->remove($owner);
        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);
        
        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);
    }

    public function testShouldSendUpdatedCollectionToMessageQueue()
    {
        $em = $this->getEntityManager();

        $toBeAddedChild = new TestAuditDataChild();
        $em->persist($toBeAddedChild);

        $owner = new TestAuditDataOwner();
        $em->persist($owner);

        $em->flush();

        $this->getMessageProducer()->clearTraces();

        $owner->getChildrenManyToMany()->add($toBeAddedChild);

        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();

        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);
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

        $this->getMessageProducer()->clearTraces();

        $toBeUpdateEntity->setStringProperty('anotherString');

        $em->remove($toBeDeletedEntity);

        $toBeInsertedEntity = new TestAuditDataOwner();
        $toBeInsertedEntity->setStringProperty('aString');
        $em->persist($toBeInsertedEntity);

        $em->flush();

        $traces = $this->getMessageProducer()->getTraces();

        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);
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

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        $message = $traces[0]['message'];

        $this->assertArrayNotHasKey('user_id', $message);
        $this->assertArrayNotHasKey('user_class', $message);
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

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        $message = $traces[0]['message'];

        $this->assertArrayHasKey('user_id', $message);
        $this->assertSame(123, $message['user_id']);

        $this->assertArrayHasKey('user_class', $message);
        $this->assertSame(User::class, $message['user_class']);
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

        $traces = $this->getMessageProducer()->getTraces();
        $this->assertCount(1, $traces);

        //guard
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        $message = $traces[0]['message'];

        $this->assertArrayHasKey('organization_id', $message);
        $this->assertSame(123, $message['organization_id']);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return TraceableMessageProducer
     */
    private function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
