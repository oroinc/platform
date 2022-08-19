<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Test\Async\RedeliveryAwareMessageProcessor;
use Oro\Bundle\MessageQueueBundle\Test\Async\Topic\SampleNormalizableBodyTopic;
use Oro\Bundle\MessageQueueBundle\Test\Model\StdModel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Psr\Log\Test\TestLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedeliveryMessageExtensionTest extends WebTestCase
{
    private MessageProducerInterface $messageProducer;
    private QueueConsumer $consumer;

    protected function setUp(): void
    {
        $this->initClient();
        $container = self::getContainer();
        $this->messageProducer = $container->get('oro_message_queue.message_producer');
        $this->consumer = $container->get('oro_message_queue.consumption.queue_consumer');

        $this->clearMessages();
        RedeliveryAwareMessageProcessor::clearProcessedMessages();
    }

    protected function tearDown(): void
    {
        $this->clearMessages();
        RedeliveryAwareMessageProcessor::clearProcessedMessages();
    }

    public function testMessageConsumptionIsInterruptedByMessageLimit(): void
    {
        self::assertEquals([], RedeliveryAwareMessageProcessor::getProcessedMessages());

        $body = ['entity' => ['SampleClass', 42]];
        $this->messageProducer->send(SampleNormalizableBodyTopic::getName(), $body);

        $logger = new TestLogger();

        $this->consumer->bind('oro.default');
        $this->consumer->consume(
            new ChainExtension([
                // 1 (first message) + 1 (rejected message) + 1 (requeued message) = 3
                new LimitConsumedMessagesExtension(3),
                new LoggerExtension($logger),
            ])
        );

        self::assertFalse($logger->hasErrorRecords());

        $processedMessages = RedeliveryAwareMessageProcessor::getProcessedMessages();
        $resolvedBody = ['entity' => new StdModel(['SampleClass', 42])];
        self::assertCount(2, $processedMessages);
        self::assertEquals(
            ['body' => $resolvedBody, 'status' => MessageProcessorInterface::REQUEUE],
            $processedMessages[0]
        );
        self::assertEquals(
            ['body' => $resolvedBody, 'status' => MessageProcessorInterface::ACK],
            $processedMessages[1]
        );
    }

    private function clearMessages(): void
    {
        $connection = self::getContainer()->get(
            'oro_message_queue.transport.dbal.connection',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
        if ($connection instanceof DbalConnection) {
            $connection->getDBALConnection()->executeQuery('DELETE FROM ' . $connection->getTableName());
        }
    }
}
