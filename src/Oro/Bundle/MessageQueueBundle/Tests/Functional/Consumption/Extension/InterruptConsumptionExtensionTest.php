<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Test\Async\ChangeConfigProcessor;
use Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension\TestLogger;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InterruptConsumptionExtensionTest extends WebTestCase
{
    /** @var MessageProducerInterface */
    private $producer;

    /** @var MessageProcessorInterface */
    private $messageProcessor;

    /** @var TestLogger */
    private $logger;

    /** @var QueueConsumer */
    private $consumer;

    protected function setUp(): void
    {
        $this->initClient();
        $container = self::getContainer();
        $this->producer = $container->get('oro_message_queue.message_producer');
        $this->messageProcessor = $container->get('oro_message_queue.client.delegate_message_processor');
        $this->logger = new TestLogger();
        $this->consumer = $container->get('oro_message_queue.consumption.queue_consumer');
        $this->clearMessages();
    }

    protected function tearDown(): void
    {
        $this->clearMessages();
    }

    public function testMessageConsumptionIsNotInterruptedByMessageLimit()
    {
        $this->producer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_NOOP);
        $this->producer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_NOOP);

        $this->consumer->bind('oro.default', $this->messageProcessor);
        $this->consumer->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LoggerExtension($this->logger)
        ]));

        $this->assertInterruptionMessage('Consuming interrupted, reason: The message limit reached.');
    }

    public function testMessageConsumptionIsInterruptedByConfigCacheChanged()
    {
        $this->producer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_CHANGE_CACHE);
        $this->producer->send(ChangeConfigProcessor::TEST_TOPIC, ChangeConfigProcessor::COMMAND_CHANGE_CACHE);

        $this->consumer->bind('oro.default', $this->messageProcessor);
        $this->consumer->consume(new ChainExtension([
            new LimitConsumedMessagesExtension(2),
            new LoggerExtension($this->logger)
        ]));

        $this->assertInterruptionMessage('Consuming interrupted, reason: The cache has changed.');
    }

    private function assertInterruptionMessage(string $expectedMessage): void
    {
        $this->assertTrue($this->logger->hasRecord($expectedMessage, 'warning'));
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
