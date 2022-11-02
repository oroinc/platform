<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * A message queue driver for DBAL connection that implements driver interface.
 * @see \Oro\Component\MessageQueue\Client\DriverInterface
 */
class DbalDriver implements DriverInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Config
     */
    private $config;

    public function __construct(SessionInterface $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(QueueInterface $queue, Message $message): void
    {
        $headers = $message->getHeaders();
        $properties = $message->getProperties();

        $headers['content_type'] = $message->getContentType();

        $transportMessage = $this->createTransportMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($properties);

        $transportMessage->setMessageId((string)$message->getMessageId());

        if ($message->getTimestamp()) {
            $transportMessage->setTimestamp($message->getTimestamp());
        }

        if ($message->getDelay()) {
            $transportMessage->setDelay($message->getDelay());
        }

        if ($message->getPriority()) {
            $transportMessage->setPriority(MessagePriority::getMessagePriority($message->getPriority()));
        }

        $this->session->createProducer()->send($queue, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function createTransportMessage():  MessageInterface
    {
        return $this->session->createMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue(string $queueName): QueueInterface
    {
        return $this->session->createQueue($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
