<?php
namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
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

        self::getMessageCollector()->clear();

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

        self::getMessageCollector()->clear();

        return $child;
    }

    /**
     * @return array
     */
    protected function getFirstEntitiesChangedMessage()
    {
        $messages = self::getSentMessages();

        //guard
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertEquals(Topics::ENTITIES_CHANGED, $messages[0]['topic']);

        return $messages[0]['message'];
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->getClient()->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return Client
     */
    abstract protected function getClient();
}
