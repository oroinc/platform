<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Test\Async\ChangeConfigProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Psr\Log\Test\TestLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InterruptConsumptionExtensionTest extends WebTestCase
{
    private MessageProducerInterface $messageProducer;
    private QueueConsumer $consumer;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->initClient();
        $container = self::getContainer();
        $this->messageProducer = $container->get('oro_message_queue.message_producer');
        $this->consumer = $container->get('oro_message_queue.consumption.queue_consumer');
        $this->logger = new TestLogger();
        $this->clearMessages();
    }

    protected function tearDown(): void
    {
        $this->clearMessages();
    }

    public function testMessageConsumptionIsInterruptedByMessageLimit(): void
    {
        $this->messageProducer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_NOOP);
        $this->messageProducer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_NOOP);

        $this->consumer->bind('oro.default', 'oro_message_queue.async.change_config');
        $this->consumer->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LoggerExtension($this->logger)
        ]));

        $this->assertInterruptionMessage('Consuming interrupted, reason: The message limit reached.');
    }

    public function testMessageConsumptionIsInterruptedByConfigCacheChanged(): void
    {
        $this->messageProducer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_CHANGE_CACHE);
        $this->messageProducer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_CHANGE_CACHE);

        $this->consumer->bind('oro.default', 'oro_message_queue.async.change_config');
        $this->consumer->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LoggerExtension($this->logger)
        ]));

        $this->assertInterruptionMessage('Consuming interrupted, reason: The cache has changed.');
    }

    private function assertInterruptionMessage(string $expectedMessage): void
    {
        self::assertTrue($this->logger->hasRecord($expectedMessage, 'warning'));
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
