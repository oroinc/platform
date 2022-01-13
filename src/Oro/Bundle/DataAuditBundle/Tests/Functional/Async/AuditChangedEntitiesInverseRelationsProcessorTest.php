<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Async;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\DataAuditBundle\Async\AuditChangedEntitiesInverseRelationsProcessor;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * @dbIsolationPerTest
 */
class AuditChangedEntitiesInverseRelationsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use AuditChangedEntitiesExtensionTrait;

    /** @var AuditChangedEntitiesInverseRelationsProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->processor = $this->getContainer()->get('oro_dataaudit.async.audit_changed_entities_inverse_relations');
    }

    public function testCouldBeGetFromContainerAsService(): void
    {
        $this->assertInstanceOf(AuditChangedEntitiesInverseRelationsProcessor::class, $this->processor);
    }

    public function testShouldDoNothingIfAnythingChangedInMessage(): void
    {
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => [],
        ]);

        $this->processor->process($message, $this->getConnection()->createSession());

        $this->assertStoredAuditCount(0);
    }

    public function testShouldReturnAckOnProcess(): void
    {
        $message = $this->createMessage([
            'timestamp' => time(),
            'transaction_id' => 'aTransactionId',
            'entities_inserted' => [
                '000000004ad74b060000000013bc8879' => [
                    'entity_class' => Country::class,
                    'entity_id' => '0',
                    'change_set' => [
                        'iso2Code' => [null, '0'],
                        'name' => [null, '0']
                    ]
                ]
            ],
            'entities_updated' => [],
            'entities_deleted' => [],
            'collections_updated' => [],
        ]);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getConnection()->createSession())
        );
    }

    private function createMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('some_message_id');

        return $message;
    }

    private function getConnection(): ConnectionInterface
    {
        return $this->getContainer()->get('oro_message_queue.transport.connection');
    }
}
