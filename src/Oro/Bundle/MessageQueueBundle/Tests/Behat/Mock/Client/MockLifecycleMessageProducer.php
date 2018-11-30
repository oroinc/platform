<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Behat\Mock\Client;

use Doctrine\Common\Cache\Cache;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Indicate that cached messages buffer is enabled and get messages from cached buffer
 */
class MockLifecycleMessageProducer implements MessageProducerInterface
{
    /** @var MessageProducerInterface */
    private $innerProducer;

    /** @var Cache */
    private $cache;

    /**
     * @param MessageProducerInterface $producer
     * @param Cache $cache
     */
    public function __construct(MessageProducerInterface $producer, Cache $cache)
    {
        $this->innerProducer = $producer;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if ($message instanceof Message) {
            $message = clone $message;
        } else {
            $body = $message;
            $message = new Message();
            $message->setBody($body);
        }

        $message->setDelay(null);
        $message->setMessageId(uniqid('oro.', true));
        $this->saveCache($message->getMessageId());

        $this->innerProducer->send($topic, $message);
    }

    /**
     * @param string $messageId
     */
    private function saveCache($messageId)
    {
        $messages = $this->getSendMessages();
        $messages[$messageId] = true;
        $this->cache->save('send_messages', serialize($messages));
    }

    /**
     * @return array
     */
    private function getSendMessages()
    {
        if (!$this->cache->contains('send_messages')) {
            return [];
        }

        $messages = unserialize($this->cache->fetch('send_messages'));
        if (!is_array($messages)) {
            return [];
        }

        return $messages;
    }
}
