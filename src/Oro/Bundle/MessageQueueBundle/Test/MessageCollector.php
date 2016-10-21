<?php

namespace Oro\Bundle\MessageQueueBundle\Test;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This class is intended to be used in unit and functional tests and allows to get sent messages.
 */
class MessageCollector implements MessageProducerInterface
{
    /**
     * @var MessageProducerInterface|null
     */
    private $messageProducer;

    /**
     * @var array [['topic' => topic name, 'message' => message (string|array|Message)], ...]
     */
    private $sentMessages = [];


    /**
     * @param MessageProducerInterface|null $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer = null)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if (null !== $this->messageProducer) {
            $this->messageProducer->send($topic, $message);
        }

        $this->sentMessages[] = ['topic' => $topic, 'message' => $message];
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
     * @param string $topic
     *
     * @return array
     */
    public function getTopicSentMessages($topic)
    {
        $topicTraces = [];
        foreach ($this->getSentMessages() as $trace) {
            if ($topic === $trace['topic']) {
                $topicTraces[] = $trace;
            }
        }

        return $topicTraces;
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
}
