<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesRelationsProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;

/**
 * @dbIsolationPerTest
 */
class AuditChangedEntitiesRelationsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var AuditChangedEntitiesRelationsProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_relations');
    }

    public function testShouldDoNothingWhenNoCollectionsUpdated()
    {
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => [],
        ]);

        $result = $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        $this->assertStoredAuditCount(0);
    }

    public function testShouldReturnAckOnProcess()
    {
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => [
                '000000007ec8f22c00000000536823d4' => [
                    'entity_class' => Organization::class,
                    'entity_id' => 1,
                    'change_set' => [],
                ],
            ],
        ]);

        $result = $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
        $this->assertStoredAuditCount(0);
    }

    private function assertStoredAuditCount(int $expected): void
    {
        $this->assertCount($expected, $this->getEntityManager()->getRepository(Audit::class)->findAll());
    }

    private function createMessage(array $body): Message
    {
        $message = new Message();
        $message->setBody($body);

        return $message;
    }

    private function getConnection(): ConnectionInterface
    {
        return self::getContainer()->get('oro_message_queue.transport.connection');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
