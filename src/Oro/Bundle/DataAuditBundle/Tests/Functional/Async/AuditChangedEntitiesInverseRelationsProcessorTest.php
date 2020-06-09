<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseRelationsProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;

/**
 * @dbIsolationPerTest
 */
class AuditChangedEntitiesInverseRelationsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        /** @var AuditChangedEntitiesInverseRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_inverse_relations');

        $this->assertInstanceOf(AuditChangedEntitiesInverseRelationsProcessor::class, $processor);
    }

    public function testShouldDoNothingIfAnythingChangedInMessage()
    {
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => [],
        ]);

        /** @var AuditChangedEntitiesInverseRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_inverse_relations');
        /** @var ConnectionInterface $connection */
        $connection = $this->getContainer()->get('oro_message_queue.transport.connection');

        $processor->process($message, $connection->createSession());

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
            'collections_updated' => [],
        ]);

        /** @var AuditChangedEntitiesInverseRelationsProcessor $processor */
        $processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_inverse_relations');
        /** @var ConnectionInterface $connection */
        $connection = $this->getContainer()->get('oro_message_queue.transport.connection');
        $session = $connection->createSession();

        $this->assertEquals(MessageProcessorInterface::ACK, $processor->process($message, $session));
    }

    private function assertStoredAuditCount($expected)
    {
        $this->assertCount($expected, $this->getEntityManager()->getRepository(Audit::class)->findAll());
    }

    /**
     * @param array $body
     * @return Message
     */
    private function createMessage(array $body)
    {
        $message = new Message();
        $message->setBody(json_encode($body));

        return $message;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
