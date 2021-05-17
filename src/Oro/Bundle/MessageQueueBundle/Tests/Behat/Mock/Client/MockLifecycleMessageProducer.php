<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Behat\Mock\Client;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageBuilderInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\PdoAdapter;

/**
 * Indicate that cached messages buffer is enabled and get messages from cached buffer
 */
class MockLifecycleMessageProducer implements MessageProducerInterface
{
    /** @var MessageProducerInterface */
    private $innerProducer;

    /** @var PdoAdapter */
    private $cache;

    public function __construct(MessageProducerInterface $producer, PdoAdapter $cache)
    {
        $this->innerProducer = $producer;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        if ($message instanceof MessageBuilderInterface) {
            $message = $message->getMessage();
        }
        if ($message instanceof Message) {
            $message = clone $message;
        } else {
            $body = $message;
            $message = new Message();
            $message->setBody($body);
        }

        $message->setDelay(null);
        $message->setMessageId(uniqid($topic.'.', true));

        /** @var CacheItemInterface $item */
        $item = $this->cache->getItem($message->getMessageId());
        $item->set($message->getMessageId());
        $this->cache->save($item);

        $this->innerProducer->send($topic, $message);
    }
}
