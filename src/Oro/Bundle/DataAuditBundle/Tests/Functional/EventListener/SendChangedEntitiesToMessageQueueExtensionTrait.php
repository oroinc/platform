<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesTopic;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\Message;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait SendChangedEntitiesToMessageQueueExtensionTrait
{
    use MessageQueueExtension;

    public function assertEntitiesInsertedInMessageCount(int $expected, Message $message): void
    {
        $this->assertTrue(isset($message->getBody()['entities_inserted']));
        $this->assertCount($expected, $message->getBody()['entities_inserted']);
    }

    public function assertEntitiesUpdatedInMessageCount(int $expected, Message $message): void
    {
        $this->assertTrue(isset($message->getBody()['entities_updated']));
        $this->assertCount($expected, $message->getBody()['entities_updated']);
    }

    public function assertEntitiesDeletedInMessageCount(int $expected, Message $message): void
    {
        $this->assertTrue(isset($message->getBody()['entities_deleted']));
        $this->assertCount($expected, $message->getBody()['entities_deleted']);
    }

    public function assertCollectionsUpdatedInMessageCount(int $expected, Message $message): void
    {
        $this->assertTrue(isset($message->getBody()['collections_updated']));
        $this->assertCount($expected, $message->getBody()['collections_updated']);
    }

    private function createOwner(): TestAuditDataOwner
    {
        $owner = new TestAuditDataOwner();

        $this->getEntityManager()->persist($owner);
        $this->getEntityManager()->flush();

        self::getMessageCollector()->clear();

        return $owner;
    }

    private function createOwnerProxy(): TestAuditDataOwner|Proxy
    {
        $owner = $this->createOwner();

        $this->getEntityManager()->clear();

        $ownerProxy = $this->getEntityManager()->getReference(TestAuditDataOwner::class, $owner->getId());

        //guard
        $this->assertInstanceOf(Proxy::class, $ownerProxy);

        return $ownerProxy;
    }

    private function createChild(): TestAuditDataChild
    {
        $child = new TestAuditDataChild();

        $this->getEntityManager()->persist($child);
        $this->getEntityManager()->flush();

        self::getMessageCollector()->clear();

        return $child;
    }

    private function getFirstEntitiesChangedMessage(): Message
    {
        $messages = self::getSentMessages(false);

        //guard
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertEquals(AuditChangedEntitiesTopic::getName(), $messages[0]['topic']);

        return $messages[0]['message'];
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getClientInstance()->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @return KernelBrowser
     */
    abstract protected static function getClientInstance();
}
