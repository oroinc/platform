<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

/**
 * The message producer that can be used in case if the sending messages
 * to the queue should be postponed by some reasons.
 * When the buffering is enabled, stores messages in the internal buffer.
 * When the buffering is disabled, sends messages directly to the queue via the decorated message producer.
 * By default the buffering is disabled.
 */
class BufferedMessageProducer implements MessageProducerInterface
{
    /** @var MessageProducerInterface */
    private $innerProducer;

    /** @var LoggerInterface */
    private $logger;

    /** @var MessageFilterInterface */
    private $filter;

    /** @var MessageBuffer|null */
    private $buffer;

    /** @var int */
    private $enableBufferingNestingLevel = 0;

    public function __construct(
        MessageProducerInterface $producer,
        LoggerInterface $logger,
        MessageFilterInterface $filter
    ) {
        $this->innerProducer = $producer;
        $this->logger = $logger;
        $this->filter = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if ($this->enableBufferingNestingLevel > 0) {
            if (null === $this->buffer) {
                $this->buffer = new MessageBuffer();
            }
            $this->buffer->addMessage($topic, $message);
        } else {
            $buffer = new MessageBuffer();
            $buffer->addMessage($topic, $message);
            $this->sendMessages($buffer);
        }
    }

    /**
     * Indicates whether the buffering of messages is enabled.
     */
    public function isBufferingEnabled(): bool
    {
        return $this->enableBufferingNestingLevel > 0;
    }

    /**
     * Enables the buffering of messages.
     * In this mode messages are not sent, instead they are added to internal buffer.
     * To send collected messages to the queue the "flushBuffer" method should be called.
     * To remove all collected messages without sending them to the queue the "clearBuffer" method should be called.
     */
    public function enableBuffering(): void
    {
        $this->enableBufferingNestingLevel++;
    }

    /**
     * Disables the buffering of messages.
     * In this mode messages are sent to the queue directly without buffering.
     * Please note that this method does nothing with already buffered messages;
     * to send them to the queue the buffering should be enabled and the "flushBuffer" method should be called.
     *
     * @throws \LogicException if the buffering of messages is already disabled
     */
    public function disableBuffering(): void
    {
        if (0 === $this->enableBufferingNestingLevel) {
            $this->logger->critical(
                'The buffered message producer fails because the buffering of messages is already disabled.'
            );
            throw new \LogicException('The buffering of messages is already disabled.');
        }
        $this->enableBufferingNestingLevel--;
    }

    /**
     * Checks whether the buffer contains at least one message.
     */
    public function hasBufferedMessages(): bool
    {
        return null !== $this->buffer && $this->buffer->hasMessages();
    }

    /**
     * Flushes buffered messages.
     *
     * @throws \LogicException if the buffering of messages is disabled
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception if the sending a message to the queue
     * fails due to some internal error
     */
    public function flushBuffer(): void
    {
        $this->assertBufferingEnabled();
        if (null !== $this->buffer && $this->buffer->hasMessages()) {
            $this->sendMessages($this->buffer);
        }
    }

    /**
     * Clears buffered messages.
     *
     * @throws \LogicException if the buffering of messages is disabled
     */
    public function clearBuffer(): void
    {
        $this->assertBufferingEnabled();
        if (null !== $this->buffer) {
            $this->buffer->clear();
        }
    }

    /**
     * Asserts that the buffering of messages is enabled.
     *
     * @throws \LogicException if the buffering is disabled
     */
    private function assertBufferingEnabled(): void
    {
        if (0 === $this->enableBufferingNestingLevel) {
            $this->logger->critical(
                'The buffered message producer fails because the buffering of messages is disabled.'
            );
            throw new \LogicException('The buffering of messages is disabled.');
        }
    }

    /**
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception if the sending a message to the queue
     * fails due to some internal error
     */
    private function sendMessages(MessageBuffer $buffer): void
    {
        try {
            $this->filter->apply($buffer);
            $messages = $buffer->getMessages();
            foreach ($messages as [$topic, $message]) {
                $this->innerProducer->send($topic, $message);
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'The buffered message producer fails to send messages to the queue.',
                ['exception' => $e]
            );
            throw $e;
        } finally {
            $buffer->clear();
        }
    }
}
