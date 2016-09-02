<?php
namespace Oro\Bundle\DataAudit\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataChild;
use Oro\Bundle\TestFrameworkBundle\Entity\TestAuditDataOwner;
use Symfony\Bundle\FrameworkBundle\Client;

trait SendChangedEntitiesToMessageQueueExtensionTrait
{
    use MessageQueueExtension;

    /**
     * @param int $expected
     * @param array $message
     */
    public function assertEntitiesInsertedInMessageCount($expected, $message)
    {
        $this->assertTrue(isset($message->getBody()['entities_inserted']));
        $this->assertCount($expected, $message->getBody()['entities_inserted']);
    }

    /**
     * @param int $expected
     * @param array $message
     */
    public function assertEntitiesUpdatedInMessageCount($expected, $message)
    {
        $this->assertTrue(isset($message->getBody()['entities_updated']));
        $this->assertCount($expected, $message->getBody()['entities_updated']);
    }

    /**
     * @param int $expected
     * @param array $message
     */
    public function assertEntitiesDeletedInMessageCount($expected, $message)
    {
        $this->assertTrue(isset($message->getBody()['entities_deleted']));
        $this->assertCount($expected, $message->getBody()['entities_deleted']);
    }

    /**
     * @param int $expected
     * @param array $message
     */
    public function assertCollectionsUpdatedInMessageCount($expected, $message)
    {
        $this->assertTrue(isset($message->getBody()['collections_updated']));
        $this->assertCount($expected, $message->getBody()['collections_updated']);
    }

    /**
     * @return TestAuditDataOwner
     */
    protected function createOwner()
    {
        $owner = new TestAuditDataOwner();

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->flush();

        $this->getMessageProducer()->clear();

        return $owner;
    }

    /**
     * @return TestAuditDataOwner|Proxy
     */
    protected function createOwnerProxy()
    {
        $owner = $this->createOwner();

        $this->getEntityManager()->clear();

        $ownerProxy = $this->getEntityManager()->getReference(TestAuditDataOwner::class, $owner->getId());

        //guard
        $this->assertInstanceOf(Proxy::class, $ownerProxy);

        return $ownerProxy;
    }

    /**
     * @return TestAuditDataChild
     */
    protected function createChild()
    {
        $child = new TestAuditDataChild();

        $this->getEntityManager()->persist($child);
        $this->getEntityManager()->flush();

        $this->getMessageProducer()->clear();

        return $child;
    }

    /**
     * @return array
     */
    protected function getFirstEntitiesChangedMessage()
    {
        $traces = $this->getMessageProducer()->getSentMessages();

        //guard
        $this->assertGreaterThanOrEqual(1, count($traces));
        $this->assertEquals(Topics::ENTITIES_CHANGED, $traces[0]['topic']);

        return $traces[0]['message'];
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getClient()->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return MessageCollector
     */
    private function getMessageProducer()
    {
        return $this->getClient()->getContainer()->get('oro_message_queue.client.message_producer');
    }

    /**
     * @return Client
     */
    abstract protected function getClient();
}
