<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This class is intended to be used in functional tests and allows to get sent messages.
 */
class MessageCollector implements MessageProducerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var array [['topic' => topic name, 'message' => message (string|array|Message)], ...]
     */
    private $sentMessages = [];

    /**
     * @var bool
     */
    private $enabled = false;

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->messageProducer->send($topic, $message);

        if ($this->enabled) {
            $this->sentMessages[] = ['topic' => $topic, 'message' => $message];
        }
    }

    /**
     * Gets all sent messages.
     *
     * @return array [['topic' => topic name, 'message' => message (string|array|Message)], ...]
     */
    public function getSentMessages()
    {
        return $this->sentMessages;
    }

    /**
     * Removes all collected messages.
     *
     * $return self
     */
    public function clear()
    {
        $this->sentMessages = [];

        return $this;
    }

    /**
     * Disables the collecting of messages.
     *
     * $return self
     */
    public function disable()
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Enables the collecting of messages.
     *
     * $return self
     */
    public function enable()
    {
        $this->enabled = true;

        return $this;
    }
}
