<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Bundle\MessageQueueBundle\Test\MessageCollector as BaseMessageCollector;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This class is intended to be used in functional tests and allows to get sent messages.
 */
class MessageCollector extends BaseMessageCollector
{
    /** @var MessageFilterInterface */
    private $filter;

    /** @var array|null [['topic' => topic name, 'message' => message (string|array|Message)], ...] */
    private $filteredSentMessages;

    /**
     * @param MessageProducerInterface $messageProducer
     * @param MessageFilterInterface   $filter
     */
    public function __construct(MessageProducerInterface $messageProducer, MessageFilterInterface $filter)
    {
        parent::__construct($messageProducer);
        $this->filter = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->filteredSentMessages = null;
        parent::send($topic, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function getSentMessages()
    {
        if (null === $this->filteredSentMessages) {
            $this->filteredSentMessages = [];
            $sentMessages = parent::getSentMessages();
            if ($sentMessages) {
                $buffer = new MessageBuffer();
                foreach ($sentMessages as $trace) {
                    $buffer->addMessage($trace['topic'], $trace['message']);
                }
                $this->filter->apply($buffer);
                $filteredMessages = $buffer->getMessages();
                foreach ($filteredMessages as [$topic, $message]) {
                    $this->filteredSentMessages[] = ['topic' => $topic, 'message' => $message];
                }
            }
        }

        return $this->filteredSentMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function clearTopicMessages($topic)
    {
        if (null !== $this->filteredSentMessages) {
            $filteredTraces = [];
            foreach ($this->filteredSentMessages as $trace) {
                if ($topic !== $trace['topic']) {
                    $filteredTraces[] = $trace;
                }
            }
            $this->filteredSentMessages = $filteredTraces;
        }

        return parent::clearTopicMessages($topic);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->filteredSentMessages = null;

        return parent::clear();
    }
}
