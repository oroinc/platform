<?php

namespace Oro\Bundle\MessageQueueBundle\Client;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

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

    /** @var array [[topic, message], ...] */
    private $buffer = [];

    /** @var bool */
    private $bufferEnabled = false;

    /**
     * @param MessageProducerInterface $producer
     */
    public function __construct(MessageProducerInterface $producer)
    {
        $this->innerProducer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if ($this->bufferEnabled) {
            $this->buffer[] = [$topic, $message];
        } else {
            $this->innerProducer->send($topic, $message);
        }
    }

    /**
     * Indicates whather the buffering of messages is enabled.
     */
    public function isBufferingEnabled()
    {
        return $this->bufferEnabled;
    }

    /**
     * Enables the buffering of messages.
     * In this mode messages are not sent, instead they are added to internal buffer.
     * To send collected messages to the queue the "flushBuffer" method should be called.
     * To remove all collected messages without sending them to the queue the "clearBuffer" method should be called.
     */
    public function enableBuffering()
    {
        $this->bufferEnabled = true;
    }

    /**
     * Disables the buffering of messages.
     * In this mode messages are sent to the queue directly without buffering.
     * Please note that this method does nothing with already buffered messages;
     * to send them to the queue the buffering should be enabled and the "flushBuffer" method should be called.
     */
    public function disableBuffering()
    {
        $this->bufferEnabled = false;
    }

    /**
     * Flushes buffered messages.
     *
     * @throws \LogicException if the buffering of messages is disabled
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception if the sending a message to the queue
     * fails due to some internal error
     */
    public function flushBuffer()
    {
        $this->assertBufferingEnabled();
        try {
            foreach ($this->buffer as list($topic, $message)) {
                $this->innerProducer->send($topic, $message);
            }
        } finally {
            $this->buffer = [];
        }
    }

    /**
     * Clears buffered messages.
     *
     * @throws \LogicException if the buffering of messages is disabled
     */
    public function clearBuffer()
    {
        $this->assertBufferingEnabled();
        $this->buffer = [];
    }

    /**
     * Asserts that the buffering of messages is enabled.
     *
     * @throws \LogicException if the buffering is disabled
     */
    private function assertBufferingEnabled()
    {
        if (!$this->bufferEnabled) {
            throw new \LogicException('The buffering of messages is disabled.');
        }
    }
}
