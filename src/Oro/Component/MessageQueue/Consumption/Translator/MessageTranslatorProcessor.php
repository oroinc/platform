<?php
namespace Oro\Component\MessageQueue\Consumption\Translator;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class MessageTranslatorProcessor implements MessageProcessorInterface
{
    /**
     * @var string
     */
    protected $topicName;

    /**
     * @param string $topicName
     */
    public function __construct($topicName)
    {
        $this->topicName = $topicName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $topic = $session->createTopic($this->topicName);
        $newMessage = $session->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());

        $session->createProducer()->send($topic, $newMessage);
    }
}
