<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Doctrine\DBAL\Types\Types;
use Monolog\Handler\TestHandler;
use Oro\Bundle\TestFrameworkBundle\Mailer\EventListener\MessageLoggerListener;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageBuilderInterface;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Test\Async\Extension\ConsumedMessagesCollectorExtension;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueConsumerTestTrait
{
    protected static function getConsumer(): QueueConsumer
    {
        return self::getContainer()->get('oro_message_queue.consumption.queue_consumer');
    }

    protected static function purgeMessageQueue(string $queueName = 'oro.default'): void
    {
        $connection = self::getContainer()->get(
            'oro_message_queue.transport.connection',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );

        if ($connection instanceof DbalConnection) {
            $tableName = $connection->getDBALConnection()->quoteIdentifier($connection->getTableName());
            $connection->getDBALConnection()->executeStatement(
                'DELETE FROM ' . $tableName . ' WHERE queue = :queueName',
                ['queueName' => $queueName],
                ['queueName' => Types::STRING]
            );
        }
    }

    protected static function sendMessage(
        string $topic,
        Message|MessageBuilderInterface|array|string|null $message
    ): Message {
        self::getMessageProducer()->send($topic, $message);

        if ($message instanceof MessageBuilderInterface) {
            $message = $message->getMessage();
        }

        if ($message instanceof Message) {
            $messageBody = $message->getBody();
        } else {
            $messageBody = $message;
        }

        self::assertMessageSent($topic, $messageBody);
        $sentMessages = self::getSentMessagesByTopic($topic, false);

        return array_pop($sentMessages);
    }

    protected static function consume(?int $messagesCount = null, int $timeLimit = 10): void
    {
        $messageLoggerListener = self::getContainer()
            ->get('mailer.message_logger_listener');
        if ($messageLoggerListener instanceof MessageLoggerListener) {
            // Makes mailer message logger listener ignore reset to keep state during MQ consumption.
            $messageLoggerListener->setSkipReset(true);
        }

        self::getLoggerTestHandler()->setSkipReset(true);

        try {
            $queueConsumer = self::getConsumer();

            // Unbinds queue so consumption can start more than 1 time.
            \Closure::bind(static function (QueueConsumer $queueConsumer) {
                unset($queueConsumer->boundMessageProcessors['oro.default']);
            }, null, QueueConsumer::class)($queueConsumer);

            $queueConsumer
                ->bind('oro.default')
                ->consume(
                    new ChainExtension([
                        new LimitConsumedMessagesExtension($messagesCount ?? count(self::getSentMessages())),
                        new LimitConsumptionTimeExtension(new \DateTime('+' . $timeLimit . ' sec')),
                    ])
                );

            if ($messagesCount === null && count(self::getSentMessages()) > count(self::getProcessedMessages())) {
                // Consumes messages that were sent during the previous consumption.
                self::consume($messagesCount, $timeLimit);
            }
        } finally {
            $messageLoggerListener->setSkipReset(false);
            self::getLoggerTestHandler()->setSkipReset(false);
        }
    }

    protected static function consumeMessage(Message|string $message): void
    {
        $messageId = is_object($message) ? $message->getMessageId() : $message;

        $processedMessagesCount = count(self::getProcessedMessages());
        while (true) {
            self::consume(1);

            $processedMessages = self::getProcessedMessages();

            if (count($processedMessages) === $processedMessagesCount) {
                // No messages were processed during consumption.
                break;
            }

            $processedMessagesCount = count($processedMessages);
            foreach ($processedMessages as $processedMessage) {
                if ($processedMessage['message']->getMessageId() === $messageId) {
                    // Desired message is processed.
                    return;
                }
            }
        }

        self::fail(sprintf('The message #%s was not found among processed messages', $messageId));
    }

    protected static function getConsumedMessagesCollector(): ConsumedMessagesCollectorExtension
    {
        return self::getContainer()->get('oro_message_queue.test.async.extension.consumed_messages_collector');
    }

    protected static function getLoggerTestHandler(): TestHandler
    {
        return self::getConsumedMessagesCollector()->getLoggerTestHandler();
    }

    /**
     * @return array<int, array{topic: string, message: MessageInterface, context: Context}>
     */
    protected static function getProcessedMessages(): array
    {
        return self::getConsumedMessagesCollector()->getProcessedMessages();
    }

    protected static function clearProcessedMessages(): void
    {
        if (null === self::getContainer()) {
            return;
        }

        self::getConsumedMessagesCollector()->clearProcessedMessages();
    }

    protected static function clearTopicMessages(string $topic): void
    {
        self::getConsumedMessagesCollector()->clearProcessedMessagesByTopic($topic);
    }

    /**
     * @return array{topic: string, message: MessageInterface, context: Context}
     */
    protected static function getProcessedMessage(MessageInterface|Message|string $message): array
    {
        $messageId = is_object($message) ? $message->getMessageId() : $message;

        $messages = [];
        /** @var array{topic: string, message: MessageInterface, context: Context} $processedMessage */
        foreach (self::getProcessedMessages() as $processedMessage) {
            if ($processedMessage['message']->getMessageId() === $messageId) {
                $messages[] = $processedMessage;
            }
        }

        self::assertCount(
            1,
            $messages,
            sprintf(
                '%d messages with id %s found: %s.',
                count($messages),
                $messageId,
                json_encode($messages, JSON_THROW_ON_ERROR)
            )
        );

        return reset($messages);
    }

    protected static function assertProcessedMessageStatus(
        string $status,
        MessageInterface|Message|string $message
    ): void {
        $processedMessage = self::getProcessedMessage($message);

        self::assertEquals($status, $processedMessage['context']->getStatus());
    }

    protected static function assertProcessedMessageProcessor(
        string $processorName,
        MessageInterface|Message|string $message
    ): void {
        $processedMessage = self::getProcessedMessage($message);

        self::assertEquals($processorName, $processedMessage['context']->getMessageProcessorName());
    }

    /**
     * @return array<int, array{topic: string, message: MessageInterface, context: Context}>
     */
    protected static function getProcessedMessagesByStatus(string $status): array
    {
        return array_filter(
            self::getProcessedMessages(),
            static fn (array $processedMessage) => $processedMessage['context']->getStatus() === $status
        );
    }

    /**
     * @return array<int, array{topic: string, message: MessageInterface, context: Context}>
     */
    protected static function getProcessedMessagesByTopic(string $topic): array
    {
        return array_filter(
            self::getProcessedMessages(),
            static fn (array $processedMessage) => $processedMessage['topic'] === $topic
        );
    }
}
